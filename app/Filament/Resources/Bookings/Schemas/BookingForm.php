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

                                $html = '';
                                foreach ($tourists as $i => $t) {
                                    $num = $i + 1;
                                    $html .= "<div style='border:1px solid #d1d5db;border-radius:6px;padding:10px;margin-bottom:10px;background:#fafbfc;'>";
                                    $html .= "<div style='font-weight:700;margin-bottom:6px;color:#1a5c2e;'>Tourist {$num}: " . e($t->title) . ' ' . e($t->last_name) . ' ' . e($t->first_name) . "</div>";
                                    $html .= "<table style='font-size:12px;'>";
                                    $html .= "<tr><td style='color:#666;padding:2px 10px 2px 0;'>Sex:</td><td>" . e($t->gender ?? '—') . "</td>";
                                    $html .= "<td style='color:#666;padding:2px 10px 2px 20px;'>Birth date:</td><td>" . ($t->birth_date?->format('d.m.Y') ?? '—') . "</td></tr>";
                                    $html .= "<tr><td style='color:#666;padding:2px 10px 2px 0;'>Birth country:</td><td>" . e($t->birth_country ?? '—') . "</td>";
                                    $html .= "<td style='color:#666;padding:2px 10px 2px 20px;'>Nationality:</td><td>" . e($t->nationality ?? '—') . "</td></tr>";
                                    $html .= "<tr><td style='color:#666;padding:2px 10px 2px 0;'>Document type:</td><td>" . e($t->document_type ?? '—') . "</td>";
                                    $html .= "<td style='color:#666;padding:2px 10px 2px 20px;'>Series:</td><td>" . e($t->passport_series ?? '—') . "</td></tr>";
                                    $html .= "<tr><td style='color:#666;padding:2px 10px 2px 0;'>Number:</td><td><strong>" . e($t->passport_number ?? '—') . "</strong></td>";
                                    $html .= "<td style='color:#666;padding:2px 10px 2px 20px;'>Valid to:</td><td>" . ($t->passport_expiry?->format('d.m.Y') ?? '—') . "</td></tr>";
                                    $html .= "<tr><td style='color:#666;padding:2px 10px 2px 0;'>Issued:</td><td>" . ($t->passport_issued?->format('d.m.Y') ?? '—') . "</td>";
                                    $html .= "<td style='color:#666;padding:2px 10px 2px 20px;'>Issued by:</td><td>" . e($t->passport_issued_by ?? '—') . "</td></tr>";
                                    $html .= "</table></div>";
                                }
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
