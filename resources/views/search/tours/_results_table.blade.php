<style>
.st .results-table { width: 100%; border-collapse: collapse; }
.st .results-table th {
    background: #f0f4f7; padding: 4px 6px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; color: #555; border-bottom: 2px solid #ddd;
    white-space: nowrap;
}
.st .results-table th a { color: #555; text-decoration: none; }
.st .results-table th a:hover { color: #1B6B2E; }
.st .results-table td { padding: 3px 6px; border-bottom: 1px solid #eee; font-size: 12px; }
.st .results-table tr:hover { background: #f8fdf9; }
.st .hotel-group-row { background: #e8f5e9; cursor: pointer; font-weight: 600; }
.st .hotel-group-row:hover { background: #d4edda; }
.st .hotel-group-row td { padding: 5px 6px; font-size: 12px; }
.st .avail-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.st .avail-green { background: #28a745; }
.st .avail-yellow { background: #ffc107; }
.st .avail-red { background: #dc3545; }
.st .meal-badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 10px; font-weight: 600; }
.st .meal-ai { background: #e8d5f5; color: #6b21a8; }
.st .meal-fb { background: #dbeafe; color: #1d4ed8; }
.st .meal-hb { background: #d1fae5; color: #065f46; }
.st .meal-bb { background: #fef3c7; color: #92400e; }
.st .meal-ro { background: #f3f4f6; color: #4b5563; }
.st .book-btn {
    display: inline-block; padding: 2px 8px; background: #1B6B2E; color: #fff;
    font-size: 11px; font-weight: 600; border-radius: 3px; text-decoration: none;
}
.st .book-btn:hover { background: #145222; }
.st .view-link { color: #1B6B2E; text-decoration: none; font-size: 14px; }
.st .view-link:hover { color: #145222; }
.st .hotel-name { font-weight: 600; color: #222; }
.st .hotel-location { font-size: 10px; color: #888; }
.st .price-main { font-size: 14px; font-weight: 700; color: #1B6B2E; }
.st .price-currency { font-size: 10px; color: #888; }
.st .transport-icon { font-size: 14px; }
.st .no-results { padding: 40px 20px; text-align: center; }
.st .no-results-title { font-size: 14px; font-weight: 600; color: #555; margin-bottom: 6px; }
.st .no-results-text { font-size: 12px; color: #888; margin-bottom: 12px; }
</style>

@if($tours->count() > 0)
    @if($groupByHotel)
        {{-- Grouped by Hotel View --}}
        @php $groupedTours = $tours->groupBy('hotel_id'); @endphp

        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="date_from">
                            {{ __('messages.check_in') }}@if($sortBy == 'date_from') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">
                        <a href="#" class="sortable-header" data-sort="nights">
                            {{ __('messages.nights') }}@if($sortBy == 'nights') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">
                            {{ __('messages.hotel') }}@if($sortBy == 'hotel_name') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.avail') }}</th>
                    <th style="text-align:center;">{{ __('messages.meal') }}</th>
                    <th style="text-align:left;">{{ __('messages.room') }}</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">
                            {{ __('messages.price') }}@if($sortBy == 'price') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.transport') }}</th>
                    <th style="text-align:center;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>

            @foreach($groupedTours as $hotelId => $hotelTours)
                @php
                    $firstTour = $hotelTours->first();
                    $hotel = $firstTour->hotel;
                @endphp

                {{-- Hotel Group Header --}}
                <tbody>
                    <tr class="hotel-group-row hotel-group-header" data-hotel-id="{{ $hotelId }}">
                        <td colspan="9">
                            <span class="toggle-icon" style="display:inline-block; width:12px; margin-right:4px;">&#9660;</span>
                            <span class="hotel-name">{{ $hotel->name ?? __('messages.unknown_hotel') }}</span>
                            @if($hotel && $hotel->category)
                                <span class="stars" style="margin-left:4px;">@for($i = 0; $i < $hotel->category->stars; $i++)&#9733;@endfor</span>
                            @endif
                            <span class="hotel-location" style="margin-left:8px;">
                                @if($firstTour->stays->isNotEmpty())
                                    {{ $firstTour->stays->map(fn($s) => ($s->city->name ?? $s->resort->name_en ?? '') . ' (' . $s->nights . 'n)')->join(' + ') }}
                                @elseif($firstTour->resort)
                                    {{ $firstTour->resort->name_en }}, {{ $firstTour->country->name_en ?? '' }}
                                @endif
                            </span>
                            <span style="float:right; font-weight:400; font-size:11px; color:#555;">{{ $hotelTours->count() }} {{ __('messages.tours') }}</span>
                        </td>
                    </tr>
                </tbody>

                {{-- Tour Rows --}}
                <tbody id="hotel-group-{{ $hotelId }}">
                    @foreach($hotelTours as $tour)
                        <tr>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('tours.show', $tour) }}" style="color:#1B6B2E; text-decoration:none; font-weight:500;">{{ $tour->date_from ? $tour->date_from->format('d.m') : '-' }}</a>
                            </td>
                            <td style="text-align:center; font-weight:600;">{{ $tour->nights ?? 0 }}</td>
                            <td>
                                @if($tour->stays->isNotEmpty())
                                    @foreach($tour->stays as $stay)
                                        <div @if(!$loop->first) style="border-top:1px solid #f0f0f0; margin-top:1px; padding-top:1px;" @endif>
                                            <span class="hotel-name" style="font-size:12px;">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</span>
                                            <span style="font-size:10px; color:#999;">{{ $stay->nights }}n</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="hotel-name" style="font-size:12px;">{{ $tour->hotel->name ?? '-' }}</span>
                                    @if($tour->hotel && $tour->hotel->category)
                                        <span class="stars" style="font-size:11px;">@for($i = 0; $i < $tour->hotel->category->stars; $i++)&#9733;@endfor</span>
                                    @endif
                                    @if($tour->resort)
                                        <div class="hotel-location">{{ $tour->resort->name_en }}</div>
                                    @endif
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @php $seats = $tour->tourPrices->sum('availability'); @endphp
                                @if($tour->is_available && $seats > 5)
                                    <span class="avail-dot avail-green" title="{{ __('messages.available') }}"></span>
                                @elseif($tour->is_available && $seats > 0)
                                    <span class="avail-dot avail-yellow" title="{{ __('messages.limited') }}"></span>
                                @else
                                    <span class="avail-dot avail-red" title="{{ __('messages.sold_out') }}"></span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($tour->mealType)
                                    @php $mc = strtolower($tour->mealType->code); @endphp
                                    <span class="meal-badge @if($mc == 'ai') meal-ai @elseif($mc == 'fb') meal-fb @elseif($mc == 'hb') meal-hb @elseif($mc == 'bb') meal-bb @else meal-ro @endif">{{ $tour->mealType->code }}</span>
                                @else
                                    <span style="color:#ccc;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($tour->tourPrices && $tour->tourPrices->first())
                                    {{ $tour->tourPrices->first()->roomType->name_en ?? __('messages.standard') }}
                                @else
                                    {{ __('messages.standard') }}
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <span class="price-main">{{ number_format($tour->price, 0) }}</span><br>
                                <span class="price-currency">{{ $tour->currency->code ?? 'USD' }}</span>
                            </td>
                            <td style="text-align:center;">
                                @if($tour->transportType)
                                    @if(str_contains(strtolower($tour->transportType->name_en), 'flight') || str_contains(strtolower($tour->transportType->name_en), 'plane'))
                                        <span class="transport-icon" title="{{ $tour->transportType->name_en }}">&#9992;</span>
                                    @elseif(str_contains(strtolower($tour->transportType->name_en), 'bus'))
                                        <span class="transport-icon" title="{{ $tour->transportType->name_en }}">&#128652;</span>
                                    @else
                                        <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
                                    @endif
                                @else
                                    <span style="color:#ccc;">-</span>
                                @endif
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                <a href="{{ route('tours.show', $tour) }}" class="view-link" title="{{ __('messages.view_details') }}">&#128065;</a>
                                <a href="{{ route('bookings.create', $tour) }}" class="book-btn" title="{{ __('messages.book_now') }}">{{ __('messages.book') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>

    @else
        {{-- Standard Table View (Not Grouped) --}}
        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="date_from">
                            {{ __('messages.check_in') }}@if($sortBy == 'date_from') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">
                        <a href="#" class="sortable-header" data-sort="nights">
                            {{ __('messages.nights') }}@if($sortBy == 'nights') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">
                            {{ __('messages.hotel') }}@if($sortBy == 'hotel_name') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.avail') }}</th>
                    <th style="text-align:center;">{{ __('messages.meal') }}</th>
                    <th style="text-align:left;">{{ __('messages.room') }}</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">
                            {{ __('messages.price') }}@if($sortBy == 'price') {{ $sortDir == 'asc' ? "\u25B2" : "\u25BC" }}@endif
                        </a>
                    </th>
                    <th style="text-align:center;">{{ __('messages.transport') }}</th>
                    <th style="text-align:center;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tours as $tour)
                    <tr>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('tours.show', $tour) }}" style="color:#1B6B2E; text-decoration:none; font-weight:500;">{{ $tour->date_from ? $tour->date_from->format('d.m') : '-' }}</a>
                        </td>
                        <td style="text-align:center; font-weight:600;">{{ $tour->nights ?? 0 }}</td>
                        <td>
                            @if($tour->stays->isNotEmpty())
                                @foreach($tour->stays as $stay)
                                    <div @if(!$loop->first) style="border-top:1px solid #f0f0f0; margin-top:1px; padding-top:1px;" @endif>
                                        <span class="hotel-name" style="font-size:12px;">{{ $stay->hotel->name ?? ($stay->city->name ?? '-') }}</span>
                                        @if($stay->hotel && $stay->hotel->category)
                                            <span class="stars" style="font-size:11px;">@for($i = 0; $i < $stay->hotel->category->stars; $i++)&#9733;@endfor</span>
                                        @endif
                                        <span style="font-size:10px; color:#999;">{{ $stay->nights }}n</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="hotel-name" style="font-size:12px;">{{ $tour->hotel->name ?? '-' }}</span>
                                @if($tour->hotel && $tour->hotel->category)
                                    <span class="stars" style="font-size:11px;">@for($i = 0; $i < $tour->hotel->category->stars; $i++)&#9733;@endfor</span>
                                @endif
                                @if($tour->resort)
                                    <div class="hotel-location">{{ $tour->resort->name_en }}</div>
                                @endif
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tour->availability_status == 'available' || $tour->available_seats > 5)
                                <span class="avail-dot avail-green" title="{{ __('messages.available') }}"></span>
                            @elseif($tour->available_seats > 0)
                                <span class="avail-dot avail-yellow" title="{{ __('messages.limited') }}"></span>
                            @else
                                <span class="avail-dot avail-red" title="{{ __('messages.sold_out') }}"></span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tour->mealType)
                                @php $mc = strtolower($tour->mealType->code); @endphp
                                <span class="meal-badge @if($mc == 'ai') meal-ai @elseif($mc == 'fb') meal-fb @elseif($mc == 'hb') meal-hb @elseif($mc == 'bb') meal-bb @else meal-ro @endif">{{ $tour->mealType->code }}</span>
                            @else
                                <span style="color:#ccc;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($tour->tourPrices && $tour->tourPrices->first())
                                {{ $tour->tourPrices->first()->roomType->name_en ?? __('messages.standard') }}
                            @else
                                {{ __('messages.standard') }}
                            @endif
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <span class="price-main">{{ number_format($tour->price, 0) }}</span><br>
                            <span class="price-currency">{{ $tour->currency->code ?? 'USD' }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($tour->transportType)
                                @if(str_contains(strtolower($tour->transportType->name_en), 'flight') || str_contains(strtolower($tour->transportType->name_en), 'plane'))
                                    <span class="transport-icon" title="{{ $tour->transportType->name_en }}">&#9992;</span>
                                @elseif(str_contains(strtolower($tour->transportType->name_en), 'bus'))
                                    <span class="transport-icon" title="{{ $tour->transportType->name_en }}">&#128652;</span>
                                @else
                                    <span style="font-size:10px;">{{ $tour->transportType->name_en }}</span>
                                @endif
                            @else
                                <span style="color:#ccc;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="{{ route('tours.show', $tour) }}" class="view-link" title="{{ __('messages.view_details') }}">&#128065;</a>
                            <a href="{{ route('bookings.create', $tour) }}" class="book-btn" title="{{ __('messages.book_now') }}">{{ __('messages.book') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@else
    {{-- No Results --}}
    <div class="no-results">
        <div class="no-results-title">{{ __('messages.no_tours_found') }}</div>
        <div class="no-results-text">{{ __('messages.try_adjusting_filters') }}</div>
        <a href="{{ route('search.tours') }}" class="book-btn" style="padding:5px 16px; font-size:12px;">{{ __('messages.start_new_search') }}</a>
    </div>
@endif
