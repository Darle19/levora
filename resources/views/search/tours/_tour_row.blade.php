@php
    $seats = $tour->tourPrices->sum('availability');
    $isStop = !$tour->is_available || $seats <= 0;
    $rowClass = $isStop ? 'row-stop' : ($loop->even ? 'row-even' : 'row-odd');
    $bars = availBars($tour, $seats);

    // Calculate hotel nights vs total nights (travel nights = total - hotel)
    $hotelNights = $tour->stays->isNotEmpty() ? $tour->stays->sum('nights') : $tour->nights;
    $travelNights = ($tour->nights ?? 0) - $hotelNights;

    // First outbound flight time
    $firstFlight = $tour->flights->sortBy('pivot.leg_order')->first();
    $depTime = $firstFlight?->departure_time ?? null;

    // Flight seat info
    $flightSeats = $firstFlight?->available_seats ?? 0;

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
<tr class="{{ $rowClass }}" onclick="window.location='{{ route('tours.show', $tour) }}'">
    {{-- 1. Departure date + time --}}
    <td style="white-space:nowrap;">
        <span class="dep-date">{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}</span>
        <span class="dep-day">{{ $tour->date_from?->locale('en')->shortDayName }}</span>
        @if($depTime)
            <br><span class="dep-time tip" title="Departure time">{{ $depTime }}</span>
        @endif
    </td>

    {{-- 1b. Stats/Flights monitor icon --}}
    <td style="text-align:center; width:24px; padding:2px;" onclick="event.stopPropagation();">
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
                    <a href="{{ route('tours.show', $tour) }}" class="hotel-link" onclick="event.stopPropagation();">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</a>
                    @if($stay->hotel && $stay->hotel->category)
                        <span class="stars">@for($i = 0; $i < $stay->hotel->category->stars; $i++)★@endfor</span>
                    @endif
                    <span class="hotel-loc">({{ $stay->city->name ?? $stay->resort->name_en ?? '' }})</span>
                </div>
            @endforeach
        @else
            <a href="{{ route('tours.show', $tour) }}" class="hotel-link" onclick="event.stopPropagation();">{{ $tour->hotel->name ?? '-' }}</a>
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
    <td style="text-align:right;" onclick="event.stopPropagation();">
        <span class="price-val {{ $isStop ? 'price-stop' : '' }}"
              @if($isStop) title="Stop sale — booking unavailable" @else title="Price per person" @endif>
            {{ number_format($tour->price, 0) }} {{ $tour->currency->code ?? 'USD' }}
        </span>
        @if($isStop)
            <span class="stop-label">stop sale</span>
        @endif
    </td>

    {{-- 9. Transport (Econom + Business with seat indicators) --}}
    <td style="text-align:center;">
        @if($tour->transportType && (str_contains(strtolower($tour->transportType->name_en ?? ''), 'air') || str_contains(strtolower($tour->transportType->name_en ?? ''), 'plane')))
            <div class="transport-line">
                <span>Econom</span>
                <span class="seat-dot {{ $flightSeats > 5 ? 'seat-y' : ($flightSeats > 0 ? 'seat-f' : 'seat-n') }}" title="{{ $flightSeats > 5 ? 'Seats available' : ($flightSeats > 0 ? 'Few seats (' . $flightSeats . ')' : 'No seats') }}"></span>
                <span class="seat-dot {{ $flightSeats > 0 ? 'seat-f' : 'seat-n' }}" title="{{ $flightSeats > 0 ? 'Return available' : 'No return seats' }}"></span>
            </div>
            <div class="transport-line">
                <span>Business</span>
                <span class="seat-dot seat-f" title="Few seats"></span>
                <span class="seat-dot seat-n" title="No seats"></span>
            </div>
        @elseif($tour->transportType)
            <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
        @else
            <span style="color:#ccc;">-</span>
        @endif
    </td>

    {{-- 10. Actions --}}
    <td style="text-align:center; white-space:nowrap;" onclick="event.stopPropagation();">
        <a href="{{ route('tours.show', $tour) }}" class="view-link" title="View details">👁</a>
        <a href="{{ route('bookings.create', $tour) }}" class="book-btn">Book</a>
    </td>
</tr>
