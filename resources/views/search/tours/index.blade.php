@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-day.available-date { background: #d4edda; border-color: #1B6B2E; }
    .flatpickr-day.available-date:hover { background: #1B6B2E; color: #fff; }
    .flatpickr-day.available-date.selected { background: #1B6B2E; color: #fff; border-color: #145222; }
</style>
@endpush

@section('content')
<style>
    .st { font-family: inherit; font-size: 12px; color: #222; }
    .st .panel { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px hsl(0 0% 88%); margin-bottom: 10px; }
    .st select, .st input[type="text"], .st input[type="number"], .st input[type="date"] {
        background: #fff; border: 1px solid #999; border-radius: 4px; padding: 1px 3px;
        font-size: 12px; height: 22px; line-height: 18px; width: 100%; box-sizing: border-box;
    }
    .st select { cursor: pointer; }
    .st input.hotelsearch { background: #ffc; border: 1px solid #999; border-radius: 4px; font-size: 12px; height: 20px; width: 150px; padding: 1px 3px; }
    .st .searchmodes { display: flex; gap: 0; margin-bottom: 0; }
    .st .searchmode { padding: 6px 14px; font-size: 13px; cursor: pointer; border-radius: 6px 6px 0 0; }
    .st .searchmode a { color: #1B6B2E; text-decoration: none; font-weight: 500; }
    .st .searchmode a:hover { color: #F64214; }
    .st .searchmode-active { background: #fff; font-weight: 700; box-shadow: 0 -2px 4px hsl(0 0% 88%); color: #222; }
    .st .searchmode-inactive { background: #e8edf1; color: #555; }
    .st .checklistbox {
        height: 13em; overflow-y: auto; overflow-x: hidden; border: 1px solid silver;
        border-radius: 0 0 4px 4px; background: #fff; padding: 3px;
    }
    .st .checklistbox label { display: block; padding: 1px 2px; cursor: pointer; white-space: nowrap; font-size: 12px; line-height: 18px; }
    .st .checklistbox label:hover { background: #e8f4fc; }
    .st .checklistbox label input { margin: 0 4px 1px 0; vertical-align: middle; }
    .st .filter-header {
        height: 22px; line-height: 22px; padding: 1px 5px; background: #f0f4f7;
        border: 1px solid silver; border-bottom: none; border-radius: 4px 4px 0 0;
        display: flex; align-items: center; justify-content: space-between; font-size: 11px;
    }
    .st .filter-header label { cursor: pointer; display: flex; align-items: center; gap: 3px; white-space: nowrap; }
    .st .filter-header label input { margin: 0; }
    .st .filter-header .title { font-weight: 600; color: #444; }
    .st .filter-header .any-check { color: #c00; font-size: 11px; }
    .st .filter-header .any-check input[type="checkbox"] { accent-color: #c00; }
    .st .small-list { height: 7em; }
    .st .btn-search {
        cursor: pointer; padding: 7px 28px; color: #fff; background: #1B6B2E;
        font-size: 14px; font-weight: 700; border: 0; border-radius: 4px;
        box-shadow: 0 2px 4px hsl(0 0% 86%); transition: background .25s;
    }
    .st .btn-search:hover { background: #145222; }
    .st .stars { color: #e8a500; font-size: 13px; letter-spacing: -1px; }

    /* Vertical form layout */
    .st .form-row { display: flex; flex-wrap: wrap; align-items: center; padding: 4px 8px; border-bottom: 1px solid #e8e8e8; gap: 4px 0; }
    .st .form-field { display: inline-flex; align-items: center; margin-right: 12px; }
    .st .form-label { width: 120px; text-align: right; padding-right: 8px; color: #555; font-size: 12px; white-space: nowrap; flex-shrink: 0; }
    .st .form-input { flex: 1; min-width: 100px; max-width: 200px; }
    .st .form-input select, .st .form-input input { width: 100%; }
    .st .form-input-short { flex: 0 0 70px; min-width: 70px; max-width: 90px; }
    .st .form-input-short select, .st .form-input-short input { width: 100%; }
    .st .form-separator { color: #999; font-size: 12px; margin: 0 4px; flex-shrink: 0; }
    .st .filters-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-bottom: 1px solid #e8e8e8; }
    .st .filters-row > div { padding: 8px 8px; }
    .st .filters-row > div + div { border-left: 1px solid #e8e8e8; }
    .st .inline-checks { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 18px; padding: 10px 12px; border-bottom: 1px solid #e8e8e8; }
    .st .inline-checks label { cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; white-space: nowrap; color: #444; }
    .st .inline-checks label input { margin: 0; width: auto; height: auto; }
    .st .inline-checks .separator { width: 1px; height: 14px; background: #ddd; margin: 0 6px; }
    .st .footer-row { display: flex; align-items: center; justify-content: flex-end; gap: 20px; padding: 10px 14px; }
    .st .footer-row label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 12px; color: #555; }
    .st .footer-row label input { accent-color: #c00; width: auto; height: auto; }
    .st .reset-link { color: #F64214; text-decoration: none; font-size: 12px; font-weight: 500; }
    .st .reset-link:hover { text-decoration: underline; }
</style>

<div class="st">
    <div style="max-width:1100px; margin:0 auto; padding:40px 10px 10px 10px;">

        {{-- Banner Slider --}}
        @if($banners->count())
        <div class="banner-slider" style="margin-bottom:15px; position:relative; overflow:hidden; border-radius:8px; box-shadow:0 2px 8px hsl(0 0% 88%);">
            <div class="banner-slider-track" style="display:flex; transition:transform 0.5s ease;">
                @foreach($banners as $banner)
                <div class="banner-slide" style="min-width:100%; flex-shrink:0;">
                    @if($banner->link)
                    <a href="{{ $banner->link }}" target="_blank">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}" style="width:100%; height:200px; object-fit:cover; display:block;">
                    </a>
                    @else
                    <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}" style="width:100%; height:200px; object-fit:cover; display:block;">
                    @endif
                </div>
                @endforeach
            </div>
            @if($banners->count() > 1)
            <button type="button" class="banner-prev" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.4); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center;">&#10094;</button>
            <button type="button" class="banner-next" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.4); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center;">&#10095;</button>
            <div class="banner-dots" style="position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:6px;">
                @foreach($banners as $i => $b)
                <span class="banner-dot{{ $i === 0 ? ' active' : '' }}" data-index="{{ $i }}" style="width:10px; height:10px; border-radius:50%; background:{{ $i === 0 ? '#fff' : 'rgba(255,255,255,0.5)' }}; cursor:pointer;"></span>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Search Mode Tabs --}}
        <div class="searchmodes">
            <div class="searchmode searchmode-active">{{ __('messages.nav.tours') }}</div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.hotels') }}">{{ __('messages.nav.hotels') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.tickets') }}">{{ __('messages.nav.tickets') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.excursions') }}">{{ __('messages.nav.excursions') }}</a></div>
        </div>

        <form action="{{ route('search.tours.search') }}" method="POST" id="tourSearchForm">
            @csrf

            <div class="panel" style="border-radius: 0 8px 0 0; margin-bottom:0;">

                {{-- Row 1: Departure City, Country, Tour Route --}}
                <div class="form-row">
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.departure_city') }}:</span>
                        <span class="form-input">
                            <select id="departure_city_id" name="departure_city_id" required>
                                <option value="">---</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.country') }}:</span>
                        <span class="form-input">
                            <select id="country_id" name="country_id" required>
                                <option value="">---</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.tour_route') ?? 'Tour Route' }}:</span>
                        <span class="form-input">
                            <select name="tour_route">
                                <option value="">----</option>
                                @foreach($tourRoutes as $route)
                                    <option value="{{ $route['slug'] }}">{{ $route['label'] }}</option>
                                @endforeach
                            </select>
                        </span>
                    </div>
                </div>

                {{-- Row 2: Dates and Nights --}}
                <div class="form-row">
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.departure_from') }}:</span>
                        <span class="form-input">
                            <input type="date" id="date_from" name="date_from" value="{{ date('Y-m-d') }}">
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-separator">{{ __('messages.search.departure_till') ?? 'till' }}:</span>
                        <span class="form-input">
                            <input type="date" id="date_to" name="date_to" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.nights_from') }}:</span>
                        <span class="form-input-short">
                            <select id="nights_from" name="nights_from">
                                @for($i = 3; $i <= 21; $i++)
                                    <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-separator">{{ __('messages.search.nights_to') ?? 'to' }}:</span>
                        <span class="form-input-short">
                            <select id="nights_to" name="nights_to">
                                @for($i = 3; $i <= 21; $i++)
                                    <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </span>
                    </div>
                </div>

                {{-- Row 3: Adults, Children, Currency --}}
                <div class="form-row">
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.adults') }}:</span>
                        <span class="form-input-short">
                            <select id="adults" name="adults">
                                @for($i = 1; $i <= 6; $i++)
                                    <option value="{{ $i }}" {{ $i == 2 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-label" style="width:auto;">{{ __('messages.search.children') }}:</span>
                        <span class="form-input-short" style="margin-left:8px;">
                            <select id="children" name="children">
                                @for($i = 0; $i <= 4; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.currency') }}:</span>
                        <span class="form-input-short">
                            <select id="currency_id" name="currency_id">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ $currency->code == 'USD' ? 'selected' : '' }}>{{ $currency->code }}</option>
                                @endforeach
                            </select>
                        </span>
                    </div>
                </div>

                {{-- Row 3b: Price range --}}
                <div class="form-row">
                    <div class="form-field">
                        <span class="form-label">{{ __('messages.search.price_from') ?? 'Price from' }}:</span>
                        <span class="form-input-short">
                            <input type="number" name="price_from" min="0" placeholder="">
                        </span>
                    </div>
                    <div class="form-field">
                        <span class="form-separator">{{ __('messages.search.price_to') ?? 'to' }}:</span>
                        <span class="form-input-short">
                            <input type="number" name="price_to" min="0" placeholder="">
                        </span>
                    </div>
                </div>

                {{-- Child Ages (dynamic, hidden by default) --}}
                <div id="childAgesRow" style="display:none; padding:4px 6px; border-bottom:1px solid #e8e8e8;">
                    <span style="color:#555; font-size:11px;">{{ __('messages.search.child_age') }}:</span>
                    <span id="childAges"></span>
                </div>

                {{-- Row 4: 4-Column Filter Panel: Resorts | Star Rating | Hotels | Meal Types --}}
                <div class="filters-row">
                    {{-- Cities --}}
                    <div>
                        <div class="filter-header">
                            <span class="title">{{ __('messages.search.cities') ?? 'Cities' }}</span>
                            <label class="any-check">
                                <input type="checkbox" id="resorts_all" checked> {{ __('messages.search.all') ?? 'All' }}
                            </label>
                        </div>
                        <div id="resortsContainer" class="checklistbox">
                            <label style="color:#999; text-align:center; padding:20px 0;">{{ __('messages.search.select_destination') }}</label>
                        </div>
                    </div>

                    {{-- Star Rating --}}
                    <div>
                        <div class="filter-header">
                            <span class="title">{{ __('messages.search.star_rating') ?? 'Stars' }}</span>
                        </div>
                        <div class="checklistbox">
                            @foreach($hotelCategories as $category)
                                <label>
                                    <input type="checkbox" name="hotel_category_ids[]" value="{{ $category->id }}">
                                    <span class="stars">@for($i = 0; $i < $category->stars; $i++)&#9733;@endfor</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hotels --}}
                    <div>
                        <div class="filter-header">
                            <span class="title">{{ __('messages.search.hotels') ?? 'Hotels' }}</span>
                            <span style="display:flex; align-items:center; gap:6px;">
                                <input type="text" id="hotelSearchInput" class="hotelsearch" placeholder="{{ __('messages.search.search_hotel') ?? 'Search...' }}">
                                <label class="any-check">
                                    <input type="checkbox" id="hotels_all" checked> {{ __('messages.search.all') ?? 'All' }}
                                </label>
                            </span>
                        </div>
                        <div id="hotelsContainer" class="checklistbox">
                            <label style="color:#999; text-align:center; padding:20px 0;">{{ __('messages.search.select_resorts') ?? 'Select a country first' }}</label>
                        </div>
                    </div>

                    {{-- Meal Types --}}
                    <div>
                        <div class="filter-header">
                            <span class="title">{{ __('messages.search.meal_types') ?? 'Meals' }}</span>
                            <label class="any-check">
                                <input type="checkbox" id="meals_all" checked> {{ __('messages.search.all') ?? 'All' }}
                            </label>
                        </div>
                        <div class="checklistbox">
                            @foreach($mealTypes as $meal)
                                <label>
                                    <input type="checkbox" name="meal_type_ids[]" value="{{ $meal->id }}" class="meal-checkbox">
                                    {{ $meal->code }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Row 5: Inline checkboxes — filters + transport types --}}
                <div class="inline-checks">
                    <label><input type="checkbox" name="instant_confirmation" value="1"> {{ __('messages.search.instant_confirmation') }}</label>
                    <label><input type="checkbox" name="no_stop_sale" value="1"> {{ __('messages.search.no_stop_sale') }}</label>
                    <label><input type="checkbox" name="with_flight" value="1"> {{ __('messages.search.with_flight') }}</label>
                    <label><input type="checkbox" name="direct_flight" value="1"> {{ __('messages.search.direct_flight') }}</label>
                    <label><input type="checkbox" name="is_hot" value="1"> {{ __('messages.search.hot_deals_only') }}</label>
                    <span class="separator"></span>
                    <label><input type="checkbox" name="transport_type_ids[]" value="1"> ✈ Air</label>
                </div>

            </div>

            {{-- Row 6: Group by hotel | Reset | Search --}}
            <div class="panel" style="border-radius:0 0 8px 8px; margin-bottom:0;">
                <div class="footer-row">
                    <label>
                        <input type="checkbox" name="group_by_hotel" value="1">
                        {{ __('messages.search.group_by_hotel') ?? 'Group by hotel' }}
                    </label>
                    <a href="javascript:void(0)" onclick="resetForm()" class="reset-link">{{ __('messages.search.reset') ?? 'Reset' }}</a>
                    <button type="submit" class="btn-search">
                        {{ __('messages.search.search_button') }}
                    </button>
                </div>
            </div>

        </form>

        {{-- Results Area (appears after search) --}}
        <div id="resultsArea" style="display:none; margin-top:15px;">
            {{-- Results top bar --}}
            <div class="panel" style="border-radius:8px 8px 0 0; margin-bottom:0; padding:6px 10px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px; background:#f5f7f9;">
                <span style="font-size:12px; font-weight:600;">
                    {{ __('messages.found') }} <b id="resultCount" style="color:#1B6B2E;">0</b> {{ __('messages.tours') }}
                </span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <label style="cursor:pointer; display:flex; align-items:center; gap:4px; font-size:12px;">
                        <input id="group_by_hotel" type="checkbox" style="width:auto; height:auto;"> {{ __('messages.group_by_hotel') }}
                    </label>
                    <span style="font-size:11px; color:#555;">{{ __('messages.sort_by') }}:</span>
                    <select id="sort_by" style="border:1px solid #999; border-radius:4px; font-size:11px; height:22px; padding:0 3px; cursor:pointer; width:auto;">
                        <option value="price">{{ __('messages.price') }}</option>
                        <option value="date_from">{{ __('messages.date') }}</option>
                        <option value="nights">{{ __('messages.nights') }}</option>
                        <option value="hotel_name">{{ __('messages.hotel') }}</option>
                    </select>
                    <button id="sort_dir_toggle" type="button" data-direction="asc" style="border:1px solid #999; border-radius:4px; background:#fff; cursor:pointer; padding:2px 5px; line-height:1;" title="{{ __('messages.toggle_sort_direction') }}">&#9650;</button>
                </div>
            </div>

            {{-- Flights Monitor Popup --}}
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

            {{-- Loading Spinner --}}
            <div id="loading-spinner" style="display:none;">
                <div class="panel" style="border-radius:0; margin-bottom:0; padding:30px; text-align:center;">
                    <div style="display:inline-block; width:24px; height:24px; border:2px solid #ddd; border-top-color:#1B6B2E; border-radius:50%; animation:spin 0.6s linear infinite;"></div>
                    <p style="margin-top:8px; font-size:12px; color:#888;">{{ __('messages.loading') ?? 'Loading' }}...</p>
                </div>
            </div>
            <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

            {{-- Results Table --}}
            <div id="results-container" class="panel" style="border-radius:0; margin-bottom:0;"></div>

            {{-- Pagination --}}
            <div id="pagination-container" class="panel" style="border-radius:0 0 8px 8px; padding:8px 10px;"></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const hotelsByCity = @json($hotelsByCity ?? []);
    const tourRouteFilters = @json(collect($tourRoutes)->keyBy('slug')->map(fn($r) => $r['filters']));
    const departureDates = @json($departureDates ?? []);

    // Cities that have hotels (for route auto-fill and country filter)
    @php
        $citiesWithHotels = \App\Models\City::whereIn('id', \App\Models\Hotel::where('is_active', true)->whereNotNull('city_id')->distinct()->pluck('city_id'))
            ->where('is_active', true)
            ->get(['id', 'name_en', 'country_id']);
    @endphp
    const citiesWithHotels = @json($citiesWithHotels);

    // Initialize flatpickr with green departure dates
    (function() {
        const greenDates = new Set(departureDates);

        const fpConfig = {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd.m.Y',
            minDate: 'today',
            disableMobile: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const d = dayElem.dateObj;
                const dateStr = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                if (greenDates.has(dateStr)) {
                    dayElem.classList.add('available-date');
                }
            }
        };

        const fpFrom = flatpickr('#date_from', {
            ...fpConfig,
            defaultDate: document.getElementById('date_from').value || 'today',
            onChange: function(sel) {
                if (sel[0] && fpTo) fpTo.set('minDate', sel[0]);
            }
        });

        const fpTo = flatpickr('#date_to', {
            ...fpConfig,
            defaultDate: document.getElementById('date_to').value || null,
        });

        // Expose for route auto-fill
        window._fpFrom = fpFrom;
        window._fpTo = fpTo;
    })();

    // Tour Route → auto-fill all filters
    document.querySelector('select[name="tour_route"]').addEventListener('change', function() {
        const f = tourRouteFilters[this.value];
        if (!f) return;

        // Departure city
        if (f.departure_city_id) {
            document.getElementById('departure_city_id').value = f.departure_city_id;
        }

        // Country — set visually only (route auto-fill handles cities below)
        if (f.country_id) {
            document.getElementById('country_id').value = f.country_id;
        }

        // Dates (use flatpickr setDate for proper display)
        if (f.date_from && window._fpFrom) window._fpFrom.setDate(f.date_from);
        if (f.date_to && window._fpTo) window._fpTo.setDate(f.date_to);

        // Nights
        if (f.nights_from) document.getElementById('nights_from').value = f.nights_from;
        if (f.nights_to) document.getElementById('nights_to').value = f.nights_to;

        // Render cities from route — show all cities in this tour's stays
        const rc = document.getElementById('resortsContainer');
        const hc = document.getElementById('hotelsContainer');
        let cityHtml = '';
        const routeCityIds = (f.city_ids || []).map(Number);

        // Show cities from the route + their hotels
        citiesWithHotels.forEach(c => {
            if (routeCityIds.includes(Number(c.id))) {
                cityHtml += `<label><input type="checkbox" name="city_ids[]" value="${c.id}" class="resort-checkbox" checked> ${c.name_en}</label>`;
            }
        });
        rc.innerHTML = cityHtml || '<label style="color:#999;padding:20px 0;">No cities</label>';
        document.querySelectorAll('.resort-checkbox').forEach(cb => cb.addEventListener('change', updateHotelsByCity));
        document.getElementById('resorts_all').checked = true;
        updateHotelsByCity();

        // Check only relevant hotels
        setTimeout(() => {
            if (f.hotel_ids && f.hotel_ids.length) {
                document.querySelectorAll('.hotel-checkbox').forEach(cb => {
                    cb.checked = f.hotel_ids.includes(parseInt(cb.value));
                });
                updateHotelsAllCheckbox();
            }
        }, 50);
    });

    // Country → Cities (show cities that have hotels)
    document.getElementById('country_id').addEventListener('change', function() {
        const countryId = this.value;
        const rc = document.getElementById('resortsContainer');
        if (!countryId) {
            rc.innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">{{ __("messages.search.select_destination") }}</label>';
            document.getElementById('hotelsContainer').innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">Select a country first</label>';
            return;
        }
        const cities = citiesWithHotels.filter(c => c.country_id == countryId);
        if (!cities.length) { rc.innerHTML = '<label style="color:#999;padding:20px 0;">No cities</label>'; return; }
        rc.innerHTML = cities.map(c => `
            <label><input type="checkbox" name="city_ids[]" value="${c.id}" class="resort-checkbox"> ${c.name_en}</label>
        `).join('');
        document.querySelectorAll('.resort-checkbox').forEach(cb => cb.addEventListener('change', updateHotelsByCity));
        updateResortsAllCheckbox();
    });

    // Cities → Hotels
    function updateHotelsByCity() {
        const sel = Array.from(document.querySelectorAll('.resort-checkbox:checked')).map(cb => cb.value);
        const hc = document.getElementById('hotelsContainer');
        if (!sel.length) { hc.innerHTML = '<label style="color:#999;padding:20px 0;">Select cities</label>'; return; }

        let html = '';
        const allCityNames = @json($cities->pluck('name_en', 'id'));
        sel.forEach(cityId => {
            const hotels = hotelsByCity[cityId] || [];
            const cityName = allCityNames[cityId] || 'City ' + cityId;
            if (sel.length > 1 && hotels.length) {
                html += `<div style="font-weight:600;font-size:11px;color:#1B6B2E;padding:4px 4px 1px;border-bottom:1px solid #e0e0e0;margin-top:3px;">${cityName}</div>`;
            }
            hotels.forEach(h => {
                const s = h.category ? '★'.repeat(h.category.stars) : '';
                html += `<label class="hotel-item"><input type="checkbox" name="hotel_ids[]" value="${h.id}" class="hotel-checkbox"> ${h.name} <span class="stars">${s}</span></label>`;
            });
        });
        hc.innerHTML = html || '<label style="color:#999;padding:20px 0;">No hotels</label>';
        updateHotelsAllCheckbox();
    }

    // Legacy: keep updateHotels for backward compat with resort-based code
    function updateHotels() { updateHotelsByCity(); }

    // "All" toggles
    document.getElementById('resorts_all').addEventListener('change', function() {
        document.querySelectorAll('.resort-checkbox').forEach(cb => cb.checked = this.checked);
        updateHotelsByCity();
    });
    function updateResortsAllCheckbox() {
        const cbs = document.querySelectorAll('.resort-checkbox');
        document.getElementById('resorts_all').checked = !cbs.length || Array.from(cbs).every(cb => cb.checked);
    }

    document.getElementById('hotels_all').addEventListener('change', function() {
        document.querySelectorAll('.hotel-checkbox').forEach(cb => cb.checked = this.checked);
    });
    function updateHotelsAllCheckbox() {
        const cbs = document.querySelectorAll('.hotel-checkbox');
        document.getElementById('hotels_all').checked = !cbs.length || Array.from(cbs).every(cb => cb.checked);
    }

    document.getElementById('meals_all').addEventListener('change', function() {
        document.querySelectorAll('.meal-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Child ages
    document.getElementById('children').addEventListener('change', function() {
        const n = parseInt(this.value), row = document.getElementById('childAgesRow'), div = document.getElementById('childAges');
        if (n > 0) {
            row.style.display = '';
            div.innerHTML = Array.from({length: n}, (_, i) =>
                `<select name="child_ages[]" style="width:45px; display:inline-block; margin:0 3px;">${Array.from({length: 18}, (_, j) => `<option value="${j}" ${j===4?'selected':''}>${j}</option>`).join('')}</select>`
            ).join('');
        } else { row.style.display = 'none'; div.innerHTML = ''; }
    });

    // Hotel search filter
    document.getElementById('hotelSearchInput').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#hotelsContainer .hotel-item').forEach(l => l.style.display = l.textContent.toLowerCase().includes(q) ? '' : 'none');
    });

    // Reset
    function resetForm() {
        document.getElementById('tourSearchForm').reset();
        document.getElementById('childAgesRow').style.display = 'none';
        document.getElementById('childAges').innerHTML = '';
        document.getElementById('resortsContainer').innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">{{ __("messages.search.select_destination") }}</label>';
        document.getElementById('hotelsContainer').innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">{{ __("messages.search.select_resorts") ?? "Select a country first" }}</label>';
        document.getElementById('resultsArea').style.display = 'none';
    }

    // ---- AJAX Search ----
    const form = document.getElementById('tourSearchForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        doSearch();
    });

    function getFormData() {
        const fd = new FormData(form);
        const params = new URLSearchParams();
        for (let [k, v] of fd.entries()) {
            if (v !== '' && k !== '_token') params.append(k, v);
        }
        params.append('sort_by', document.getElementById('sort_by').value);
        params.append('sort_dir', document.getElementById('sort_dir_toggle').dataset.direction);
        params.append('group_by_hotel', document.getElementById('group_by_hotel').checked ? '1' : '0');
        return params;
    }

    function doSearch(page) {
        const params = getFormData();
        if (page) params.set('page', page);

        const area = document.getElementById('resultsArea');
        const spinner = document.getElementById('loading-spinner');
        const results = document.getElementById('results-container');
        const pagination = document.getElementById('pagination-container');

        area.style.display = '';
        spinner.style.display = '';
        results.style.opacity = '0.4';

        fetch('{{ route("search.tours.results") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            results.innerHTML = data.resultsHtml;
            pagination.innerHTML = data.paginationHtml;
            document.getElementById('resultCount').textContent = data.total;
            spinner.style.display = 'none';
            results.style.opacity = '1';
            initResultEvents();
            area.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(err => {
            console.error('Search error:', err);
            spinner.style.display = 'none';
            results.style.opacity = '1';
        });
    }

    // Sort controls
    document.getElementById('sort_by').addEventListener('change', function() { doSearch(); });
    document.getElementById('sort_dir_toggle').addEventListener('click', function() {
        const d = this.dataset.direction === 'asc' ? 'desc' : 'asc';
        this.dataset.direction = d;
        this.textContent = d === 'asc' ? '\u25B2' : '\u25BC';
        doSearch();
    });
    document.getElementById('group_by_hotel').addEventListener('change', function() { doSearch(); });

    // Row click → navigate, but NOT if clicking a link, button, or interactive element
    document.addEventListener('click', function(e) {
        const row = e.target.closest('tr[data-href]');
        if (!row) return;
        if (e.target.closest('a, button, .stats-btn, .book-btn, .view-link, .hotel-link')) return;
        window.location = row.dataset.href;
    });

    // Flights Monitor popup
    window.openFM = function(el) {
        event.stopPropagation();
        const d = JSON.parse(el.dataset.fm);
        document.getElementById('fm-tour-info').innerHTML =
            '<div><div class="fm-label">Departure</div><div class="fm-val">' + d.date + '</div></div>' +
            '<div><div class="fm-label">Hotel</div><div class="fm-val">' + d.hotel + '</div></div>' +
            '<div><div class="fm-label">Nights</div><div class="fm-val">' + d.nights + '</div></div>' +
            '<div><div class="fm-label">Price</div><div class="fm-val">' + d.price + '</div></div>';
        var html = '';
        (d.flights || []).forEach(function(f) {
            var econSeat = f.seats > 5 ? 'green' : (f.seats > 0 ? 'yellow' : 'red');
            var econLabel = f.seats > 5 ? 'MANY' : (f.seats > 0 ? 'FEW (' + f.seats + ')' : 'NO');
            html += '<div class="fm-flight">' +
                '<div class="fm-flight-header"><span>' + f.date + ' ' + f.from_city + ' &rarr; ' + f.to_city + '</span><span style="font-weight:400;color:#888;">' + f.direction + '</span></div>' +
                '<div class="fm-flight-body">' +
                '<div class="fm-flight-route"><span class="fm-airport-code">' + f.from + '</span><span class="fm-arrow">&rarr;</span><span class="fm-airport-code">' + f.to + '</span></div>' +
                '<div class="fm-flight-details">' + f.airline + ' &bull; ' + f.flight_number + '<br>' + f.dep_time + ' ' + f.from + ' &mdash; ' + f.arr_time + ' ' + f.to + '</div>' +
                '<div class="fm-seats-row">' +
                '<div class="fm-seat-class"><span class="fm-seat-dot fm-seat-' + econSeat + '"></span><span>Econom</span><span class="fm-seat-label fm-seat-' + econSeat + '-text">' + econLabel + '</span></div>' +
                '<div class="fm-seat-class"><span class="fm-seat-dot fm-seat-gray"></span><span>Business</span><span class="fm-seat-label fm-seat-gray-text">N/A</span></div>' +
                '</div></div></div>';
        });
        if (!d.flights || !d.flights.length) {
            html = '<div style="padding:10px;color:#888;text-align:center;">No flight data available</div>';
        }
        document.getElementById('fm-flights').innerHTML = html;
        document.getElementById('fm-overlay').style.display = 'block';
        document.getElementById('fm-popup').style.display = 'block';
    };
    window.closeFM = function() {
        document.getElementById('fm-overlay').style.display = 'none';
        document.getElementById('fm-popup').style.display = 'none';
    };
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeFM(); });

    // Result table + pagination events
    function initResultEvents() {
        document.querySelectorAll('.sortable-header').forEach(h => {
            h.addEventListener('click', function(e) {
                e.preventDefault();
                const col = this.dataset.sort;
                const sb = document.getElementById('sort_by');
                const sd = document.getElementById('sort_dir_toggle');
                let d = 'asc';
                if (sb.value === col) d = sd.dataset.direction === 'asc' ? 'desc' : 'asc';
                sb.value = col; sd.dataset.direction = d;
                doSearch();
            });
        });
        document.querySelectorAll('.hotel-group-header').forEach(h => {
            h.addEventListener('click', function() {
                const tbody = document.getElementById('hotel-group-' + this.dataset.hotelId);
                const icon = this.querySelector('.toggle-icon');
                if (tbody.style.display === 'none') { tbody.style.display = ''; icon.textContent = '\u25BC'; }
                else { tbody.style.display = 'none'; icon.textContent = '\u25BA'; }
            });
        });
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                doSearch(this.dataset.page);
            });
        });
    }

    // Banner Slider
    (function() {
        const slider = document.querySelector('.banner-slider');
        if (!slider) return;
        const track = slider.querySelector('.banner-slider-track');
        const slides = slider.querySelectorAll('.banner-slide');
        const dots = slider.querySelectorAll('.banner-dot');
        if (slides.length < 2) return;

        let current = 0, timer;

        function goTo(i) {
            current = (i + slides.length) % slides.length;
            track.style.transform = `translateX(-${current * 100}%)`;
            dots.forEach((d, j) => d.style.background = j === current ? '#fff' : 'rgba(255,255,255,0.5)');
        }

        function autoPlay() { timer = setInterval(() => goTo(current + 1), 5000); }
        function resetTimer() { clearInterval(timer); autoPlay(); }

        slider.querySelector('.banner-prev').addEventListener('click', () => { goTo(current - 1); resetTimer(); });
        slider.querySelector('.banner-next').addEventListener('click', () => { goTo(current + 1); resetTimer(); });
        dots.forEach(d => d.addEventListener('click', () => { goTo(+d.dataset.index); resetTimer(); }));

        autoPlay();
    })();
</script>
@endpush
@endsection
