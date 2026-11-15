<?php

// app/Services/ClerkTokenService.php

namespace App\Services;

use App\Models\Customer;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Verifies Clerk-issued Bearer JWTs locally against Clerk's JWKS and
 * maps the verified email to a Customer. No Clerk identifiers are
 * stored locally — see docs/adr/0001-clerk-email-only-linking.md.
 */
class ClerkTokenService
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * The verified claims from the request's bearer token, or null
     * when no token was presented.
     *
     * @return object{email: string}|null
     */
    public function claimsFrom(Request $request): ?object
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return null;
        }

        try {
            $claims = JWT::decode($token, $this->keySet());
        } catch (\RuntimeException $e) {
            // Signature, expiry, format — any invalid token is a 401,
            // never a 500.
            throw new UnauthorizedHttpException('Bearer', 'Invalid token.', $e);
        }

        $this->assertClaims($claims);

        if (($claims->email_verified ?? false) !== true || empty($claims->email)) {
            throw new UnauthorizedHttpException('Bearer', 'Clerk email is not verified.');
        }

        return $claims;
    }

    /**
     * The email-matched customer — never created here.
     */
    public function customerFor(object $claims): ?Customer
    {
        return Customer::query()
            ->where('email', strtolower((string) $claims->email))
            ->first();
    }

    /**
     * Verify a presented bearer token (if any) so an invalid token is
     * rejected with 401 even on public endpoints; absent tokens are
     * fine — that is the guest flow.
     */
    public function assertTokenValidIfPresent(Request $request): void
    {
        if ($request->bearerToken() !== null) {
            $this->claimsFrom($request);
        }
    }

    /**
     * When an authenticated customer places an order, the payload
     * email must match the verified JWT email — otherwise the order
     * would attach to another customer.
     */
    public function assertPayloadEmailMatches(Request $request, ?string $payloadEmail): void
    {
        $claims = $this->claimsFrom($request);

        if ($claims === null) {
            return;
        }

        if ($payloadEmail !== null && strtolower($payloadEmail) !== strtolower((string) $claims->email)) {
            throw ValidationException::withMessages([
                'email' => 'This email doesn\'t match your signed-in account.',
            ]);
        }
    }

    private function keySet(): CachedKeySet
    {
        return new CachedKeySet(
            config('clerk.jwks_url'),
            app(Client::class),
            app(HttpFactory::class),
            $this->psr6Cache(),
            null,
            $this->cacheSeconds(),
        );
    }

    private function cacheSeconds(): int
    {
        return (int) config('clerk.cache_seconds', 600);
    }

    /**
     * A PSR-6 pool backed by Laravel's cache store, for CachedKeySet.
     */
    private function psr6Cache(): CacheItemPoolInterface
    {
        return new class($this->cacheSeconds(), $this->cache) implements CacheItemPoolInterface
        {
            public function __construct(private readonly int $cacheSeconds, private readonly CacheRepository $cache) {}

            public function getItem(string $key): CacheItemInterface
            {
                return new class($key, $this->cache->get($key)) implements CacheItemInterface
                {
                    public mixed $stored = null;

                    public function __construct(
                        private readonly string $key,
                        private readonly mixed $value,
                    ) {}

                    public function getKey(): string
                    {
                        return $this->key;
                    }

                    public function get(): mixed
                    {
                        return $this->value;
                    }

                    public function isHit(): bool
                    {
                        return $this->value !== null;
                    }

                    public function set(mixed $value): static
                    {
                        $this->stored = $value;

                        return $this;
                    }

                    public function expiresAt(?\DateTimeInterface $expiration): static
                    {
                        return $this;
                    }

                    public function expiresAfter(int|\DateInterval|null $time): static
                    {
                        return $this;
                    }
                };
            }

            public function getItems(array $keys = []): iterable
            {
                foreach ($keys as $key) {
                    yield $key => $this->getItem($key);
                }
            }

            public function hasItem(string $key): bool
            {
                return $this->cache->has($key);
            }

            public function clear(): bool
            {
                return true;
            }

            public function deleteItem(string $key): bool
            {
                return $this->cache->forget($key);
            }

            public function deleteItems(array $keys): bool
            {
                foreach ($keys as $key) {
                    $this->deleteItem($key);
                }

                return true;
            }

            public function save(CacheItemInterface $item): bool
            {
                /** @var object{stored: mixed} $item */
                return $this->cache->put($item->getKey(), $item->stored, $this->cacheSeconds);
            }

            public function saveDeferred(CacheItemInterface $item): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }
        };
    }

    /**
     * Issuer and authorized-party checks after signature verification.
     */
    private function assertClaims(object $claims): void
    {
        if (($claims->iss ?? null) !== config('clerk.issuer')) {
            throw new UnauthorizedHttpException('Bearer', 'Unknown token issuer.');
        }

        $origins = (array) config('clerk.allowed_origins');
        $azp = $claims->azp ?? null;

        if ($origins === [] || $azp === null || ! in_array($azp, $origins, true)) {
            throw new UnauthorizedHttpException('Bearer', 'Token origin is not allowed.');
        }
    }
}
