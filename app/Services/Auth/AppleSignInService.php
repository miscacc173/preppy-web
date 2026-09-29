<?php

namespace App\Services\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AppleSignInService
{
    private const AUTHORIZE_URL = 'https://appleid.apple.com/auth/authorize';
    private const TOKEN_URL = 'https://appleid.apple.com/auth/token';
    private const KEYS_URL = 'https://appleid.apple.com/auth/keys';

    public function enabled(): bool
    {
        return (bool) config('services.apple_sign_in.enabled', false);
    }

    public function authorizationUrl(string $state, string $nonce): string
    {
        $this->assertConfigured();

        return self::AUTHORIZE_URL.'?'.http_build_query([
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'client_id' => config('services.apple_sign_in.client_id'),
            'redirect_uri' => config('services.apple_sign_in.redirect'),
            'scope' => 'name email',
            'state' => $state,
            'nonce' => $nonce,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array<string,mixed> */
    public function exchangeCode(string $code): array
    {
        $this->assertConfigured();
        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'client_id' => config('services.apple_sign_in.client_id'),
            'client_secret' => $this->clientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.apple_sign_in.redirect'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Apple authorization code exchange failed.');
        }

        $payload = $response->json();
        if (! is_array($payload) || empty($payload['id_token'])) {
            throw new RuntimeException('Apple did not return a valid identity token.');
        }

        return $payload;
    }

    /** @return array<string,mixed> */
    public function verifyIdentityToken(string $identityToken, ?string $expectedNonce = null, ?array $allowedAudiences = null): array
    {
        $this->assertConfigured();
        $jwks = Cache::remember('apple-sign-in:jwks', now()->addHours(6), function (): array {
            $response = Http::timeout(15)->get(self::KEYS_URL);
            if (! $response->successful()) throw new RuntimeException('Unable to retrieve Apple signing keys.');
            return $response->json();
        });

        $claims = (array) JWT::decode($identityToken, JWK::parseKeySet($jwks));
        if (($claims['iss'] ?? null) !== 'https://appleid.apple.com') {
            throw new RuntimeException('Invalid Apple identity issuer.');
        }

        $audiences = $allowedAudiences ?: array_values(array_filter([
            config('services.apple_sign_in.client_id'),
            config('services.apple_sign_in.native_client_id'),
        ]));
        $tokenAudiences = is_array($claims['aud'] ?? null) ? $claims['aud'] : [($claims['aud'] ?? null)];
        if (! array_intersect($audiences, $tokenAudiences)) {
            throw new RuntimeException('Apple identity token is not intended for this application.');
        }

        if ($expectedNonce !== null && ! hash_equals($expectedNonce, (string) ($claims['nonce'] ?? ''))) {
            throw new RuntimeException('Apple sign-in nonce is invalid.');
        }

        if (empty($claims['sub'])) throw new RuntimeException('Apple identity token does not contain a user identifier.');
        return $claims;
    }

    public function clientSecret(): string
    {
        $this->assertConfigured();
        $privateKey = $this->privateKey();
        $now = time();

        return JWT::encode([
            'iss' => config('services.apple_sign_in.team_id'),
            'iat' => $now,
            'exp' => $now + 300,
            'aud' => 'https://appleid.apple.com',
            'sub' => config('services.apple_sign_in.client_id'),
        ], $privateKey, 'ES256', (string) config('services.apple_sign_in.key_id'));
    }

    public function newNonce(): string
    {
        return Str::random(48);
    }

    private function privateKey(): string
    {
        $configured = (string) config('services.apple_sign_in.private_key', '');
        if ($configured !== '') return str_replace('\\n', "\n", $configured);

        $path = (string) config('services.apple_sign_in.private_key_path', '');
        if ($path !== '' && is_file($path)) return (string) file_get_contents($path);
        throw new RuntimeException('Apple Sign in private key is not configured.');
    }

    private function assertConfigured(): void
    {
        if (! $this->enabled()) throw new RuntimeException('Sign in with Apple is not enabled.');
        foreach (['client_id', 'team_id', 'key_id', 'redirect'] as $key) {
            if (! config('services.apple_sign_in.'.$key)) throw new RuntimeException('Apple Sign in configuration is incomplete.');
        }
    }
}
