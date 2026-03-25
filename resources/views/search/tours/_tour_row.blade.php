@php
    $seats = $tour->tourPrices->sum('availability');
    $isStop = !$tour->is_available || $seats <= 0;
    $rowClass = $isStop ? 'row-stop' : ($loop->even ? 'row-even' : 'row-odd');
    $bars = availBars($tour, $seats);
@endphp
<tr class="{{ $rowClass }}" onclick="window.location='{{ route('tours.show', $tour) }}'" title="{{ $isStop ? 'Stop sale' : '' }}">
    {{-- 1. Departure --}}
    <td style="white-space:nowrap;">
        <span class="dep-date">{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}</span>
        @if($tour->date_from)
            <span class="dep-day">{{ $tour->date_from->locale('en')->shortDayName }}</span>
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

    {{-- 3. Nights --}}
    <td style="text-align:center; font-weight:600;">{{ $tour->nights ?? 0 }}</td>

    {{-- 4. Hotel (clickable) --}}
    <td>
        @if($tour->stays->isNotEmpty())
            @foreach($tour->stays as $stay)
                <div @if(!$loop->first) style="border-top:1px solid #eee; margin-top:2px; padding-top:2px;" @endif>
                    <a href="{{ route('tours.show', $tour) }}" class="hotel-link" onclick="event.stopPropagation();">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</a>
                    @if($stay->hotel && $stay->hotel->category)
                        <span class="stars">@for($i = 0; $i < $stay->hotel->category->stars; $i++)★@endfor</span>
                    @endif
                    <span class="hotel-loc">({{ $stay->city->name ?? $stay->resort->name_en ?? '' }} {{ $stay->nights }}n)</span>
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

    {{-- 5. Availability bars (4 bars like aquatravel) --}}
    <td style="text-align:center;">
        <span class="avail-bars" title="{{ $isStop ? 'No availability' : $seats . ' seats' }}">
            @foreach($bars as $b)
                <span class="avail-bar bar-{{ $b }}"></span>
            @endforeach
        </span>
    </td>

    {{-- 6. Meal --}}
    <td style="text-align:center;">
        @if($tour->mealType)
            @php $mc = strtolower($tour->mealType->code ?? ''); @endphp
            <span class="meal-badge @if($mc=='ai') meal-ai @elseif($mc=='fb') meal-fb @elseif($mc=='hb') meal-hb @elseif($mc=='bb') meal-bb @else meal-ro @endif">{{ $tour->mealType->code }}</span>
        @else
            <span style="color:#ccc;">-</span>
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

    {{-- 8. Price (with tooltip for stop sale dates) --}}
    <td style="text-align:right;" onclick="event.stopPropagation();">
        <span class="price-val {{ $isStop ? 'price-stop' : '' }}"
              @if($isStop) title="Stop sale active" @endif>
            {{ number_format($tour->price, 0) }} {{ $tour->currency->code ?? 'USD' }}
        </span>
        @if($isStop)
            <span class="stop-label">stop sale</span>
        @endif
    </td>

    {{-- 9. Transport (Econom/Business with seat dots) --}}
    <td style="text-align:center;">
        @if($tour->transportType && (str_contains(strtolower($tour->transportType->name_en ?? ''), 'air') || str_contains(strtolower($tour->transportType->name_en ?? ''), 'plane')))
            <div class="transport-line">
                <span>Econom</span>
                <span class="seat-dot {{ $seats > 5 ? 'seat-y' : ($seats > 0 ? 'seat-f' : 'seat-n') }}" title="{{ $seats > 5 ? 'Available' : ($seats > 0 ? 'Few seats' : 'No seats') }}"></span>
            </div>
        @elseif($tour->transportType)
            <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
        @else
            <span style="color:#ccc;">-</span>
        @endif
    </td>

    {{-- 10. Actions --}}
    <td style="text-align:center; white-space:nowrap;" onclick="event.stopPropagation();">
        <a href="{{ route('tours.show', $tour) }}" class="view-link" title="Details">👁</a>
        <a href="{{ route('bookings.create', $tour) }}" class="book-btn">Book</a>
    </td>
</tr>
