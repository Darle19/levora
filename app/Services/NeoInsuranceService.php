<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tourist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NeoInsurance Travel API integration.
 *
 * Endpoints (travel-neo):
 *   GET  /api/travel-neo/get-data         — countries, tariffs, purposes, exchange rates
 *   POST /api/travel-neo/calculator-total  — calculate premium for trip
 *   POST /api/travel-neo/save-polis        — create policy, returns payment URLs
 *   POST /api/travel-neo/checkPolis        — check policy status
 */
class NeoInsuranceService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private bool $configured;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.neoinsurance.base_url') ?? '', '/');
        $this->username = config('services.neoinsurance.username') ?? '';
        $this->password = config('services.neoinsurance.password') ?? '';
        $this->configured = $this->baseUrl !== '' && $this->username !== '' && $this->password !== '';
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Get countries, tariffs, purposes, exchange rates.
     */
    public function getData(): ?array
    {
        if (! $this->configured) {
            return null;
        }

        return Cache::remember('neoinsurance_data', 86400, function () {
            $response = $this->get('/api/travel-neo/get-data');
            return $response['data'] ?? $response;
        });
    }

    /**
     * Calculate insurance premium.
     *
     * @param string $beginDate DD-MM-YYYY
     * @param string $endDate DD-MM-YYYY
     * @param array $countryCodes e.g. ['TR', 'FR']
     * @param array $travelerBirthDates e.g. ['16-12-2001']
     * @param array $risks e.g. ['accident' => 1, 'luggage' => 0, ...]
     * @return array|null Array of programs with prices
     */
    public function calculatePremium(
        string $beginDate,
        string $endDate,
        array $countryCodes,
        array $travelerBirthDates,
        array $risks = [],
    ): ?array {
        $payload = [
            'begin_date' => $beginDate,
            'end_date' => $endDate,
            'countries' => $countryCodes,
            'purpose_id' => 1, // Travel
            'kop_martali' => false,
            'is_family' => false,
            'has_covid' => false,
            'travelers' => $travelerBirthDates,
            'risklar' => array_merge([
                'accident' => 1,
                'luggage' => 0,
                'cancel_travel' => 0,
                'person_respon' => 0,
                'delay_travel' => 0,
            ], $risks),
        ];

        $response = $this->post('/api/travel-neo/calculator-total', $payload);

        if (! $response || ! ($response['result'] ?? false)) {
            return null;
        }

        return $response['response'] ?? null;
    }

    /**
     * Create and save insurance policy. Returns order_id + payment URLs.
     */
    public function savePolicy(array $policyData): ?array
    {
        $response = $this->post('/api/travel-neo/save-polis', $policyData);

        if (! $response || ! ($response['result'] ?? false)) {
            Log::error('NeoInsurance save-polis failed', [
                'message' => $response['message'] ?? 'Unknown error',
            ]);
            return null;
        }

        return $response['response'] ?? null;
    }

    /**
     * Check policy status by order ID.
     */
    public function checkPolicy(int $orderId): ?array
    {
        $response = $this->post('/api/travel-neo/checkPolis', [
            'order_id' => $orderId,
        ]);

        return $response;
    }

    /**
     * Get insurance options formatted for booking page display.
     * Returns array of programs with USD prices.
     */
    public function getInsuranceOptionsForBooking(
        string $departureDate,
        string $returnDate,
        array $countryCodes,
        int $travelerCount = 1,
    ): array {
        // Use a dummy birth date for calculation (adult)
        $travelerDates = array_fill(0, $travelerCount, '01-01-1990');

        $beginDate = date('d-m-Y', strtotime($departureDate));
        $endDate = date('d-m-Y', strtotime($returnDate));

        $programs = $this->calculatePremium($beginDate, $endDate, $countryCodes, $travelerDates);

        if (! $programs || ! is_array($programs)) {
            return [];
        }

        return collect($programs)->map(function ($program) use ($departureDate, $returnDate) {
            return [
                'id' => 'neo_program_' . ($program['program_id'] ?? 0),
                'program_id' => $program['program_id'] ?? 0,
                'name' => $program['program_name'] ?? 'Insurance',
                'price_usd' => (float) ($program['prem_usd'] ?? 0),
                'price_uzs' => (int) ($program['prem_uzs'] ?? 0),
                'currency' => 'USD',
                'is_per_person' => true,
                'period' => $departureDate . ' — ' . $returnDate,
                'source' => 'neoinsurance',
            ];
        })->all();
    }

    // ── HTTP helpers ──

    private function get(string $endpoint): ?array
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(15)
                ->get("{$this->baseUrl}{$endpoint}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("NeoInsurance GET {$endpoint} failed", [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 300),
            ]);
        } catch (\Exception $e) {
            Log::error("NeoInsurance GET {$endpoint} exception", ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function post(string $endpoint, array $data): ?array
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(15)
                ->post("{$this->baseUrl}{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("NeoInsurance POST {$endpoint} failed", [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 300),
                'payload' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error("NeoInsurance POST {$endpoint} exception", ['message' => $e->getMessage()]);
        }

        return null;
    }
}
