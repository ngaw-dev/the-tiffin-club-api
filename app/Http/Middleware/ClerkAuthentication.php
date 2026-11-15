<?php

// app/Http/Middleware/ClerkAuthentication.php

namespace App\Http\Middleware;

use App\Services\ClerkTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Rejects requests without a valid, email-verified Clerk Bearer JWT
 * and resolves the email-matched Customer. No Clerk identifiers are
 * stored locally — see docs/adr/0001-clerk-email-only-linking.md.
 */
class ClerkAuthentication
{
    public function __construct(
        private readonly ClerkTokenService $clerkTokens,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $claims = $this->clerkTokens->claimsFrom($request);

        if ($claims === null) {
            throw new UnauthorizedHttpException('Bearer', 'No bearer token provided.');
        }

        $request->setUserResolver(fn () => $this->clerkTokens->customerFor($claims));
        $request->attributes->set('clerk_claims', $claims);

        return $next($request);
    }
}
