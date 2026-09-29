<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function configured(): bool
    {
        if (! config('firebase.enabled')) return false;
        $credentials = $this->credentials(false);
        return filled($this->projectId($credentials))
            && filled($credentials['client_email'] ?? null)
            && filled($credentials['private_key'] ?? null);
    }

    /** @return array<string,mixed> */
    public function publicWebConfig(): array
    {
        $web = config('firebase.web', []);
        $configured = $this->configured()
            && filled($web['api_key'] ?? null)
            && filled($web['project_id'] ?? null)
            && filled($web['messaging_sender_id'] ?? null)
            && filled($web['app_id'] ?? null)
            && filled($web['vapid_key'] ?? null);

        return [
            'enabled' => $configured,
            'firebase_config' => [
                'apiKey' => $web['api_key'] ?? null,
                'authDomain' => $web['auth_domain'] ?? null,
                'projectId' => $web['project_id'] ?? null,
                'storageBucket' => $web['storage_bucket'] ?? null,
                'messagingSenderId' => $web['messaging_sender_id'] ?? null,
                'appId' => $web['app_id'] ?? null,
            ],
            'vapid_key' => $web['vapid_key'] ?? null,
        ];
    }

    /** @return array{success:bool,status:int,unregistered:bool,message_id:?string,error:?string} */
    public function send(string $token, array $data): array
    {
        if (! $this->configured()) throw new RuntimeException('Firebase Cloud Messaging is not configured.');

        $credentials = $this->credentials();
        $projectId = $this->projectId($credentials);
        $payload = [];
        foreach ($data as $key => $value) {
            if ($value === null) continue;
            $payload[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        $response = Http::withToken($this->accessToken($credentials))
            ->acceptJson()
            ->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'data' => $payload,
                    'webpush' => [
                        'headers' => ['Urgency' => 'high'],
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => ['apns-priority' => '10'],
                        'payload' => ['aps' => ['content-available' => 1]],
                    ],
                ],
            ]);

        $json = $response->json() ?: [];
        $errorCode = null;
        foreach ((array) data_get($json, 'error.details', []) as $detail) {
            if (is_array($detail) && isset($detail['errorCode'])) {
                $errorCode = (string) $detail['errorCode'];
                break;
            }
        }

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'unregistered' => in_array($errorCode, ['UNREGISTERED', 'SENDER_ID_MISMATCH'], true),
            'message_id' => $response->successful() ? (string) ($json['name'] ?? '') ?: null : null,
            'error' => $response->successful() ? null : (string) (data_get($json, 'error.message') ?: 'FCM request failed.'),
        ];
    }

    /** @return array<string,mixed> */
    private function credentials(bool $throw = true): array
    {
        $json = trim((string) config('firebase.service_account_json'));
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) return $decoded;
        }

        $path = trim((string) config('firebase.service_account_path'));
        if ($path !== '') {
            $resolved = str_starts_with($path, '/') ? $path : base_path($path);
            if (is_file($resolved)) {
                $decoded = json_decode((string) file_get_contents($resolved), true);
                if (is_array($decoded)) return $decoded;
            }
        }

        $clientEmail = trim((string) config('firebase.client_email'));
        $privateKey = str_replace('\\n', "\n", (string) config('firebase.private_key'));
        if ($clientEmail !== '' && trim($privateKey) !== '') {
            return [
                'client_email' => $clientEmail,
                'private_key' => $privateKey,
                'project_id' => config('firebase.project_id'),
                'token_uri' => config('firebase.token_uri'),
            ];
        }

        if ($throw) throw new RuntimeException('Firebase service-account credentials are missing.');
        return [];
    }

    private function projectId(array $credentials): string
    {
        return trim((string) (config('firebase.project_id') ?: ($credentials['project_id'] ?? '')));
    }

    private function accessToken(array $credentials): string
    {
        $cacheKey = 'firebase:fcm-access-token:'.sha1((string) ($credentials['client_email'] ?? 'default'));
        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $tokenUri = (string) ($credentials['token_uri'] ?? config('firebase.token_uri'));
            $assertion = $this->jwt(
                ['alg' => 'RS256', 'typ' => 'JWT'],
                [
                    'iss' => (string) $credentials['client_email'],
                    'scope' => self::SCOPE,
                    'aud' => $tokenUri,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ],
                (string) $credentials['private_key'],
            );

            $response = Http::asForm()->acceptJson()->timeout(15)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Unable to obtain Firebase access token: '.($response->json('error_description') ?: $response->body()));
            }
            return (string) $response->json('access_token');
        });
    }

    private function jwt(array $header, array $claims, string $privateKey): string
    {
        $segments = [$this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES)), $this->base64Url(json_encode($claims, JSON_UNESCAPED_SLASHES))];
        $input = implode('.', $segments);
        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if (! $key || ! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Firebase private key could not sign the OAuth assertion.');
        }
        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
