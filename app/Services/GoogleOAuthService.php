<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthService
{
    private $serviceAccountPath;
    private $accessToken;
    private $tokenExpiresAt;

    public function __construct()
    {
        $this->serviceAccountPath = storage_path('app/google-service-account.json');
    }

    public function getAccessToken(): string
    {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        // Get new token
        return $this->requestNewToken();
    }

    private function requestNewToken(): string
    {
        try {
            if (!file_exists($this->serviceAccountPath)) {
                throw new \Exception('Google service account JSON file not found at: ' . $this->serviceAccountPath);
            }

            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
            
            if (!$serviceAccount) {
                throw new \Exception('Invalid service account JSON file');
            }

            // Create JWT token
            $jwt = $this->createJWT($serviceAccount);
            
            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get OAuth2 token: ' . $response->body());
            }

            $tokenData = $response->json();
            $this->accessToken = $tokenData['access_token'];
            $this->tokenExpiresAt = time() + $tokenData['expires_in'] - 60;

            Log::info('OAuth2 access token obtained successfully');
            return $this->accessToken;

        } catch (\Exception $e) {
            Log::error('OAuth2 token request failed', ['error' => $e->getMessage()]);
            throw new \Exception('OAuth2 authentication failed: ' . $e->getMessage());
        }
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
            'iat' => $now
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        $signatureData = $headerEncoded . '.' . $payloadEncoded;
        
        openssl_sign($signatureData, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
        
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
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
