<?php

namespace App\Services\AppleIap;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AppStoreServerApiClient
{
    public function transaction(string $transactionId): array
    {
        $mode = config('payments.apple_iap.environment', 'auto');
        $environments = $mode === 'auto' ? ['production', 'sandbox'] : [$mode];
        $last = null;
        foreach ($environments as $environment) {
            $base = $environment === 'sandbox'
                ? 'https://api.storekit-sandbox.itunes.apple.com'
                : 'https://api.storekit.itunes.apple.com';
            $response = Http::acceptJson()->withToken($this->bearerToken())
                ->timeout(15)->get($base.'/inApps/v1/transactions/'.rawurlencode($transactionId));
            if ($response->successful()) {
                $signed = (string) $response->json('signedTransactionInfo');
                if ($signed === '') throw new RuntimeException('Apple returned an empty signed transaction.');
                $payload = $this->decodePayload($signed);
                $payload['_verified_via'] = 'app_store_server_api';
                $payload['_api_environment'] = $environment;
                return $payload;
            }
            $last = $response;
            if ($response->status() !== 404) break;
        }
        throw new RuntimeException('Apple transaction verification failed: '.($last?->body() ?: 'unknown response'));
    }

    public function decodeSignedPayload(string $jws): array
    {
        return $this->decodePayload($jws);
    }

    private function bearerToken(): string
    {
        $issuer = (string) config('payments.apple_iap.issuer_id');
        $keyId = (string) config('payments.apple_iap.key_id');
        $bundleId = (string) config('payments.apple_iap.bundle_id');
        $privateKeyPath = (string) config('payments.apple_iap.private_key_path');
        if ($issuer === '' || $keyId === '' || $bundleId === '' || $privateKeyPath === '' || ! is_file($privateKeyPath)) {
            throw new RuntimeException('Apple IAP server credentials are incomplete.');
        }
        $now = time();
        $header = ['alg' => 'ES256', 'kid' => $keyId, 'typ' => 'JWT'];
        $payload = ['iss' => $issuer, 'iat' => $now, 'exp' => $now + 300, 'aud' => 'appstoreconnect-v1', 'bid' => $bundleId];
        $unsigned = $this->b64(json_encode($header, JSON_UNESCAPED_SLASHES)).'.'.$this->b64(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $key = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if (! $key || ! openssl_sign($unsigned, $derSignature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign App Store Server API token.');
        }
        return $unsigned.'.'.$this->b64($this->derToJose($derSignature, 64));
    }

    private function decodePayload(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) throw new RuntimeException('Invalid Apple JWS payload.');
        $json = base64_decode(strtr($parts[1], '-_', '+/'));
        $data = json_decode($json ?: '', true);
        if (! is_array($data)) throw new RuntimeException('Unable to decode Apple JWS payload.');
        return $data;
    }

    private function b64(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }

    private function derToJose(string $der, int $length): string
    {
        $offset = 2;
        if ((ord($der[1]) & 0x80) !== 0) $offset = 2 + (ord($der[1]) & 0x7f);
        if (ord($der[$offset]) !== 0x02) throw new RuntimeException('Invalid ECDSA signature.');
        $rLength = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLength);
        $offset += 2 + $rLength;
        if (ord($der[$offset]) !== 0x02) throw new RuntimeException('Invalid ECDSA signature.');
        $sLength = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLength);
        $partLength = intdiv($length, 2);
        $r = str_pad(ltrim($r, "\0"), $partLength, "\0", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\0"), $partLength, "\0", STR_PAD_LEFT);
        return $r.$s;
    }
}
