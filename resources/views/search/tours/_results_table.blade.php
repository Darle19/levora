<style>
.st .results-table { width: 100%; border-collapse: collapse; }
.st .results-table th {
    background: #f0f4f7; padding: 6px 8px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; color: #555; border-bottom: 2px solid #ccc;
    white-space: nowrap;
}
.st .results-table th a { color: #555; text-decoration: none; }
.st .results-table th a:hover { color: #1B6B2E; }
.st .results-table td { padding: 6px 8px; border-bottom: 1px solid #e8e8e8; font-size: 12px; vertical-align: middle; }
.st .results-table .row-even { background: #fff; }
.st .results-table .row-odd { background: #f8f9fa; }
.st .results-table .row-even:hover, .st .results-table .row-odd:hover { background: #eef6ee; }
.st .results-table .row-stop { background: #fff0f0; }
.st .results-table .row-stop:hover { background: #ffe8e8; }
.st .hotel-group-row { background: #e8f5e9; cursor: pointer; font-weight: 600; }
.st .hotel-group-row:hover { background: #d4edda; }
.st .hotel-group-row td { padding: 7px 8px; font-size: 12px; }
.st .avail-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; vertical-align: middle; }
.st .avail-green { background: #28a745; }
.st .avail-yellow { background: #ffc107; }
.st .avail-red { background: #dc3545; }
.st .avail-request { background: #6c757d; }
.st .meal-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; }
.st .meal-ai { background: #e8d5f5; color: #6b21a8; }
.st .meal-fb { background: #dbeafe; color: #1d4ed8; }
.st .meal-hb { background: #d1fae5; color: #065f46; }
.st .meal-bb { background: #fef3c7; color: #92400e; }
.st .meal-ro { background: #f3f4f6; color: #4b5563; }
.st .book-btn {
    display: inline-block; padding: 3px 10px; background: #1B6B2E; color: #fff;
    font-size: 11px; font-weight: 600; border-radius: 3px; text-decoration: none;
}
.st .book-btn:hover { background: #145222; }
.st .view-link { color: #1B6B2E; text-decoration: none; font-size: 13px; margin-right: 6px; }
.st .view-link:hover { color: #145222; }
.st .hotel-name { font-weight: 600; color: #222; }
.st .hotel-loc { font-size: 10px; color: #888; }
.st .stars { color: #e8a500; font-size: 12px; letter-spacing: -1px; }
.st .price-val { font-size: 13px; font-weight: 700; color: #1B6B2E; white-space: nowrap; }
.st .price-cur { font-size: 10px; color: #888; margin-left: 2px; }
.st .price-stop { color: #c00; }
.st .tour-name { font-size: 11px; color: #444; }
.st .room-info { font-size: 11px; color: #333; }
.st .room-pax { font-size: 10px; color: #888; }
.st .transport-cls { font-size: 10px; color: #555; display: block; white-space: nowrap; }
.st .seat-avail { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-left: 3px; vertical-align: middle; }
.st .seat-y { background: #28a745; }
.st .seat-f { background: #ffc107; }
.st .seat-n { background: #dc3545; }
.st .no-results { padding: 40px 20px; text-align: center; }
.st .no-results-title { font-size: 14px; font-weight: 600; color: #555; margin-bottom: 8px; }
.st .no-results-text { font-size: 12px; color: #888; margin-bottom: 14px; }
.st .stop-label { font-size: 9px; color: #c00; display: block; }
</style>

@if($tours->count() > 0)
    @if($groupByHotel)
        {{-- Grouped by Hotel View --}}
        @php $groupedTours = $tours->groupBy('hotel_id'); @endphp

        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left; width:90px;">
                        <a href="#" class="sortable-header" data-sort="date_from">
                            {{ __('messages.check_in') }}@if($sortBy == 'date_from') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">{{ __('messages.tour') ?? 'Tour' }}</th>
                    <th style="text-align:center; width:50px;">
                        <a href="#" class="sortable-header" data-sort="nights">
                            {{ __('messages.nights') }}@if($sortBy == 'nights') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">
                            {{ __('messages.hotel') }}@if($sortBy == 'hotel_name') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:center; width:50px;">{{ __('messages.avail') }}</th>
                    <th style="text-align:center;">{{ __('messages.meal') }}</th>
                    <th style="text-align:left;">{{ __('messages.room') ?? 'Room' }}</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">
                            {{ __('messages.price') }}@if($sortBy == 'price') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.transport') }}</th>
                    <th style="text-align:center; width:80px;"></th>
                </tr>
            </thead>

            @foreach($groupedTours as $hotelId => $hotelTours)
                @php $firstTour = $hotelTours->first(); $hotel = $firstTour->hotel; @endphp

                <tbody>
                    <tr class="hotel-group-row hotel-group-header" data-hotel-id="{{ $hotelId }}">
                        <td colspan="10">
                            <span class="toggle-icon" style="display:inline-block; width:14px;">▼</span>
                            <span class="hotel-name">{{ $hotel->name ?? __('messages.unknown_hotel') }}</span>
                            @if($hotel && $hotel->category)
                                <span class="stars" style="margin-left:3px;">@for($i = 0; $i < $hotel->category->stars; $i++)★@endfor</span>
                            @endif
                            <span class="hotel-loc" style="margin-left:8px;">
                                @if($firstTour->stays->isNotEmpty())
                                    ({{ $firstTour->stays->map(fn($s) => ($s->city->name ?? $s->resort->name_en ?? '') . ' ' . $s->nights . 'n')->join(' + ') }})
                                @elseif($firstTour->resort)
                                    ({{ $firstTour->resort->name_en }})
                                @endif
                            </span>
                            <span style="float:right; font-weight:400; font-size:11px; color:#555;">{{ $hotelTours->count() }} {{ __('messages.tours') }}</span>
                        </td>
                    </tr>
                </tbody>

                <tbody id="hotel-group-{{ $hotelId }}">
                    @foreach($hotelTours as $tour)
                        @php
                            $seats = $tour->tourPrices->sum('availability');
                            $isStop = !$tour->is_available || $seats <= 0;
                            $rowClass = $isStop ? 'row-stop' : ($loop->even ? 'row-even' : 'row-odd');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td style="white-space:nowrap;">
                                {{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}
                                @if($tour->date_from)
                                    <span style="font-size:10px; color:#888;">{{ $tour->date_from->locale('en')->shortDayName }}</span>
                                @endif
                            </td>
                            <td class="tour-name">
                                @if($tour->stays->isNotEmpty())
                                    {{ $tour->stays->pluck('city.name_en')->filter()->unique()->implode(' + ') }}
                                @else
                                    {{ $tour->country->name_en ?? '-' }}
                                @endif
                            </td>
                            <td style="text-align:center; font-weight:600;">{{ $tour->nights ?? 0 }}</td>
                            <td>
                                @if($tour->stays->isNotEmpty())
                                    @foreach($tour->stays as $stay)
                                        <div @if(!$loop->first) style="border-top:1px solid #eee; margin-top:2px; padding-top:2px;" @endif>
                                            <span class="hotel-name" style="font-size:11px;">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</span>
                                            @if($stay->hotel && $stay->hotel->category)
                                                <span class="stars" style="font-size:10px;">@for($i = 0; $i < $stay->hotel->category->stars; $i++)★@endfor</span>
                                            @endif
                                            <span style="font-size:10px; color:#999;">({{ $stay->nights }}n)</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="hotel-name" style="font-size:11px;">{{ $tour->hotel->name ?? '-' }}</span>
                                    @if($tour->hotel && $tour->hotel->category)
                                        <span class="stars" style="font-size:10px;">@for($i = 0; $i < $tour->hotel->category->stars; $i++)★@endfor</span>
                                    @endif
                                    @if($tour->resort)
                                        <span class="hotel-loc">({{ $tour->resort->name_en }})</span>
                                    @endif
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($tour->is_available && $seats > 5)
                                    <span class="avail-dot avail-green" title="{{ __('messages.available') }} ({{ $seats }})"></span>
                                @elseif($tour->is_available && $seats > 0)
                                    <span class="avail-dot avail-yellow" title="{{ __('messages.limited') }} ({{ $seats }})"></span>
                                @else
                                    <span class="avail-dot avail-red" title="{{ __('messages.sold_out') }}"></span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($tour->mealType)
                                    @php $mc = strtolower($tour->mealType->code); @endphp
                                    <span class="meal-badge @if($mc=='ai') meal-ai @elseif($mc=='fb') meal-fb @elseif($mc=='hb') meal-hb @elseif($mc=='bb') meal-bb @else meal-ro @endif">{{ $tour->mealType->code }}</span>
                                @else
                                    <span style="color:#ccc;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($tour->tourPrices && $tour->tourPrices->first())
                                    <span class="room-info">{{ $tour->tourPrices->first()->roomType->name_en ?? 'Standard' }}</span>
                                    <span class="room-pax">/ {{ $tour->adults ?? 2 }}ADL{{ $tour->children ? '+' . $tour->children . 'CHD' : '' }}</span>
                                @else
                                    <span class="room-info">Standard</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <span class="price-val {{ $isStop ? 'price-stop' : '' }}">{{ number_format($tour->price, 0) }} {{ $tour->currency->code ?? 'USD' }}</span>
                                @if($isStop)
                                    <span class="stop-label">stop sale</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($tour->transportType)
                                    @if(str_contains(strtolower($tour->transportType->name_en ?? ''), 'air') || str_contains(strtolower($tour->transportType->name_en ?? ''), 'plane'))
                                        <span class="transport-cls">Econom <span class="seat-avail {{ $seats > 5 ? 'seat-y' : ($seats > 0 ? 'seat-f' : 'seat-n') }}"></span></span>
                                    @else
                                        <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
                                    @endif
                                @else
                                    <span style="color:#ccc;">-</span>
                                @endif
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                <a href="{{ route('tours.show', $tour) }}" class="view-link" title="{{ __('messages.view_details') }}">👁</a>
                                <a href="{{ route('bookings.create', $tour) }}" class="book-btn">{{ __('messages.book') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>

    @else
        {{-- Standard Table View --}}
        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left; width:90px;">
                        <a href="#" class="sortable-header" data-sort="date_from">
                            {{ __('messages.check_in') }}@if($sortBy == 'date_from') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">{{ __('messages.tour') ?? 'Tour' }}</th>
                    <th style="text-align:center; width:50px;">
                        <a href="#" class="sortable-header" data-sort="nights">
                            {{ __('messages.nights') }}@if($sortBy == 'nights') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">
                            {{ __('messages.hotel') }}@if($sortBy == 'hotel_name') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:center; width:50px;">{{ __('messages.avail') }}</th>
                    <th style="text-align:center;">{{ __('messages.meal') }}</th>
                    <th style="text-align:left;">{{ __('messages.room') ?? 'Room' }}</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">
                            {{ __('messages.price') }}@if($sortBy == 'price') {{ $sortDir == 'asc' ? '▲' : '▼' }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.transport') }}</th>
                    <th style="text-align:center; width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tours as $tour)
                    @php
                        $seats = $tour->tourPrices->sum('availability');
                        $isStop = !$tour->is_available || $seats <= 0;
                        $rowClass = $isStop ? 'row-stop' : ($loop->even ? 'row-even' : 'row-odd');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td style="white-space:nowrap;">
                            {{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}
                            @if($tour->date_from)
                                <span style="font-size:10px; color:#888;">{{ $tour->date_from->locale('en')->shortDayName }}</span>
                            @endif
                        </td>
                        <td class="tour-name">
                            @if($tour->stays->isNotEmpty())
                                {{ $tour->stays->pluck('city.name_en')->filter()->unique()->implode(' + ') }}
                            @else
                                {{ $tour->country->name_en ?? '-' }}
                            @endif
                        </td>
                        <td style="text-align:center; font-weight:600;">{{ $tour->nights ?? 0 }}</td>
                        <td>
                            @if($tour->stays->isNotEmpty())
                                @foreach($tour->stays as $stay)
                                    <div @if(!$loop->first) style="border-top:1px solid #eee; margin-top:2px; padding-top:2px;" @endif>
                                        <span class="hotel-name" style="font-size:11px;">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</span>
                                        @if($stay->hotel && $stay->hotel->category)
                                            <span class="stars" style="font-size:10px;">@for($i = 0; $i < $stay->hotel->category->stars; $i++)★@endfor</span>
                                        @endif
                                        <span style="font-size:10px; color:#999;">({{ $stay->nights }}n)</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="hotel-name" style="font-size:11px;">{{ $tour->hotel->name ?? '-' }}</span>
                                @if($tour->hotel && $tour->hotel->category)
                                    <span class="stars" style="font-size:10px;">@for($i = 0; $i < $tour->hotel->category->stars; $i++)★@endfor</span>
                                @endif
                                @if($tour->resort)
                                    <span class="hotel-loc">({{ $tour->resort->name_en }})</span>
                                @endif
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tour->is_available && $seats > 5)
                                <span class="avail-dot avail-green" title="{{ __('messages.available') }} ({{ $seats }})"></span>
                            @elseif($tour->is_available && $seats > 0)
                                <span class="avail-dot avail-yellow" title="{{ __('messages.limited') }} ({{ $seats }})"></span>
                            @else
                                <span class="avail-dot avail-red" title="{{ __('messages.sold_out') }}"></span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tour->mealType)
                                @php $mc = strtolower($tour->mealType->code); @endphp
                                <span class="meal-badge @if($mc=='ai') meal-ai @elseif($mc=='fb') meal-fb @elseif($mc=='hb') meal-hb @elseif($mc=='bb') meal-bb @else meal-ro @endif">{{ $tour->mealType->code }}</span>
                            @else
                                <span style="color:#ccc;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($tour->tourPrices && $tour->tourPrices->first())
                                <span class="room-info">{{ $tour->tourPrices->first()->roomType->name_en ?? 'Standard' }}</span>
                                <span class="room-pax">/ {{ $tour->adults ?? 2 }}ADL{{ $tour->children ? '+' . $tour->children . 'CHD' : '' }}</span>
                            @else
                                <span class="room-info">Standard</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <span class="price-val {{ $isStop ? 'price-stop' : '' }}">{{ number_format($tour->price, 0) }} {{ $tour->currency->code ?? 'USD' }}</span>
                            @if($isStop)
                                <span class="stop-label">stop sale</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tour->transportType)
                                @if(str_contains(strtolower($tour->transportType->name_en ?? ''), 'air') || str_contains(strtolower($tour->transportType->name_en ?? ''), 'plane'))
                                    <span class="transport-cls">Econom <span class="seat-avail {{ $seats > 5 ? 'seat-y' : ($seats > 0 ? 'seat-f' : 'seat-n') }}"></span></span>
                                @else
                                    <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
                                @endif
                            @else
                                <span style="color:#ccc;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="{{ route('tours.show', $tour) }}" class="view-link" title="{{ __('messages.view_details') }}">👁</a>
                            <a href="{{ route('bookings.create', $tour) }}" class="book-btn">{{ __('messages.book') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@else
    <div class="no-results">
        <div class="no-results-title">{{ __('messages.no_tours_found') }}</div>
        <div class="no-results-text">{{ __('messages.try_adjusting_filters') }}</div>
        <a href="{{ route('search.tours') }}" class="book-btn" style="padding:5px 16px; font-size:12px;">{{ __('messages.start_new_search') }}</a>
    </div>
@endif
