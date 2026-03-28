<style>
.st .results-table { width: 97%; margin: auto; border-collapse: collapse; font-family: Tahoma, Arial, sans-serif; font-size: 13px; }
.st .results-table th {
    background: #366383; color: #ecf5fb; font-weight: bold; font-size: 13px;
    padding: 4px 10px; white-space: nowrap; border-bottom: 1px solid #2a4f6a;
}
.st .results-table th a { color: #ecf5fb; text-decoration: none; }
.st .results-table th a:hover { color: #fff; text-decoration: underline; }
.st .results-table td { padding: 5px 8px; font-size: 13px; vertical-align: middle; }
.st .results-table .row-even td { background: #F6F9FB; border-bottom: 1px solid #8E959A; }
.st .results-table .row-odd td { background: #e3e7ea; border-bottom: 1px solid #8E959A; }
.st .results-table .row-even:hover td, .st .results-table .row-odd:hover td { background: #cdddff; }
.st .results-table .row-stop td { background: #f1afaf; border-bottom: 1px solid #cc5050; color: #9f4d4d; }
.st .results-table tr { cursor: pointer; }
.st .avail-bars { display: inline-flex; gap: 3px; }
.st .avail-bar { width: 5px; height: 16px; box-shadow: gray -1px 1px 2px inset; }
.st .bar-y { background: rgb(77, 182, 70); }
.st .bar-r { background: rgb(169, 169, 169); }
.st .bar-n { background: rgb(199, 61, 66); }
.st .bar-f { background: rgb(255, 193, 7); }
.st .hotel-link { font-weight: 600; color: #366383; cursor: pointer; text-decoration: none; }
.st .hotel-link:hover { color: #1a3a50; text-decoration: underline; }
.st .hotel-loc { font-size: 11px; color: #666; }
.st .stars { color: #e8a500; font-size: 12px; letter-spacing: -1px; }
.st .price-val { font-size: 13px; font-weight: 700; white-space: nowrap; }
.st .stop-label { font-size: 9px; color: #9f4d4d; display: block; font-weight: 600; }
.st .route-code { font-size: 10px; color: #888; display: block; font-family: monospace; }
.st .dep-date { font-weight: 500; color: #222; }
.st .dep-day { font-size: 11px; color: #666; }
.st .dep-time { font-size: 12px; color: #366383; cursor: help; font-weight: 500; }
.st .transport-line { font-size: 12px; color: #333; display: flex; align-items: center; gap: 3px; white-space: nowrap; }
.st .seat-arrow { font-size: 12px; font-weight: bold; cursor: help; }
.st .seat-arrow-y { color: rgb(77, 182, 70); }
.st .seat-arrow-f { color: rgb(255, 193, 7); }
.st .seat-arrow-n { color: rgb(199, 61, 66); }
.st .book-btn { display: inline-block; padding: 3px 10px; background: #1B6B2E; color: #fff; font-size: 11px; font-weight: 600; border-radius: 3px; text-decoration: none; }
.st .book-btn:hover { background: #145222; }
.st .no-results { padding: 40px 20px; text-align: center; }
.st .no-results-title { font-size: 14px; font-weight: 600; color: #555; margin-bottom: 8px; }
.st .no-results-text { font-size: 12px; color: #888; margin-bottom: 14px; }
</style>

@if(count($results ?? []) > 0)
    <table class="results-table">
        <thead>
            <tr>
                <th style="text-align:left;">
                    <a href="#" class="sortable-header" data-sort="date_from">Departure @if(($sortBy ?? '') == 'date_from'){{ ($sortDir ?? 'asc') == 'asc' ? '▲' : '▼' }}@endif</a>
                </th>
                <th style="text-align:left;">Route</th>
                <th style="text-align:center;">Nights</th>
                <th style="text-align:left;">Hotels</th>
                <th style="text-align:center;">Avail</th>
                <th style="text-align:right;">
                    <a href="#" class="sortable-header" data-sort="price">Price @if(($sortBy ?? '') == 'price'){{ ($sortDir ?? 'asc') == 'asc' ? '▲' : '▼' }}@endif</a>
                </th>
                <th style="text-align:center;">Transport</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $i => $result)
                @php
                    $fp = $result->flight_path;
                    $isStop = $result->min_seats <= 0;
                    $rowClass = $isStop ? 'row-stop' : ($i % 2 === 0 ? 'row-even' : 'row-odd');

                    // Availability bars
                    $s = $result->min_seats;
                    $bars = $isStop ? ['n','n','n','n'] : ($s > 10 ? ['y','y','y','y'] : ($s > 5 ? ['y','y','y','f'] : ($s > 2 ? ['f','f','r','r'] : ['f','r','n','n'])));

                    // First outbound flight for departure time
                    $firstLeg = $fp->legs->sortBy('leg_order')->first();
                    $depTime = $firstLeg?->flight?->departure_time ?? null;

                    // Route code
                    $routeCode = $fp->legs->map(fn($l) => $l->flight?->fromAirport?->code)->push($fp->legs->last()?->flight?->toAirport?->code)->filter()->implode('→');

                    // Seat arrows
                    $outSeats = $fp->legs->where('direction', 'outbound')->min(fn($l) => $l->flight->available_seats ?? 0);
                    $retSeats = $fp->legs->where('direction', 'return')->min(fn($l) => $l->flight->available_seats ?? 0);
                    $arrowCls = fn($ss) => $ss > 5 ? 'seat-arrow-y' : ($ss > 0 ? 'seat-arrow-f' : 'seat-arrow-n');
                @endphp
                <tr class="{{ $rowClass }}" data-href="#">
                    {{-- Departure --}}
                    <td style="white-space:nowrap;">
                        <span class="dep-date">{{ $fp->departure_date->format('d.m.Y') }}</span>
                        <span class="dep-day">{{ $fp->departure_date->locale('en')->shortDayName }}</span>
                        @if($depTime)
                            <br><span class="dep-time" title="Departure time">{{ $depTime }}</span>
                        @endif
                    </td>

                    {{-- Route --}}
                    <td>
                        <span style="font-size:13px; color:#333;">{{ $fp->route_name }}</span>
                        <span class="route-code">{{ $routeCode }}</span>
                    </td>

                    {{-- Nights --}}
                    <td style="text-align:center; font-weight:600;">{{ $fp->nights }}</td>

                    {{-- Hotels per city --}}
                    <td>
                        @foreach($result->hotels as $stayData)
                            <div @if(!$loop->first) style="border-top:1px solid #eee; margin-top:2px; padding-top:2px;" @endif>
                                <span class="hotel-link">{{ $stayData['hotel']->name }}</span>
                                @if($stayData['hotel']->category)
                                    <span class="stars">@for($j = 0; $j < $stayData['hotel']->category->stars; $j++)★@endfor</span>
                                @endif
                                <span class="hotel-loc">({{ $stayData['city']->name_en ?? '' }} {{ $stayData['nights'] }}n)</span>
                            </div>
                        @endforeach
                    </td>

                    {{-- Availability --}}
                    <td style="text-align:center;">
                        <span class="avail-bars" title="{{ $isStop ? 'No seats' : $result->min_seats . ' seats' }}">
                            @foreach($bars as $b)
                                <span class="avail-bar bar-{{ $b }}"></span>
                            @endforeach
                        </span>
                    </td>

                    {{-- Price --}}
                    <td style="text-align:right;">
                        <span class="price-val {{ $isStop ? '' : '' }}" title="Flight: ${{ number_format($result->flight_price, 0) }} + Hotels: ${{ number_format($result->hotel_cost, 0) }} + Fees: ${{ number_format($result->fees, 0) }}">
                            {{ number_format($result->price, 0) }} {{ $result->currency->code ?? 'USD' }}
                        </span>
                        @if($isStop)
                            <span class="stop-label">no seats</span>
                        @endif
                    </td>

                    {{-- Transport --}}
                    <td style="text-align:center;">
                        <div class="transport-line">
                            <span>Econom</span>
                            <span class="seat-arrow {{ $arrowCls($outSeats) }}" title="Outbound: {{ $outSeats }} seats">»</span>
                            <span class="seat-arrow {{ $arrowCls($retSeats) }}" title="Return: {{ $retSeats }} seats">«</span>
                        </div>
                    </td>

                    {{-- Actions --}}
                    <td style="text-align:center; white-space:nowrap;">
                        <a href="#" class="book-btn">Book</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="no-results">
        <div class="no-results-title">{{ __('messages.no_tours_found') }}</div>
        <div class="no-results-text">{{ __('messages.try_adjusting_filters') }}</div>
        <a href="{{ route('search.tours') }}" class="book-btn" style="padding:5px 16px; font-size:12px;">{{ __('messages.start_new_search') }}</a>
    </div>
@endif
