<?php

namespace App\Services\Amadeus;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmadeusAuthService
{
    public function getAccessToken(): ?string
    {
        $token = DB::table('amadeus_tokens')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($token) {
            return $token->access_token;
        }

        return $this->requestNewToken();
    }

    private function requestNewToken(): ?string
    {
        $clientId = config('amadeus.client_id');
        $clientSecret = config('amadeus.client_secret');
        $baseUrl = config('amadeus.base_url');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Amadeus credentials not configured');
            return null;
        }

        try {
            $response = Http::asForm()->post("{$baseUrl}/v1/security/oauth2/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (! $response->successful()) {
                Log::error('Amadeus auth failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $expiresIn = $data['expires_in'] ?? 1799;

            DB::table('amadeus_tokens')->insert([
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'Bearer',
                'expires_in' => $expiresIn,
                'expires_at' => now()->addSeconds($expiresIn - 60),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('Amadeus auth exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
