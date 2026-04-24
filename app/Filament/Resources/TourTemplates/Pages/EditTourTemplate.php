<?php

namespace App\Filament\Resources\TourTemplates\Pages;

use App\Filament\Resources\TourTemplates\TourTemplateResource;
use App\Models\FlightPath;
use App\Services\Flights\FlightPathGenerator;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTourTemplate extends EditRecord
{
    protected static string $resource = TourTemplateResource::class;

    protected function afterSave(): void
    {
        $this->record->update([
            'total_nights' => $this->record->stays()->sum('nights'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFlights')
                ->label('Generate Flights')
                ->icon(Heroicon::OutlinedBolt)
                ->color('success')
                ->form([
                    Section::make('Departure Dates')
                        ->description('Generate one FlightPath per date using the shared FlightPathGenerator (same logic as the table action).')
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->live(),
                            TextInput::make('interval_days')
                                ->label('Interval (days)')
                                ->numeric()
                                ->default(7)
                                ->required()
                                ->helperText('7 = weekly, 14 = bi-weekly')
                                ->live(),
                            TextInput::make('count')
                                ->label('Count')
                                ->numeric()
                                ->default(12)
                                ->required()
                                ->minValue(1)
                                ->maxValue(52)
                                ->live(),
                            Toggle::make('replace_existing')
                                ->label('Replace existing FlightPaths')
                                ->helperText('If on, every FlightPath already attached to this template is deleted before new ones are built. Turn on after editing legs/airlines to make sure stale combos disappear.')
                                ->default(false)
                                ->columnSpanFull(),
                            Placeholder::make('preview')
                                ->label('Dates to generate')
                                ->content(function (Get $get) {
                                    $start = $get('start_date');
                                    $interval = (int) ($get('interval_days') ?: 7);
                                    $count = (int) ($get('count') ?: 1);
                                    if (! $start || $count < 1) return '—';
                                    $dates = [];
                                    for ($i = 0; $i < min($count, 52); $i++) {
                                        $d = strtotime($start . ' +' . ($i * $interval) . ' days');
                                        $dates[] = date('d M Y (D)', $d);
                                    }
                                    return implode(', ', $dates);
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ])
                ->action(function (array $data) {
                    $this->runGeneratorForDates($data);
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * Run FlightPathGenerator for an explicit list of base dates.
     *
     * This page used to carry a ~300-line bespoke generator that read the
     * legacy tour_template_legs.airline_id column and ignored the
     * tour_template_leg_airlines pivot — so ops could select "Qanot Sharq"
     * in the multi-select and still get Centrum-flavoured FlightPaths.
     * Everything now delegates to the shared FlightPathGenerator service,
     * which is the same code path the table action and CLI command use.
     */
    protected function runGeneratorForDates(array $data): void
    {
        $template = $this->record->loadMissing(['legs.airlines', 'stays']);

        if ($template->legs->isEmpty()) {
            Notification::make()->danger()->title('No flight legs defined. Add legs to the template first.')->send();
            return;
        }

        $startDate = $data['start_date'] ?? null;
        $interval = (int) ($data['interval_days'] ?? 7);
        $count = (int) ($data['count'] ?? 1);
        $replaceExisting = (bool) ($data['replace_existing'] ?? false);

        if (! $startDate || $count < 1) {
            Notification::make()->danger()->title('Set a start date and count.')->send();
            return;
        }

        if ($replaceExisting) {
            $deleted = FlightPath::where('tour_template_id', $template->id)->count();
            FlightPath::where('tour_template_id', $template->id)->delete();
            if ($deleted > 0) {
                Notification::make()->title("Cleared {$deleted} existing FlightPath(s) before regenerating.")->send();
            }
        }

        $generator = app(FlightPathGenerator::class);
        $created = 0;
        $skipped = 0;
        $reasons = [];

        for ($i = 0; $i < $count; $i++) {
            $baseDate = CarbonImmutable::parse($startDate)->addDays($i * $interval);
            $result = $generator->generate($template, $baseDate);
            $created += $result['created'];
            $skipped += $result['skipped'];
            if (! empty($result['reason']) && $result['created'] === 0 && $result['skipped'] === 0) {
                $reasons[$baseDate->toDateString()] = $result['reason'];
            }
        }

        $parts = ["Created {$created} FlightPath(s)."];
        if ($skipped > 0) {
            $parts[] = "Skipped {$skipped} (already existed).";
        }
        if (! empty($reasons)) {
            $sample = array_slice($reasons, 0, 3, true);
            $rendered = [];
            foreach ($sample as $date => $reason) {
                $rendered[] = "{$date}: {$reason}";
            }
            $parts[] = 'No combos on: ' . implode(' · ', $rendered);
            if (count($reasons) > 3) {
                $parts[] = '(+' . (count($reasons) - 3) . ' more)';
            }
        }

        $notification = $created > 0
            ? Notification::make()->success()
            : Notification::make()->warning();
        $notification->title(implode(' ', $parts))->send();
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\TemplateFlightPathsTable::make(['record' => $this->getRecord()]),
        ];
    }
}
