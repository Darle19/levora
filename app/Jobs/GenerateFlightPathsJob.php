<?php

namespace App\Jobs;

use App\Models\TourTemplate;
use App\Services\Flights\FlightPathGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Run FlightPathGenerator for one TourTemplate off the request cycle.
 *
 * RapidAPI-sourced legs make generation slow (one HTTP call per leg x date x
 * airline) — an admin clicking "Generate Paths" should not block the UI for
 * a minute. This job writes progress back to the template so the Filament
 * table can poll and show status.
 */
class GenerateFlightPathsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Don't retry — RapidAPI calls are metered and most failures are quota-related. */
    public int $tries = 1;

    /** Large templates × many dates × live API. */
    public int $timeout = 900;

    public function __construct(
        public TourTemplate $template,
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    public function handle(FlightPathGenerator $generator): void
    {
        $startedAt = now();

        $this->template->update([
            'generation_status' => 'running',
            'generation_summary' => [
                'started_at' => $startedAt->toIso8601String(),
                'from' => $this->from,
                'to' => $this->to,
            ],
        ]);

        $from = $this->from ? CarbonImmutable::parse($this->from) : CarbonImmutable::today();
        $to = $this->to ? CarbonImmutable::parse($this->to) : CarbonImmutable::today()->addDays(90);

        $result = $generator->generateForWindow($this->template, $from, $to);

        $this->template->update([
            'generation_status' => 'done',
            'generation_summary' => array_merge($result, [
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'duration_seconds' => $startedAt->diffInSeconds(now()),
            ]),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateFlightPathsJob failed', [
            'template_id' => $this->template->id,
            'error' => $e->getMessage(),
        ]);

        $this->template->refresh()->update([
            'generation_status' => 'failed',
            'generation_summary' => [
                'finished_at' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ],
        ]);
    }
}
