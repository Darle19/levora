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

/* Flights monitor popup */
.st .fm-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:999; }
.st .fm-popup {
    position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:1000;
    background:#fff; border:1px solid #bbb; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.25);
    min-width:500px; max-width:700px; max-height:80vh; overflow-y:auto; display:none;
}
.st .fm-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 14px; background:#f0f4f7; border-bottom:1px solid #ddd;
    border-radius:8px 8px 0 0;
}
.st .fm-header h3 { font-size:13px; font-weight:700; color:#333; margin:0; }
.st .fm-close { cursor:pointer; font-size:18px; color:#888; border:none; background:none; padding:0 4px; }
.st .fm-close:hover { color:#c00; }
.st .fm-body { padding:12px 14px; }
.st .fm-tour-info {
    display:grid; grid-template-columns:repeat(4,1fr); gap:4px 12px;
    padding:8px 10px; background:#f8f9fb; border-radius:4px; margin-bottom:10px;
    font-size:11px;
}
.st .fm-tour-info .fm-label { color:#888; font-size:10px; text-transform:uppercase; }
.st .fm-tour-info .fm-val { font-weight:600; color:#222; }
.st .fm-legend {
    display:flex; gap:12px; align-items:center; margin-bottom:10px;
    font-size:10px; color:#666; padding:4px 0; border-bottom:1px solid #eee;
}
.st .fm-legend-dot { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:3px; vertical-align:middle; }
.st .fm-flight {
    border:1px solid #e0e0e0; border-radius:6px; margin-bottom:8px; overflow:hidden;
}
.st .fm-flight-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:6px 10px; background:#f0f4f7; font-size:11px; font-weight:600; color:#333;
}
.st .fm-flight-body { padding:8px 10px; }
.st .fm-flight-route {
    display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:12px;
}
.st .fm-airport-code { font-weight:700; font-size:14px; color:#005991; }
.st .fm-arrow { color:#999; font-size:16px; }
.st .fm-flight-details { font-size:11px; color:#555; margin-bottom:6px; }
.st .fm-seats-row { display:flex; gap:16px; }
.st .fm-seat-class { display:flex; align-items:center; gap:4px; font-size:11px; }
.st .fm-seat-dot { display:inline-block; width:10px; height:10px; border-radius:2px; }
.st .fm-seat-green { background:#28a745; }
.st .fm-seat-yellow { background:#ffc107; }
.st .fm-seat-red { background:#dc3545; }
.st .fm-seat-gray { background:#adb5bd; }
.st .fm-seat-label { font-weight:600; font-size:10px; text-transform:uppercase; }
.st .fm-seat-green-text { color:#28a745; }
.st .fm-seat-yellow-text { color:#b8860b; }
.st .fm-seat-red-text { color:#dc3545; }
.st .fm-seat-gray-text { color:#6c757d; }

/* Stats icon button */
.st .stats-btn { cursor:pointer; font-size:13px; color:#005991; border:none; background:none; padding:2px; }
.st .stats-btn:hover { color:#003d66; }
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
                    <th style="width:24px;"></th>
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
                        <td colspan="11">
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
                    <th style="width:24px;"></th>
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
{{-- Flights Monitor Popup (shared, populated by JS) --}}
<div class="fm-overlay" id="fm-overlay" onclick="closeFM()"></div>
<div class="fm-popup" id="fm-popup">
    <div class="fm-header">
        <h3>Flights Monitor</h3>
        <button class="fm-close" onclick="closeFM()">&times;</button>
    </div>
    <div class="fm-body">
        <div class="fm-tour-info" id="fm-tour-info"></div>
        <div class="fm-legend">
            <span><span class="fm-legend-dot" style="background:#28a745;"></span> many</span>
            <span><span class="fm-legend-dot" style="background:#ffc107;"></span> few</span>
            <span><span class="fm-legend-dot" style="background:#dc3545;"></span> no</span>
            <span><span class="fm-legend-dot" style="background:#adb5bd;"></span> on request</span>
        </div>
        <div id="fm-flights"></div>
    </div>
</div>

<script>
function openFM(el) {
    event.stopPropagation();
    const d = JSON.parse(el.dataset.fm);
    // Tour info
    document.getElementById('fm-tour-info').innerHTML =
        `<div><div class="fm-label">Departure</div><div class="fm-val">${d.date}</div></div>` +
        `<div><div class="fm-label">Hotel</div><div class="fm-val">${d.hotel}</div></div>` +
        `<div><div class="fm-label">Nights</div><div class="fm-val">${d.nights}</div></div>` +
        `<div><div class="fm-label">Price</div><div class="fm-val">${d.price}</div></div>`;
    // Flights
    let html = '';
    (d.flights || []).forEach(f => {
        const econSeat = f.seats > 5 ? 'green' : (f.seats > 0 ? 'yellow' : 'red');
        const econLabel = f.seats > 5 ? 'MANY' : (f.seats > 0 ? 'FEW (' + f.seats + ')' : 'NO');
        html += `<div class="fm-flight">
            <div class="fm-flight-header">
                <span>${f.date} ${f.from_city} &rarr; ${f.to_city}</span>
                <span style="font-weight:400;color:#888;">${f.direction}</span>
            </div>
            <div class="fm-flight-body">
                <div class="fm-flight-route">
                    <span class="fm-airport-code">${f.from}</span>
                    <span class="fm-arrow">&rarr;</span>
                    <span class="fm-airport-code">${f.to}</span>
                </div>
                <div class="fm-flight-details">
                    ${f.airline} &bull; ${f.flight_number}<br>
                    ${f.dep_time} ${f.from} &mdash; ${f.arr_time} ${f.to}
                </div>
                <div class="fm-seats-row">
                    <div class="fm-seat-class">
                        <span class="fm-seat-dot fm-seat-${econSeat}"></span>
                        <span>Econom</span>
                        <span class="fm-seat-label fm-seat-${econSeat}-text">${econLabel}</span>
                    </div>
                    <div class="fm-seat-class">
                        <span class="fm-seat-dot fm-seat-gray"></span>
                        <span>Business</span>
                        <span class="fm-seat-label fm-seat-gray-text">N/A</span>
                    </div>
                </div>
            </div>
        </div>`;
    });
    if (!d.flights || !d.flights.length) {
        html = '<div style="padding:10px;color:#888;text-align:center;">No flight data available</div>';
    }
    document.getElementById('fm-flights').innerHTML = html;
    document.getElementById('fm-overlay').style.display = 'block';
    document.getElementById('fm-popup').style.display = 'block';
}
function closeFM() {
    document.getElementById('fm-overlay').style.display = 'none';
    document.getElementById('fm-popup').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFM(); });
</script>

@else
    <div class="no-results">
        <div class="no-results-title">{{ __('messages.no_tours_found') }}</div>
        <div class="no-results-text">{{ __('messages.try_adjusting_filters') }}</div>
        <a href="{{ route('search.tours') }}" class="book-btn" style="padding:5px 16px; font-size:12px;">{{ __('messages.start_new_search') }}</a>
    </div>
@endif
