<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tourist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NeoInsuranceService
{
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.neoinsurance.base_url', ''), '/');
        $this->username = config('services.neoinsurance.username', '');
        $this->password = config('services.neoinsurance.password', '');
    }

    public function getInsuranceOptions(): ?array
    {
        return Cache::remember('neoinsurance_options', 86400, function () {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/api/travel-risk-neo/get-data");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('NeoInsurance get-data failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('NeoInsurance get-data exception', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }

    public function getCountries(): ?array
    {
        return Cache::remember('neoinsurance_countries', 86400, function () {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/api/accident_one_day-neo/get-country");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('NeoInsurance get-country failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('NeoInsurance get-country exception', ['message' => $e->getMessage()]);
            }

            return null;
        });
    }

    public function calculatePremium(array $risks): ?array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->post("{$this->baseUrl}/api/travel-risk-neo/calculator", [
                    'risklar' => $risks,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NeoInsurance calculator failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('NeoInsurance calculator exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    public function createPolicy(Tourist $tourist, Booking $booking): ?array
    {
        $tour = $booking->bookable;

        if (!$tour) {
            Log::error('NeoInsurance: booking has no bookable tour', ['booking_id' => $booking->id]);
            return null;
        }

        $countryId = $this->resolveCountryId($tour->country);

        $payload = [
            'begin_date' => $tour->date_from->format('d-m-Y'),
            'end_date' => $tour->date_to->format('d-m-Y'),
            'sugurtalovchi' => [
                'type' => 2,
                'passportSeries' => $tourist->passport_series ?? '',
                'passportNumber' => $tourist->passport_number ?? '',
                'birthday' => $tourist->birth_date?->format('d-m-Y') ?? '',
                'phone' => $booking->order->user->phone ?? '',
                'last_name' => $tourist->last_name ?? '',
                'first_name' => $tourist->first_name ?? '',
                'middle_name' => $tourist->middle_name ?? '',
                'address' => '',
                'gender' => $this->mapGender($tourist->gender),
                'country_id' => $countryId,
            ],
            'risklar' => [
                'accident' => 1,
                'luggage' => 0,
                'cancel_travel' => 0,
                'person_respon' => 0,
                'delay_travel' => 0,
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->post("{$this->baseUrl}/api/travel-risk-neo/save", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NeoInsurance save failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'tourist_id' => $tourist->id,
            ]);
        } catch (\Exception $e) {
            Log::error('NeoInsurance save exception', [
                'message' => $e->getMessage(),
                'tourist_id' => $tourist->id,
            ]);
        }

        return null;
    }

    private function mapGender(?string $gender): int
    {
        return match (strtolower($gender ?? '')) {
            'male', 'm' => 1,
            'female', 'f' => 2,
            default => 1,
        };
    }

    private function resolveCountryId(?object $country): int
    {
        if (!$country) {
            return 98; // Uzbekistan default
        }

        // Try to match by country name against cached NeoInsurance countries
        $neoCountries = $this->getCountries();
        if (!$neoCountries || !is_array($neoCountries)) {
            return 98;
        }

        $countryName = $country->name_en ?? $country->attributes['name'] ?? '';

        foreach ($neoCountries as $nc) {
            $names = [$nc['name_en'] ?? '', $nc['name_ru'] ?? '', $nc['name_uz'] ?? ''];
            foreach ($names as $name) {
                if ($name && stripos($name, $countryName) !== false) {
                    return (int) ($nc['id'] ?? 98);
                }
            }
        }

        return 98;
    }
}
