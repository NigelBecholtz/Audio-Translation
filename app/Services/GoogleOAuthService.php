<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthService
{
    private $serviceAccountPath;

    private $cacheKey = 'gemini_oauth_access_token';

    public function __construct()
    {
        $this->serviceAccountPath = config('services.google_cloud.credentials_path')
            ?: storage_path('app/google-service-account.json');
    }

    public function getAccessToken(): string
    {
        // Try to get cached token first
        $cachedToken = Cache::get($this->cacheKey);

        if ($cachedToken) {
            Log::debug('Using cached OAuth2 access token');

            return $cachedToken;
        }

        // Get new token and cache it
        return $this->requestNewToken();
    }

    /**
     * Google Cloud project: the service account's project_id, else GOOGLE_CLOUD_PROJECT_ID.
     */
    public function projectId(): ?string
    {
        $serviceAccount = $this->readServiceAccount();

        return ($serviceAccount['project_id'] ?? null) ?: (config('services.google_cloud.project_id') ?: null);
    }

    private function requestNewToken(): string
    {
        try {
            if (! file_exists($this->serviceAccountPath)) {
                throw new \Exception('Google service account JSON file not found at: '.$this->serviceAccountPath);
            }

            $serviceAccount = $this->readServiceAccount();

            if (! $serviceAccount) {
                throw new \Exception('Invalid service account JSON file');
            }

            // Create JWT token
            $jwt = $this->createJWT($serviceAccount);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Failed to get OAuth2 token: '.$response->body());
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            $expiresIn = $tokenData['expires_in'];

            // Cache the token for slightly less than its expiration time (minus 60 seconds for safety)
            $cacheMinutes = floor(($expiresIn - 60) / 60);
            Cache::put($this->cacheKey, $accessToken, now()->addMinutes($cacheMinutes));

            Log::info('OAuth2 access token obtained and cached', [
                'expires_in' => $expiresIn,
                'cached_for_minutes' => $cacheMinutes,
            ]);

            return $accessToken;

        } catch (\Exception $e) {
            Log::error('OAuth2 token request failed', ['error' => $e->getMessage()]);
            throw new \Exception('OAuth2 authentication failed: '.$e->getMessage());
        }
    }

    private function readServiceAccount(): ?array
    {
        if (! is_file($this->serviceAccountPath)) {
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

        return is_array($serviceAccount) ? $serviceAccount : null;
    }

    private function createJWT(array $serviceAccount): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = '';
        $signatureData = $headerEncoded.'.'.$payloadEncoded;

        if (! openssl_sign($signatureData, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \Exception('Could not sign the OAuth2 JWT with the service account private key');
        }

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded.'.'.$payloadEncoded.'.'.$signatureEncoded;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function isConfigured(): bool
    {
        return file_exists($this->serviceAccountPath);
    }
}
