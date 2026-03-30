<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking')
                    ->schema([
                        Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('pending'),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->required(),
                        DatePicker::make('date')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Tour / Flight Path')
                    ->schema([
                        Placeholder::make('tour_info')
                            ->label('')
                            ->content(function (?Booking $record) {
                                if (! $record) {
                                    return 'Save booking first.';
                                }

                                $bookable = $record->bookable;
                                if (! $bookable) {
                                    return 'No linked tour.';
                                }

                                if ($bookable instanceof \App\Models\FlightPath) {
                                    $bookable->load('legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport', 'stays.city');
                                    $legs = $bookable->legs->sortBy('leg_order')->map(fn ($l) =>
                                        ($l->flight?->fromAirport?->code ?? '?') . '→' . ($l->flight?->toAirport?->code ?? '?') .
                                        ' ' . ($l->flight?->airline?->code ?? '') . $l->flight?->flight_number .
                                        ' ' . ($l->flight?->departure_date?->format('d.m') ?? '') .
                                        ' $' . ($l->flight?->price_adult ?? 0)
                                    )->implode(' | ');

                                    $stays = $bookable->stays->sortBy('stay_order')->map(fn ($s) =>
                                        ($s->city?->name_en ?? '?') . ' ' . $s->nights . 'n'
                                    )->implode(' → ');

                                    return new HtmlString(
                                        "<strong>{$bookable->route_name}</strong> — {$bookable->departure_date->format('d.m.Y')}<br>" .
                                        "Flights: {$legs}<br>" .
                                        "Stays: {$stays}<br>" .
                                        "Flight total: <strong>\${$bookable->flight_total}</strong>"
                                    );
                                }

                                return "Tour #{$bookable->id}";
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Tourists')
                    ->schema([
                        Placeholder::make('tourists_info')
                            ->label('')
                            ->content(function (?Booking $record) {
                                if (! $record) {
                                    return 'Save booking first.';
                                }

                                $tourists = $record->tourists;
                                if ($tourists->isEmpty()) {
                                    return 'No tourists.';
                                }

                                $html = '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
                                $html .= '<tr style="background:#f1f5f9;"><th style="border:1px solid #ddd;padding:4px 8px;">Title</th><th style="border:1px solid #ddd;padding:4px 8px;">Name</th><th style="border:1px solid #ddd;padding:4px 8px;">Birth Date</th><th style="border:1px solid #ddd;padding:4px 8px;">Nationality</th><th style="border:1px solid #ddd;padding:4px 8px;">Passport</th><th style="border:1px solid #ddd;padding:4px 8px;">Expiry</th></tr>';

                                foreach ($tourists as $t) {
                                    $html .= '<tr>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;">' . e($t->title) . '</td>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;font-weight:600;">' . e($t->last_name) . ' ' . e($t->first_name) . '</td>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;">' . ($t->birth_date?->format('d.m.Y') ?? '—') . '</td>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;">' . e($t->nationality ?? '—') . '</td>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;">' . e(($t->document_series ?? '') . ' ' . ($t->passport_number ?? '—')) . '</td>';
                                    $html .= '<td style="border:1px solid #ddd;padding:4px 8px;">' . ($t->passport_expiry?->format('d.m.Y') ?? '—') . '</td>';
                                    $html .= '</tr>';
                                }

                                $html .= '</table>';
                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Contact & Notes')
                    ->schema([
                        Placeholder::make('order_info')
                            ->label('')
                            ->content(function (?Booking $record) {
                                if (! $record?->order) {
                                    return '—';
                                }
                                $o = $record->order;
                                $lines = [];
                                if ($o->notes) {
                                    $lines[] = '<strong>Notes:</strong> ' . e($o->notes);
                                }
                                $user = $o->user;
                                if ($user) {
                                    $lines[] = '<strong>Contact:</strong> ' . e($user->name) . ' — ' . e($user->email) . ' ' . e($user->phone ?? '');
                                }
                                $agency = $o->agency;
                                if ($agency) {
                                    $lines[] = '<strong>Agency:</strong> ' . e($agency->name);
                                }
                                return new HtmlString(implode('<br>', $lines) ?: '—');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
