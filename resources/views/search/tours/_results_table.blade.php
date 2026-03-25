<style>
.st .results-table { width: 100%; border-collapse: collapse; }
.st .results-table th {
    background: #f0f4f7; padding: 6px 8px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; color: #555; border-bottom: 2px solid #ccc;
    white-space: nowrap;
}
.st .results-table th a { color: #555; text-decoration: none; }
.st .results-table th a:hover { color: #1B6B2E; }
.st .results-table td { padding: 5px 8px; border-bottom: 1px solid #e8e8e8; font-size: 12px; vertical-align: middle; }
.st .results-table .row-even { background: #fff; }
.st .results-table .row-odd { background: #f8f9fb; }
.st .results-table .row-even:hover, .st .results-table .row-odd:hover { background: #eef6ee; }
.st .results-table .row-stop { background: #fff0f0; }
.st .results-table .row-stop:hover { background: #ffe8e8; }
.st .results-table tr { cursor: pointer; }

/* Hotel group */
.st .hotel-group-row { background: #e8f5e9; cursor: pointer; font-weight: 600; }
.st .hotel-group-row:hover { background: #d4edda; }
.st .hotel-group-row td { padding: 7px 8px; font-size: 12px; }

/* Availability bars (4 bars like aquatravel) */
.st .avail-bars { display: inline-flex; gap: 2px; }
.st .avail-bar { width: 4px; height: 14px; border-radius: 1px; }
.st .bar-y { background: #28a745; }
.st .bar-r { background: #6c757d; }
.st .bar-n { background: #dc3545; }
.st .bar-f { background: #ffc107; }

/* Meal badges */
.st .meal-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; }
.st .meal-ai { background: #e8d5f5; color: #6b21a8; }
.st .meal-fb { background: #dbeafe; color: #1d4ed8; }
.st .meal-hb { background: #d1fae5; color: #065f46; }
.st .meal-bb { background: #fef3c7; color: #92400e; }
.st .meal-ro { background: #f3f4f6; color: #4b5563; }

/* Buttons */
.st .book-btn {
    display: inline-block; padding: 3px 10px; background: #1B6B2E; color: #fff;
    font-size: 11px; font-weight: 600; border-radius: 3px; text-decoration: none;
}
.st .book-btn:hover { background: #145222; }
.st .view-link { color: #1B6B2E; text-decoration: none; font-size: 13px; margin-right: 6px; }
.st .view-link:hover { color: #145222; }

/* Hotel name - clickable */
.st .hotel-link { font-weight: 600; color: #005991; cursor: pointer; text-decoration: none; }
.st .hotel-link:hover { color: #003d66; text-decoration: underline; }
.st .hotel-loc { font-size: 10px; color: #888; }
.st .stars { color: #e8a500; font-size: 11px; letter-spacing: -1px; }

/* Price */
.st .price-val { font-size: 13px; font-weight: 700; color: #1B6B2E; white-space: nowrap; }
.st .price-stop { color: #c00; text-decoration: line-through; }
.st .stop-label { font-size: 9px; color: #c00; display: block; font-weight: 600; }

/* Tour / route */
.st .tour-name { font-size: 11px; color: #444; }
.st .route-code { font-size: 9px; color: #999; display: block; font-family: monospace; }

/* Room */
.st .room-info { font-size: 11px; color: #333; }
.st .room-pax { font-size: 10px; color: #888; }

/* Transport */
.st .transport-line { font-size: 10px; color: #555; display: flex; align-items: center; gap: 3px; white-space: nowrap; margin-bottom: 1px; }
.st .transport-line:last-child { margin-bottom: 0; }
.st .seat-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; }
.st .seat-y { background: #28a745; }
.st .seat-f { background: #ffc107; }
.st .seat-n { background: #dc3545; }

/* Departure cell */
.st .dep-date { font-weight: 500; color: #222; }
.st .dep-day { font-size: 10px; color: #888; }
.st .dep-time { font-size: 11px; color: #005991; cursor: help; font-weight: 500; }
.st .dep-time:hover { text-decoration: underline; }

/* Tooltips */
.st .tip { cursor: help; }

/* No results */
.st .no-results { padding: 40px 20px; text-align: center; }
.st .no-results-title { font-size: 14px; font-weight: 600; color: #555; margin-bottom: 8px; }
.st .no-results-text { font-size: 12px; color: #888; margin-bottom: 14px; }

/* Hotel popup tooltip */
.st .hotel-popup { display: none; position: absolute; z-index: 100; background: #fff; border: 1px solid #ccc; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 10px 14px; min-width: 250px; font-size: 12px; }
.st .hotel-popup .popup-title { font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #222; }
.st .hotel-popup .popup-row { display: flex; justify-content: space-between; padding: 2px 0; border-bottom: 1px solid #f0f0f0; }
.st .hotel-popup .popup-label { color: #888; }
.st .hotel-popup .popup-val { font-weight: 500; color: #222; }
</style>

@php
    // Helper: build route code from tour
    function tourRouteCode($tour) {
        $dep = $tour->departureCity->name_en ?? 'TAS';
        $dep = strtoupper(substr($dep, 0, 3));
        if ($tour->stays->isNotEmpty()) {
            $cities = $tour->stays->pluck('city.name_en')->filter()->map(fn($c) => strtoupper(substr($c, 0, 3)));
            return $dep . '→' . $cities->implode('→') . '→' . $dep;
        }
        $dest = $tour->country->name_en ?? '???';
        return $dep . '→' . strtoupper(substr($dest, 0, 3));
    }

    // Helper: get availability bars for a tour (4 bars)
    function availBars($tour, $seats) {
        if (!$tour->is_available || $seats <= 0) return ['n','n','n','n'];
        if ($seats > 10) return ['y','y','y','y'];
        if ($seats > 5) return ['y','y','y','f'];
        if ($seats > 2) return ['f','f','r','r'];
        return ['f','r','n','n'];
    }
@endphp

@if($tours->count() > 0)
    @if($groupByHotel)
        @php $groupedTours = $tours->groupBy('hotel_id'); @endphp

        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="date_from">Departure @if($sortBy=='date_from'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:left;">Tour</th>
                    <th style="text-align:center;">
                        <a href="#" class="sortable-header" data-sort="nights">Nights @if($sortBy=='nights'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">Hotel @if($sortBy=='hotel_name'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:center;">Avail</th>
                    <th style="text-align:center;">Meal</th>
                    <th style="text-align:left;">Room / Accom</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">Price @if($sortBy=='price'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:center;">Transport</th>
                    <th></th>
                </tr>
            </thead>

            @foreach($groupedTours as $hotelId => $hotelTours)
                @php $firstTour = $hotelTours->first(); $hotel = $firstTour->hotel; @endphp
                <tbody>
                    <tr class="hotel-group-row hotel-group-header" data-hotel-id="{{ $hotelId }}">
                        <td colspan="10">
                            <span class="toggle-icon" style="display:inline-block; width:14px;">▼</span>
                            <span class="hotel-link">{{ $hotel->name ?? 'Unknown' }}</span>
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
                            <span style="float:right; font-weight:400; font-size:11px; color:#555;">{{ $hotelTours->count() }} tours</span>
                        </td>
                    </tr>
                </tbody>
                <tbody id="hotel-group-{{ $hotelId }}">
                    @foreach($hotelTours as $tour)
                        @include('search.tours._tour_row', ['tour' => $tour, 'loop' => $loop])
                    @endforeach
                </tbody>
            @endforeach
        </table>

    @else
        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="date_from">Departure @if($sortBy=='date_from'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:left;">Tour</th>
                    <th style="text-align:center;">
                        <a href="#" class="sortable-header" data-sort="nights">Nights @if($sortBy=='nights'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:left;">
                        <a href="#" class="sortable-header" data-sort="hotel_name">Hotel @if($sortBy=='hotel_name'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:center;">Avail</th>
                    <th style="text-align:center;">Meal</th>
                    <th style="text-align:left;">Room / Accom</th>
                    <th style="text-align:right;">
                        <a href="#" class="sortable-header" data-sort="price">Price @if($sortBy=='price'){{ $sortDir=='asc'?'▲':'▼' }}@endif</a>
                    </th>
                    <th style="text-align:center;">Transport</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tours as $tour)
                    @include('search.tours._tour_row', ['tour' => $tour, 'loop' => $loop])
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
