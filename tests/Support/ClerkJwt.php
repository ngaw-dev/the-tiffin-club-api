<?php

// tests/Support/ClerkJwt.php

namespace Tests\Support;

use Firebase\JWT\JWT;

/**
 * Mints fake Clerk-issued JWTs signed with a local RSA key, and
 * serves the matching JWKS for ClerkAuthentication tests.
 */
final class ClerkJwt
{
    public const string ISSUER = 'https://tiffin-test.clerk.accounts.dev';

    public const string ORIGIN = 'http://localhost:3000';

    private static ?string $privateKey = null;

    private static ?array $publicJwk = null;

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function token(string $email, array $overrides = []): string
    {
        $payload = array_merge([
            'iss' => self::ISSUER,
            'azp' => self::ORIGIN,
            'sub' => 'user_test_'.md5($email),
            'aud' => null,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'email' => $email,
            'email_verified' => true,
        ], $overrides);

        return JWT::encode($payload, self::privateKey(), 'RS256', 'test-key');
    }

    /**
     * Public JWKS document matching the signing key.
     *
     * @return array<string, mixed>
     */
    public static function jwks(): array
    {
        self::ensureKeys();

        return ['keys' => [self::$publicJwk]];
    }

    private static function privateKey(): string
    {
        self::ensureKeys();

        return self::$privateKey;
    }

    private static function ensureKeys(): void
    {
        if (self::$privateKey !== null) {
            return;
        }

        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privateKey);
        self::$privateKey = $privateKey;

        $details = openssl_pkey_get_details($res);

        self::$publicJwk = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => 'test-key',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ];
    }
}
