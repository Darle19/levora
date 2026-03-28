@php
    // Availability based on flight seats (min across all legs)
    $flightSeatsMin = $tour->flights->isNotEmpty() ? $tour->flights->min('available_seats') : 0;
    $seats = $flightSeatsMin;
    $isStop = !$tour->is_available || $seats <= 0;
    $rowClass = $isStop ? 'row-stop' : ($loop->even ? 'row-even' : 'row-odd');
    $bars = availBars($tour, $seats);

    // Calculate hotel nights vs total nights (travel nights = total - hotel)
    $hotelNights = $tour->stays->isNotEmpty() ? $tour->stays->sum('nights') : $tour->nights;
    $travelNights = ($tour->nights ?? 0) - $hotelNights;

    // Flights: outbound and return
    $outboundFlight = $tour->flights->firstWhere('pivot.direction', 'outbound')
        ?? $tour->flights->sortBy('pivot.leg_order')->first();
    $returnFlight = $tour->flights->firstWhere('pivot.direction', 'return')
        ?? $tour->flights->sortByDesc('pivot.leg_order')->first();
    $depTime = $outboundFlight?->departure_time ?? null;
    $flightSeats = $outboundFlight?->available_seats ?? 0;

    // Seat status helper
    $seatClass = fn($s) => $s > 5 ? 'seat-y' : ($s > 0 ? 'seat-f' : 'seat-n');
    $seatTitle = fn($s) => $s > 5 ? 'Many seats (' . $s . ')' : ($s > 0 ? 'Few seats (' . $s . ')' : 'No seats');
    $outSeats = $outboundFlight?->available_seats ?? 0;
    $retSeats = $returnFlight?->available_seats ?? 0;

    // Build flights monitor data for popup
    $fmData = [
        'date' => $tour->date_from?->format('d.m.Y, D') ?? '-',
        'hotel' => $tour->hotel->name ?? ($tour->stays->first()?->hotel?->name ?? '-'),
        'nights' => $tour->nights,
        'price' => number_format($tour->price, 0) . ' ' . ($tour->currency->code ?? 'USD'),
        'flights' => $tour->flights->map(function($f) {
            return [
                'date' => $f->departure_date?->format('d.m.Y, D') ?? '-',
                'direction' => $f->pivot->direction ?? 'outbound',
                'airline' => $f->airline->name ?? 'Unknown',
                'flight_number' => $f->flight_number ?? '-',
                'from' => $f->fromAirport->code ?? '???',
                'to' => $f->toAirport->code ?? '???',
                'from_city' => $f->fromAirport->city->name_en ?? $f->fromAirport->name_en ?? '',
                'to_city' => $f->toAirport->city->name_en ?? $f->toAirport->name_en ?? '',
                'dep_time' => $f->departure_time ?? '-',
                'arr_time' => $f->arrival_time ?? '-',
                'seats' => $f->available_seats ?? 0,
            ];
        })->values()->toArray(),
    ];
