@extends('layouts.app')

@section('content')
<style>
    .st { font-family: inherit; font-size: 12px; color: #222; }
    .st .panel { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px hsl(0 0% 88%); margin-bottom: 10px; }
    .st select, .st input[type="text"], .st input[type="number"], .st input[type="date"] {
        background: #fff; border: 1px solid #999; border-radius: 4px; padding: 1px 3px;
        font-size: 12px; height: 22px; line-height: 18px; width: 100%; box-sizing: border-box;
    }
    .st select { cursor: pointer; }
    .st table { border-collapse: collapse; border-spacing: 0; }
    .st table td, .st table th { font-size: 12px; vertical-align: middle; }
    .st .searchmodes { display: flex; gap: 0; margin-bottom: 0; }
    .st .searchmode { padding: 6px 14px; font-size: 13px; cursor: pointer; border-radius: 6px 6px 0 0; }
    .st .searchmode a { color: #1B6B2E; text-decoration: none; font-weight: 500; }
    .st .searchmode a:hover { color: #F64214; }
    .st .searchmode-active { background: #fff; font-weight: 700; box-shadow: 0 -2px 4px hsl(0 0% 88%); color: #222; }
    .st .searchmode-inactive { background: #e8edf1; color: #555; }
    .st .filter-label { display: block; font-size: 11px; color: #555; margin-bottom: 1px; font-weight: 500; }
    .st .filter-row { padding: 3px 8px; border-bottom: 1px solid #e0e0e0; }
    .st .filter-row-pair { display: flex; gap: 0; border-bottom: 1px solid #e0e0e0; }
    .st .filter-row-pair > div { flex: 1; padding: 3px 8px; }
    .st .filter-row-pair > div:first-child { border-right: 1px solid #e0e0e0; }
    .st .btn-search {
        cursor: pointer; padding: 6px 16px; color: #fff; background: #1B6B2E;
        font-size: 13px; font-weight: 700; border: 0; border-radius: 4px;
        box-shadow: 0 2px 4px hsl(0 0% 86%); transition: background .25s; width: 100%;
    }
    .st .btn-search:hover { background: #145222; }
    .st .btn-reset {
        display: block; text-align: center; padding: 4px; font-size: 11px; color: #555;
        background: #f0f0f0; border-radius: 4px; text-decoration: none; margin-top: 4px;
    }
    .st .btn-reset:hover { background: #e0e0e0; }
    .st .chk-label { display: flex; align-items: center; gap: 4px; cursor: pointer; padding: 1px 0; font-size: 12px; }
    .st .chk-label input { margin: 0; width: auto; height: auto; }
    .st .top-bar { background: #f5f7f9; border-bottom: 1px solid #e0e0e0; padding: 5px 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; }
    .st .sort-select { border: 1px solid #999; border-radius: 4px; font-size: 11px; height: 22px; padding: 0 3px; cursor: pointer; width: auto; }
    .st .sort-btn { border: 1px solid #999; border-radius: 4px; background: #fff; cursor: pointer; padding: 2px 5px; line-height: 1; }
    .st .sort-btn:hover { background: #eee; }
</style>

<div class="st">
    <div style="max-width:1100px; margin:0 auto; padding:10px;">

        {{-- Search Mode Tabs --}}
        <div class="searchmodes">
            <div class="searchmode searchmode-active">{{ __('messages.nav.tours') }}</div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.hotels') }}">{{ __('messages.nav.hotels') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.tickets') }}">{{ __('messages.nav.tickets') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.excursions') }}">{{ __('messages.nav.excursions') }}</a></div>
        </div>

        {{-- Main content: sidebar + results --}}
        <div class="panel" style="border-radius:0 8px 8px 8px; margin-bottom:0; display:flex;">

            {{-- Sidebar Filters --}}
            <div style="width:220px; min-width:220px; border-right:1px solid #e0e0e0;">
                <form action="{{ route('search.tours.search') }}" method="POST" id="search-form">
                    @csrf

                    <div class="filter-row">
                        <span class="filter-label">{{ __('messages.country') }}</span>
                        <select id="country_id" name="country_id">
                            <option value="">{{ __('messages.all_countries') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ ($currentFilters['country_id'] ?? '') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="resort-container" class="filter-row" style="display: {{ !empty($currentFilters['country_id']) ? 'block' : 'none' }}">
                        <span class="filter-label">{{ __('messages.resort') }}</span>
                        <select id="resort_id" name="resort_id">
                            <option value="">{{ __('messages.all_resorts') }}</option>
                        </select>
                    </div>

                    <div id="hotel-container" class="filter-row" style="display: {{ !empty($currentFilters['resort_id']) ? 'block' : 'none' }}">
                        <span class="filter-label">{{ __('messages.hotel') }}</span>
                        <select id="hotel_id" name="hotel_id">
                            <option value="">{{ __('messages.all_hotels') }}</option>
                        </select>
                    </div>

                    <div class="filter-row">
                        <span class="filter-label">{{ __('messages.departure_city') }}</span>
                        <select id="departure_city_id" name="departure_city_id">
                            <option value="">{{ __('messages.all_cities') }}</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ ($currentFilters['departure_city_id'] ?? '') == $city->id ? 'selected' : '' }}>
                                    {{ $city->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row-pair">
                        <div>
                            <span class="filter-label">{{ __('messages.tour_type') }}</span>
                            <select id="tour_type_id" name="tour_type_id">
                                <option value="">{{ __('messages.all_types') }}</option>
                                @foreach($tourTypes as $type)
                                    <option value="{{ $type->id }}" {{ ($currentFilters['tour_type_id'] ?? '') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <span class="filter-label">{{ __('messages.transport_type') }}</span>
                            <select id="transport_type_id" name="transport_type_id">
                                <option value="">{{ __('messages.all_transport') }}</option>
                                @foreach($transportTypes as $transport)
                                    <option value="{{ $transport->id }}" {{ ($currentFilters['transport_type_id'] ?? '') == $transport->id ? 'selected' : '' }}>
                                        {{ $transport->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="filter-row-pair">
                        <div>
                            <span class="filter-label">{{ __('messages.nights') }}</span>
                            <div style="display:flex; gap:3px; align-items:center;">
                                <input type="number" name="nights_from" placeholder="{{ __('messages.from') }}"
                                    value="{{ $currentFilters['nights_from'] ?? '' }}" style="width:50%;">
                                <span style="color:#999; font-size:10px;">-</span>
                                <input type="number" name="nights_to" placeholder="{{ __('messages.to') }}"
                                    value="{{ $currentFilters['nights_to'] ?? '' }}" style="width:50%;">
                            </div>
                        </div>
                        <div>
                            <span class="filter-label">{{ __('messages.price_range') }}</span>
                            <div style="display:flex; gap:3px; align-items:center;">
                                <input type="number" name="price_from" placeholder="{{ __('messages.from') }}"
                                    value="{{ $currentFilters['price_from'] ?? '' }}" style="width:50%;">
                                <span style="color:#999; font-size:10px;">-</span>
                                <input type="number" name="price_to" placeholder="{{ __('messages.to') }}"
                                    value="{{ $currentFilters['price_to'] ?? '' }}" style="width:50%;">
                            </div>
                        </div>
                    </div>

                    <div class="filter-row">
                        <span class="filter-label">{{ __('messages.date_range') }}</span>
                        <div style="display:flex; gap:3px; align-items:center;">
                            <input type="date" name="date_from" value="{{ $currentFilters['date_from'] ?? '' }}" style="width:50%;">
                            <span style="color:#999; font-size:10px;">-</span>
                            <input type="date" name="date_to" value="{{ $currentFilters['date_to'] ?? '' }}" style="width:50%;">
                        </div>
                    </div>

                    <div class="filter-row-pair">
                        <div>
                            <span class="filter-label">{{ __('messages.meal_type') }}</span>
                            <select id="meal_type_id" name="meal_type_id">
                                <option value="">{{ __('messages.all_meal_types') }}</option>
                                @foreach($mealTypes as $meal)
                                    <option value="{{ $meal->id }}" {{ ($currentFilters['meal_type_id'] ?? '') == $meal->id ? 'selected' : '' }}>
                                        {{ $meal->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <span class="filter-label">{{ __('messages.hotel_stars') }}</span>
                            <select id="hotel_category_id" name="hotel_category_id">
                                <option value="">{{ __('messages.all_categories') }}</option>
                                @foreach($hotelCategories as $category)
                                    <option value="{{ $category->id }}" {{ ($currentFilters['hotel_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->stars }} {{ __('messages.stars') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="filter-row" style="padding:5px 8px;">
                        <label class="chk-label">
                            <input name="is_hot" type="checkbox" value="1" {{ !empty($currentFilters['is_hot']) ? 'checked' : '' }}>
                            {{ __('messages.hot_deals') }}
                        </label>
                        <label class="chk-label">
                            <input name="instant_confirmation" type="checkbox" value="1" {{ !empty($currentFilters['instant_confirmation']) ? 'checked' : '' }}>
                            {{ __('messages.instant_confirmation') }}
                        </label>
                        <label class="chk-label">
                            <input name="no_stop_sale" type="checkbox" value="1" {{ !empty($currentFilters['no_stop_sale']) ? 'checked' : '' }}>
                            {{ __('messages.no_stop_sale') }}
                        </label>
                    </div>

                    <div style="padding:6px 8px;">
                        <button type="submit" class="btn-search">{{ __('messages.apply_filters') }}</button>
                        <a href="{{ route('search.tours') }}" class="btn-reset">{{ __('messages.reset') }}</a>
                    </div>
                </form>
            </div>

            {{-- Results Area --}}
            <div style="flex:1; min-width:0;">
                {{-- Top Bar --}}
                <div class="top-bar">
                    <span style="font-size:12px; font-weight:600;">
                        {{ __('messages.found') }} <b style="color:#1B6B2E;">{{ $tours->total() }}</b> {{ __('messages.tours') }}
                    </span>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <label class="chk-label">
                            <input id="group_by_hotel" type="checkbox" {{ $groupByHotel ? 'checked' : '' }}>
                            {{ __('messages.group_by_hotel') }}
                        </label>

                        <span style="font-size:11px; color:#555;">{{ __('messages.sort_by') }}:</span>
                        <select id="sort_by" class="sort-select">
                            <option value="date_from" {{ $sortBy == 'date_from' ? 'selected' : '' }}>{{ __('messages.date') }}</option>
                            <option value="price" {{ $sortBy == 'price' ? 'selected' : '' }}>{{ __('messages.price') }}</option>
                            <option value="nights" {{ $sortBy == 'nights' ? 'selected' : '' }}>{{ __('messages.nights') }}</option>
                            <option value="hotel_name" {{ $sortBy == 'hotel_name' ? 'selected' : '' }}>{{ __('messages.hotel') }}</option>
                        </select>
                        <button id="sort_dir_toggle" type="button" data-direction="{{ $sortDir }}" class="sort-btn" title="{{ __('messages.toggle_sort_direction') }}">
                            @if($sortDir == 'asc') ▲ @else ▼ @endif
                        </button>
                    </div>
                </div>

                {{-- Loading Spinner --}}
                <div id="loading-spinner" style="display:none;">
                    <div style="padding:30px; text-align:center;">
                        <div style="display:inline-block; width:24px; height:24px; border:2px solid #ddd; border-top-color:#1B6B2E; border-radius:50%; animation:spin 0.6s linear infinite;"></div>
                        <p style="margin-top:8px; font-size:12px; color:#888;">{{ __('messages.loading') }}...</p>
                    </div>
                </div>
                <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

                {{-- Results Table Container --}}
                <div id="results-container">
                    @include('search.tours._results_table')
                </div>

                {{-- Pagination Container --}}
                <div id="pagination-container" style="padding:8px 10px;">
                    @include('search.tours._pagination')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    const resortsByCountry = @json($resortsByCountry ?? []);
    const hotelsByResort = @json($hotelsByResort ?? []);
    const currentFilters = @json($currentFilters ?? []);

    function initCascadingDropdowns() {
        const countrySelect = document.getElementById('country_id');
        const resortSelect = document.getElementById('resort_id');
        const hotelSelect = document.getElementById('hotel_id');
        const resortContainer = document.getElementById('resort-container');
        const hotelContainer = document.getElementById('hotel-container');

        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            resortSelect.innerHTML = '<option value="">{{ __('messages.all_resorts') }}</option>';
            hotelSelect.innerHTML = '<option value="">{{ __('messages.all_hotels') }}</option>';

            if (countryId && resortsByCountry[countryId]) {
                resortsByCountry[countryId].forEach(resort => {
                    const option = document.createElement('option');
                    option.value = resort.id;
                    option.textContent = resort.name_en;
                    resortSelect.appendChild(option);
                });
                resortContainer.style.display = 'block';
            } else {
                resortContainer.style.display = 'none';
                hotelContainer.style.display = 'none';
            }
        });

        resortSelect.addEventListener('change', function() {
            const resortId = this.value;
            hotelSelect.innerHTML = '<option value="">{{ __('messages.all_hotels') }}</option>';

            if (resortId && hotelsByResort[resortId]) {
                hotelsByResort[resortId].forEach(hotel => {
                    const option = document.createElement('option');
                    option.value = hotel.id;
                    option.textContent = hotel.name;
                    hotelSelect.appendChild(option);
                });
                hotelContainer.style.display = 'block';
            } else {
                hotelContainer.style.display = 'none';
            }
        });

        if (currentFilters.country_id) {
            countrySelect.dispatchEvent(new Event('change'));
            if (currentFilters.resort_id) {
                setTimeout(() => {
                    resortSelect.value = currentFilters.resort_id;
                    resortSelect.dispatchEvent(new Event('change'));
                    if (currentFilters.hotel_id) {
                        setTimeout(() => { hotelSelect.value = currentFilters.hotel_id; }, 100);
                    }
                }, 100);
            }
        }
    }

    function getCurrentFilters() {
        const form = document.getElementById('search-form');
        const formData = new FormData(form);
        const filters = {};
        for (let [key, value] of formData.entries()) {
            if (value !== '' && key !== '_token') filters[key] = value;
        }
        return filters;
    }

    function buildQueryString(filters, sortBy, sortDir, groupByHotel, page = 1) {
        const params = new URLSearchParams();
        for (let key in filters) params.append(key, filters[key]);
        params.append('sort_by', sortBy);
        params.append('sort_dir', sortDir);
        params.append('group_by_hotel', groupByHotel ? '1' : '0');
        params.append('page', page);
        return params.toString();
    }

    function updateResults(queryString) {
        const spinner = document.getElementById('loading-spinner');
        const results = document.getElementById('results-container');
        const pagination = document.getElementById('pagination-container');

        spinner.style.display = '';
        results.style.opacity = '0.5';

        fetch('{{ route('search.tours.results') }}?' + queryString, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            results.innerHTML = data.resultsHtml;
            pagination.innerHTML = data.paginationHtml;
            window.history.pushState({}, '', window.location.pathname + '?' + queryString);
            initTableEvents();
            initPaginationEvents();
            spinner.style.display = 'none';
            results.style.opacity = '1';
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(err => {
            console.error('Error:', err);
            spinner.style.display = 'none';
            results.style.opacity = '1';
            alert('{{ __('messages.error_loading_results') }}');
        });
    }

    function initSortControls() {
        const sortBy = document.getElementById('sort_by');
        const sortDir = document.getElementById('sort_dir_toggle');
        const groupBy = document.getElementById('group_by_hotel');

        sortBy.addEventListener('change', function() {
            updateResults(buildQueryString(getCurrentFilters(), this.value, sortDir.dataset.direction, groupBy.checked));
        });

        sortDir.addEventListener('click', function() {
            const d = this.dataset.direction === 'asc' ? 'desc' : 'asc';
            this.dataset.direction = d;
            this.textContent = d === 'asc' ? '▲' : '▼';
            updateResults(buildQueryString(getCurrentFilters(), sortBy.value, d, groupBy.checked));
        });

        groupBy.addEventListener('change', function() {
            updateResults(buildQueryString(getCurrentFilters(), sortBy.value, sortDir.dataset.direction, this.checked));
        });
    }

    function initTableEvents() {
        document.querySelectorAll('.sortable-header').forEach(h => {
            h.addEventListener('click', function(e) {
                e.preventDefault();
                const col = this.dataset.sort;
                const sortBy = document.getElementById('sort_by');
                const sortDir = document.getElementById('sort_dir_toggle');
                const groupBy = document.getElementById('group_by_hotel');
                let d = 'asc';
                if (sortBy.value === col) d = sortDir.dataset.direction === 'asc' ? 'desc' : 'asc';
                sortBy.value = col;
                sortDir.dataset.direction = d;
                updateResults(buildQueryString(getCurrentFilters(), col, d, groupBy.checked));
            });
        });

        document.querySelectorAll('.hotel-group-header').forEach(h => {
            h.addEventListener('click', function() {
                const id = this.dataset.hotelId;
                const tbody = document.getElementById('hotel-group-' + id);
                const icon = this.querySelector('.toggle-icon');
                if (tbody.style.display === 'none') {
                    tbody.style.display = '';
                    icon.textContent = '▼';
                } else {
                    tbody.style.display = 'none';
                    icon.textContent = '►';
                }
            });
        });
    }

    function initPaginationEvents() {
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sortBy = document.getElementById('sort_by');
                const sortDir = document.getElementById('sort_dir_toggle');
                const groupBy = document.getElementById('group_by_hotel');
                updateResults(buildQueryString(getCurrentFilters(), sortBy.value, sortDir.dataset.direction, groupBy.checked, this.dataset.page));
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initCascadingDropdowns();
        initSortControls();
        initTableEvents();
        initPaginationEvents();
    });
})();
</script>
@endpush
