<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tourist;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NeoInsurance Travel-Risk API integration.
 *
 * Endpoints (travel-risk-neo):
 *   GET  /api/travel-risk-neo/get-data    — risk options (accident, luggage, etc.)
 *   POST /api/travel-risk-neo/calculator  — calculate premium for selected risks
 *   POST /api/travel-risk-neo/save        — create policy, returns order_id + payment URLs
 *
 * Endpoints (travel-neo):
 *   GET  /api/travel-neo/get-data         — countries, tariffs, purposes, exchange rates
 */
class NeoInsuranceService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private bool $configured;

    private const DEFAULT_PROGRAM_ID = 3; // Premium
    private const TRAVEL_DEFAULTS = [
        'purpose_id' => 1,
        'kop_martali' => false,
        'is_family' => false,
        'has_covid' => false,
    ];

    /** Available risk types for the booking page. */
    public const RISK_TYPES = [
        'accident' => ['name_en' => 'Accident Insurance', 'name_ru' => 'Страхование от несчастного случая'],
        'luggage' => ['name_en' => 'Luggage Insurance', 'name_ru' => 'Страхование багажа'],
        'cancel_travel' => ['name_en' => 'Trip Cancellation', 'name_ru' => 'Отмена поездки'],
        'person_respon' => ['name_en' => 'Personal Liability', 'name_ru' => 'Гражданская ответственность'],
        'delay_travel' => ['name_en' => 'Travel Delay', 'name_ru' => 'Задержка рейса'],
    ];

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
     * Create insurance policy for a booking via travel-neo/save-polis.
     *
     * Flow:
     * 1. Call /api/travel-neo/calculator-total → get prem_uzs
     * 2. Call /api/travel-neo/save-polis with summa_all = prem_uzs
     *
     * Returns {order_id, contract_id, url, payme_url} on success, null on failure.
     */
    public function createPolicyForBooking(Booking $booking): ?array
    {
        $booking->loadMissing(['tourists', 'order.user', 'bookable']);

        $risks = $booking->insurance_risks;
        if (empty($risks) || ! is_array($risks)) {
            return null;
        }

        $riskFlags = self::buildRiskFlags($risks);
        if (array_sum($riskFlags) === 0) {
            return null;
        }

        $dates = $this->resolveTravelDates($booking);
        if (! $dates) {
            return null;
        }

        $countryCodes = $this->resolveCountryCodes($booking);
        if (empty($countryCodes)) {
            return null;
        }

        $firstTourist = $booking->tourists->first();
        if (! $firstTourist) {
            return null;
        }

        // Step 1: Calculate premium
        $travelerBirthDates = $booking->tourists->map(
            fn (Tourist $t) => $t->birth_date?->format('d-m-Y') ?? '01-01-1990'
        )->all();

        $calcResponse = $this->post('/api/travel-neo/calculator-total', array_merge(self::TRAVEL_DEFAULTS, [
            'begin_date' => $dates['begin'],
            'end_date' => $dates['end'],
            'countries' => $countryCodes,
            'travelers' => $travelerBirthDates,
            'risklar' => $riskFlags,
        ]));

        if (! $calcResponse || ! ($calcResponse['result'] ?? false)) {
            Log::error('NeoInsurance calculator failed', ['booking_id' => $booking->id]);
            return null;
        }

        $programs = $calcResponse['response'] ?? [];
        $program = collect($programs)->firstWhere('program_id', self::DEFAULT_PROGRAM_ID) ?? collect($programs)->last();
        if (! $program) {
            return null;
        }

        $sugurtalovchi = $this->buildPolicyholder($firstTourist, $booking->order->user?->phone);
        $travelers = $booking->tourists->map(fn (Tourist $t) => $this->buildTraveler($t))->values()->all();

        $response = $this->post('/api/travel-neo/save-polis', array_merge(self::TRAVEL_DEFAULTS, [
            'begin_date' => $dates['begin'],
            'end_date' => $dates['end'],
            'summa_all' => $program['prem_uzs'],
            'countries' => $countryCodes,
            'program_id' => (string) $program['program_id'],
            'sugurtalovchi' => $sugurtalovchi,
            'travelers' => $travelers,
            'risklar' => $riskFlags,
        ]));

        if (! $response || ! ($response['result'] ?? false)) {
            Log::error('NeoInsurance save-polis failed', [
                'booking_id' => $booking->id,
                'message' => $response['message'] ?? 'Unknown error',
            ]);
            return null;
        }

        $result = $response['response'] ?? [];

        Log::info('NeoInsurance policy created', [
            'booking_id' => $booking->id,
            'neo_order_id' => $result['order_id'] ?? null,
        ]);

        return $result ?: null;
    }

    /**
     * Check policy payment status via travel-neo/checkPolis.
     *
     * Response when paid:
     *   {error: false, check: true, url: "...", polis_seria: "ENT", polis_number: "0000112"}
     * Response when not paid:
     *   {result: true, response: {error_code: 1, message: "To'lov qilinmagan hali"}}
     */
    public function checkPolicyStatus(int $neoOrderId): ?array
    {
        return $this->post('/api/travel-neo/checkPolis', ['order_id' => $neoOrderId]);
    }

    /**
     * Determine if a policy is paid based on checkPolis response.
     */
    public function isPolicyPaid(array $checkResponse): bool
    {
        // Paid response has check: true and polis_number
        if (! empty($checkResponse['check']) && ! empty($checkResponse['polis_number'])) {
            return true;
        }
        // Unpaid response has response.error_code === 1
        return false;
    }

    private function resolveTravelDates(Booking $booking): ?array
    {
        $bookable = $booking->bookable;

        if ($bookable instanceof \App\Models\FlightPath) {
            return [
                'begin' => $bookable->departure_date->format('d-m-Y'),
                'end' => $bookable->departure_date->copy()->addDays($bookable->nights)->format('d-m-Y'),
            ];
        }

        if ($bookable instanceof \App\Models\Hotel) {
            $checkIn = $booking->date;
            if (! $checkIn) {
                return null;
            }
            $nights = $booking->hotels->first()?->pivot->nights ?? 7;
            return [
                'begin' => $checkIn->format('d-m-Y'),
                'end' => $checkIn->copy()->addDays($nights)->format('d-m-Y'),
            ];
        }

        return null;
    }

    private function resolveCountryCodes(Booking $booking): array
    {
        $bookable = $booking->bookable;

        if ($bookable instanceof \App\Models\FlightPath) {
            $bookable->loadMissing('stays.city.country');
            return $bookable->stays
                ->map(fn ($s) => $s->city?->country?->code)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if ($bookable instanceof \App\Models\Hotel) {
            $bookable->loadMissing('city.country');
            $code = $bookable->city?->country?->code;
            return $code ? [$code] : [];
        }

        return [];
    }

    private function buildPolicyholder(Tourist $tourist, ?string $phone): array
    {
        return [
            'type' => 0, // UZ citizen
            'passportSeries' => $tourist->passport_series ?? substr($tourist->passport_number ?? 'AA', 0, 2),
            'passportNumber' => $tourist->passport_number ?? '0000000',
            'birthday' => $tourist->birth_date?->format('d-m-Y') ?? '01-01-1990',
            'phone' => $phone ?? '998919777735',
            'pinfl' => '11111111111111',
            'last_name' => strtoupper($tourist->last_name),
            'first_name' => strtoupper($tourist->first_name),
            'middle_name' => strtoupper($tourist->middle_name ?? $tourist->first_name),
            'address' => 'UZB',
            'gender' => $tourist->gender === 'female' ? 2 : 1,
        ];
    }

    private function buildTraveler(Tourist $tourist): array
    {
        return [
            'passportSeries' => $tourist->passport_series ?? substr($tourist->passport_number ?? 'AA', 0, 2),
            'passportNumber' => $tourist->passport_number ?? '0000000',
            'birthday' => $tourist->birth_date?->format('d-m-Y') ?? '01-01-1990',
            'pinfl' => '11111111111111',
            'last_name' => strtoupper($tourist->last_name),
            'first_name' => strtoupper($tourist->first_name),
            'gender' => $tourist->gender === 'female' ? 2 : 1,
        ];
    }

    /** Convert selected risk names to API flags array. */
    public static function buildRiskFlags(array $selectedRisks): array
    {
        $flags = [];
        foreach (array_keys(self::RISK_TYPES) as $key) {
            $flags[$key] = in_array($key, $selectedRisks) ? 1 : 0;
        }
        return $flags;
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