@endphp
<tr class="{{ $rowClass }}" data-href="{{ route('tours.show', $tour) }}">
    {{-- 1. Departure date + time --}}
    <td style="white-space:nowrap;">
        <span class="dep-date">{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}</span>
        <span class="dep-day">{{ $tour->date_from?->locale('en')->shortDayName }}</span>
        @if($depTime)
            <br><span class="dep-time tip" title="Departure time">{{ $depTime }}</span>
        @endif
    </td>

    {{-- 1b. Stats/Flights monitor icon --}}
    <td style="text-align:center; width:24px; padding:2px;">
        @if($tour->flights->isNotEmpty())
            <button class="stats-btn" onclick="openFM(this)" data-fm='@json($fmData)' title="Flights monitor">📊</button>
        @endif
    </td>

    {{-- 2. Tour name + route code --}}
    <td>
        <span class="tour-name">
            @if($tour->stays->isNotEmpty())
                {{ $tour->stays->pluck('city.name_en')->filter()->unique()->implode(' + ') }}
            @else
                {{ $tour->country->name_en ?? '-' }}
            @endif
        </span>
        <span class="route-code">{{ tourRouteCode($tour) }}</span>
    </td>

    {{-- 3. Nights (hotel + travel) --}}
    <td style="text-align:center; font-weight:600;">
        {{ $hotelNights }}@if($travelNights > 0)<span class="tip" title="Nights on the road" style="color:#005991; font-weight:400; cursor:help;">+{{ $travelNights }}</span>@endif
    </td>

    {{-- 4. Hotel (clickable link) --}}
    <td>
        @if($tour->stays->isNotEmpty())
            @foreach($tour->stays as $stay)
                <div @if(!$loop->first) style="border-top:1px solid #eee; margin-top:2px; padding-top:2px;" @endif>
                    <a href="{{ route('tours.show', $tour) }}" class="hotel-link">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</a>
                    @if($stay->hotel && $stay->hotel->category)
                        <span class="stars">@for($i = 0; $i < $stay->hotel->category->stars; $i++)★@endfor</span>
                    @endif
                    <span class="hotel-loc">({{ $stay->city->name ?? $stay->resort->name_en ?? '' }})</span>
                </div>
            @endforeach
        @else
            <a href="{{ route('tours.show', $tour) }}" class="hotel-link">{{ $tour->hotel->name ?? '-' }}</a>
            @if($tour->hotel && $tour->hotel->category)
                <span class="stars">@for($i = 0; $i < $tour->hotel->category->stars; $i++)★@endfor</span>
            @endif
            @if($tour->resort)
                <span class="hotel-loc">({{ $tour->resort->name_en }})</span>
            @endif
        @endif
    </td>

    {{-- 5. Availability bars --}}
    <td style="text-align:center;">
        <span class="avail-bars" title="{{ $isStop ? 'No availability' : $seats . ' room(s) available' }}">
            @foreach($bars as $b)
                <span class="avail-bar bar-{{ $b }}"></span>
            @endforeach
        </span>
    </td>

    {{-- 6. Meal --}}
    <td style="text-align:center;">
        @if($tour->mealType)
            @php $mc = strtolower($tour->mealType->code ?? ''); @endphp
            <span class="meal-badge @if($mc=='ai') meal-ai @elseif($mc=='fb') meal-fb @elseif($mc=='hb') meal-hb @elseif($mc=='bb') meal-bb @else meal-ro @endif" title="{{ $tour->mealType->name_en ?? $tour->mealType->code }}">{{ $tour->mealType->code }}</span>
        @else
            <span class="meal-badge meal-ro" title="Room only">RO</span>
        @endif
    </td>

    {{-- 7. Room / Accommodation --}}
    <td>
        @if($tour->tourPrices && $tour->tourPrices->first())
            <span class="room-info">{{ strtoupper($tour->tourPrices->first()->roomType->name_en ?? 'STD') }}</span>
            <span class="room-pax">/ {{ $tour->adults ?? 2 }}ADL{{ $tour->children ? '+' . $tour->children . 'CHD' : '' }}</span>
        @else
            <span class="room-info">STANDARD</span>
            <span class="room-pax">/ {{ $tour->adults ?? 2 }}ADL</span>
        @endif
    </td>

    {{-- 8. Price --}}
    <td style="text-align:right;">
        <span class="price-val {{ $isStop ? 'price-stop' : '' }}"
              @if($isStop) title="Stop sale — booking unavailable" @else title="Price per person" @endif>
            {{ number_format($tour->price, 0) }} {{ $tour->currency->code ?? 'USD' }}
        </span>
        @if($isStop)
            <span class="stop-label">stop sale</span>
        @endif
    </td>

    {{-- 9. Transport (Econom/Business with →outbound ←return arrows) --}}
    <td class="nw" style="text-align:left;">
        @if($tour->flights->isNotEmpty())
            @php
                $arrowCls = fn($s) => $s > 5 ? 'seat-arrow-y' : ($s > 0 ? 'seat-arrow-f' : 'seat-arrow-n');
                $arrowTip = fn($dir, $s) => $dir . ': ' . ($s > 5 ? 'many seats (' . $s . ')' : ($s > 0 ? 'few seats (' . $s . ')' : 'no seats'));
            @endphp
            <div class="transport-line">
                <span>Econom</span>
                <span class="seat-arrow {{ $arrowCls($outSeats) }}" title="{{ $arrowTip('Outbound', $outSeats) }}">»</span>
                <span class="seat-arrow {{ $arrowCls($retSeats) }}" title="{{ $arrowTip('Return', $retSeats) }}">«</span>
            </div>
        @elseif($tour->transportType)
            <span style="font-size:12px;">{{ $tour->transportType->name_en }}</span>
        @else
            <span style="color:#aaa;">-</span>
        @endif
    </td>

    {{-- 10. Actions --}}
    <td style="text-align:center; white-space:nowrap;">
        <a href="{{ route('tours.show', $tour) }}" class="view-link" title="View details">👁</a>
        <a href="{{ route('bookings.create', $tour) }}" class="book-btn">Book</a>
    </td>
</tr>
