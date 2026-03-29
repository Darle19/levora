<style>
/* Results table — aquatravel style */
.st table.res { border-collapse: collapse; width: 100%; font-family: Tahoma, Arial, sans-serif; font-size: 11px; }
.st table.res thead th { background: #366383; color: #fff; font-size: 11px; font-weight: bold; padding: 4px 5px; border-bottom: 1px solid #888; white-space: nowrap; text-align: left; }
.st table.res thead th.c { text-align: center; }
.st table.res td { padding: 3px 5px; font-size: 11px; vertical-align: middle; }
.st table.res .even td { background: #F6F9FB; border-bottom: 1px solid #8E959A; }
.st table.res .odd td { background: #e3e7ea; border-bottom: 1px solid #8E959A; }
.st table.res .red_row td { background: #f1afaf; border-bottom: 1px solid #cc5050 !important; color: #9f4d4d !important; }
.st table.res tbody tr { cursor: pointer; }
.st table.res tbody tr:hover td { background: #c3d5ff; }
.st table.res .nw { white-space: nowrap; }
.st table.res .c { text-align: center; }

/* Hotel link */
.st .link-hotel { font-weight: bold; color: #007355; text-decoration: none; }
.st .link-hotel:hover { color: #f36f21; text-decoration: underline; }
.st .hotel-loc { font-size: 10px; color: #666; }

/* Availability squares */
.st .hotel_avail { display: inline-block; width: 16px; height: 16px; box-shadow: gray -1px 1px 2px inset; margin: 0 1px; vertical-align: middle; }
.st .avail_Y { background: #4db646; }
.st .avail_F { background: #ffc107; }
.st .avail_R { background: #a9a9a9; }
.st .avail_N { background: #c73d42; }

/* Transport arrows */
.st .transport-block { white-space: nowrap; }
.st .transport-block .name { font-size: 10px; margin-right: 2px; }
.st .fr_place { display: inline-block; height: 14px; width: 21px; min-width: 21px; vertical-align: middle; position: relative; cursor: help; }
.st .fr_place::after { content: ''; display: block; width: 0; height: 0; border-style: solid; position: absolute; top: 1px; }
.st .fr_place_r::after { border-width: 6px 0 6px 10px; }
.st .fr_place_l::after { border-width: 6px 10px 6px 0; left: 5px; }
.st .fr_place_r.N::after { border-color: transparent transparent transparent #c73d42; }
.st .fr_place_r.F::after { border-color: transparent transparent transparent #ffc107; }
.st .fr_place_r.Y::after { border-color: transparent transparent transparent #4db646; }
.st .fr_place_l.N::after { border-color: transparent #c73d42 transparent transparent; }
.st .fr_place_l.F::after { border-color: transparent #ffc107 transparent transparent; }
.st .fr_place_l.Y::after { border-color: transparent #4db646 transparent transparent; }

/* Price */
.st .td_price { font-weight: bold; white-space: nowrap; }
.st .price { font-style: italic; }
.st .price.stop { text-decoration: line-through; color: #9f4d4d; }
.st .price-breakdown { font-size: 9px; color: #888; font-style: normal; font-weight: normal; display: block; }
.st .type_price { font-size: 10px; color: #666; }

/* Book button */
.st .book-btn { display: inline-block; padding: 3px 10px; background: #366383; color: #fff; font-size: 11px; font-weight: bold; border-radius: 2px; text-decoration: none; border: 1px outset #4a7a9a; }
.st .book-btn:hover { background: #2a4f6a; }

/* Route code */
.st .route-code { font-size: 9px; color: #888; font-family: monospace; }

/* No results */
.st .no-results { padding: 40px 20px; text-align: center; font-size: 13px; color: #555; }
</style>

@php
    $pax = max(1, (int) ($currentFilters['adults'] ?? 2));
@endphp

@if(count($results ?? []) > 0)
<table class="res">
    <thead>
        <tr>
            <th></th>
            <th>Departure</th>
            <th>Tour</th>
            <th class="c">Nights</th>
            <th>Hotel</th>
            <th class="c">Avail</th>
            <th>Meal</th>
            <th>Room</th>
            <th></th>
            <th class="c">
                <a href="#" class="sortable-header" data-sort="price" style="color:#fff;text-decoration:none;">Price @if(($sortBy??'')=='price'){{ ($sortDir??'asc')=='asc'?'▲':'▼' }}@endif</a>
            </th>
            <th>Route</th>
            <th class="c">Transport</th>
        </tr>
    </thead>
    <tbody>
        @foreach($results as $i => $result)
            @php
                $fp = $result->flight_path;
                $isStop = $result->min_seats <= 0;
                $rowClass = $isStop ? 'red_row' : ($i % 2 === 0 ? 'even' : 'odd');
                $totalPrice = round($result->price * $pax);

                // Build book URL
                $hotelIds = collect($result->hotels)->pluck('hotel.id')->implode(',');
                $bookUrl = '/book?fp=' . $fp->id . '&h=' . $hotelIds;

                // First leg for departure time
                $firstLeg = $fp->legs->sortBy('leg_order')->first();
                $depTime = $firstLeg?->flight?->departure_time ?? null;

                // Seat status for transport arrows
                $outSeats = $fp->legs->where('direction', 'outbound')->min(fn($l) => $l->flight->available_seats ?? 0);
                $retSeats = $fp->legs->where('direction', 'return')->min(fn($l) => $l->flight->available_seats ?? 0);
                $seatCls = fn($s) => $s > 10 ? 'Y' : ($s > 0 ? 'F' : 'N');
                $seatTip = fn($s) => $s > 10 ? 'seats available' : ($s > 0 ? 'a few seats (' . $s . ')' : 'no seats');

                // Availability squares (4)
                $s = $result->min_seats;
                $sq = $isStop ? ['N','N','N','N'] : ($s > 10 ? ['Y','Y','Y','Y'] : ($s > 5 ? ['Y','Y','F','F'] : ($s > 0 ? ['F','F','R','R'] : ['N','N','N','N'])));

                // Route code
                $routeCode = $fp->legs->map(fn($l) => $l->flight?->fromAirport?->code)->push($fp->legs->last()?->flight?->toAirport?->code)->filter()->unique()->implode('-');
            @endphp
            <tr class="{{ $rowClass }}" onclick="window.location='{{ $bookUrl }}'">
                {{-- Col 1: empty (was btn-group) --}}
                <td style="width:5px;"></td>

                {{-- Col 2: Departure --}}
                <td class="nw">
                    {{ $fp->departure_date->format('d.m.Y, D') }}
                    @if($depTime)
                        <br><span style="color:#366383;">{{ $depTime }}</span>
                    @endif
                </td>

                {{-- Col 3: Tour name --}}
                <td>{{ $fp->route_name }}</td>

                {{-- Col 4: Nights --}}
                <td class="c" style="font-weight:bold;">{{ $fp->nights }}</td>

                {{-- Col 5: Hotels --}}
                <td>
                    @foreach($result->hotels as $stayData)
                        @if(!$loop->first)<br>@endif
                        <a href="#" class="link-hotel" onclick="event.stopPropagation();">{{ $stayData['hotel']->name }}</a>
                        @if($stayData['hotel']->category)
                            <span style="color:#e8a500;">@for($j=0;$j<$stayData['hotel']->category->stars;$j++)★@endfor</span>
                        @endif
                        <span class="hotel-loc">({{ $stayData['city']->name_en ?? '' }})</span>
                    @endforeach
                </td>

                {{-- Col 6: Availability (4 squares) --}}
                <td class="c nw">
                    @foreach($sq as $status)
                        <span class="hotel_avail avail_{{ $status }}" title="{{ $status === 'Y' ? 'available' : ($status === 'F' ? 'a few' : ($status === 'R' ? 'on request' : 'no')) }}"></span>
                    @endforeach
                </td>

                {{-- Col 7: Meal --}}
                <td>BB</td>

                {{-- Col 8: Room --}}
                <td>DBL / {{ $pax }}ADL</td>

                {{-- Col 9: Book button --}}
                <td class="nw" onclick="event.stopPropagation();">
                    @auth
                        <a href="{{ $bookUrl }}" class="book-btn">Book</a>
                    @else
                        <a href="{{ route('login') }}" class="book-btn">Book</a>
                    @endauth
                </td>

                {{-- Col 10: Price --}}
                <td class="td_price">
                    <span class="price {{ $isStop ? 'stop' : '' }}" title="{{ $pax }} × ${{ number_format($result->price, 0) }}/person">
                        {{ number_format($totalPrice, 0) }} {{ $result->currency->code ?? 'USD' }}
                    </span>
                    @if($pax > 1)
                        <span class="price-breakdown">{{ $pax }} × {{ number_format($result->price, 0) }}/pp</span>
                    @endif
                    @if($isStop)
                        <span class="price-breakdown" style="color:#9f4d4d;">stop sale</span>
                    @endif
                </td>

                {{-- Col 11: Route code --}}
                <td class="type_price">{{ $routeCode }}</td>

                {{-- Col 12: Transport --}}
                <td class="nw">
                    <div class="transport-block">
                        <span class="name">Econom</span>
                        <span class="fr_place fr_place_r {{ $seatCls($outSeats) }}" title="→ {{ $seatTip($outSeats) }}"></span>
                        <span class="fr_place fr_place_l {{ $seatCls($retSeats) }}" title="← {{ $seatTip($retSeats) }}"></span>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="no-results">
    <p style="font-weight:bold;">{{ __('messages.no_tours_found') }}</p>
    <p style="font-size:11px;color:#888;">{{ __('messages.try_adjusting_filters') }}</p>
</div>
@endif
