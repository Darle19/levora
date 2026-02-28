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
    .st input.hotelsearch { background: #ffc; border: 1px solid #999; border-radius: 4px; font-size: 12px; height: 20px; width: 150px; padding: 1px 3px; }
    .st table { border-collapse: collapse; border-spacing: 0; }
    .st table td, .st table th { font-size: 12px; vertical-align: middle; }
    .st .searchmodes { display: flex; gap: 0; margin-bottom: 0; }
    .st .searchmode { padding: 6px 14px; font-size: 13px; cursor: pointer; border-radius: 6px 6px 0 0; }
    .st .searchmode a { color: #1B6B2E; text-decoration: none; font-weight: 500; }
    .st .searchmode a:hover { color: #F64214; }
    .st .searchmode-active { background: #fff; font-weight: 700; box-shadow: 0 -2px 4px hsl(0 0% 88%); color: #222; }
    .st .searchmode-inactive { background: #e8edf1; color: #555; }
    .st .dir-label { width: 130px; text-align: right; padding-right: 8px; color: #555; white-space: nowrap; }
    .st .desc-label { text-align: right; padding-right: 6px; color: #555; white-space: nowrap; }
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
</style>

<div class="st">
    <div style="max-width:1100px; margin:0 auto; padding:40px 10px 10px 10px;">

        {{-- Search Mode Tabs --}}
        <div class="searchmodes">
            <div class="searchmode searchmode-active">{{ __('messages.nav.tours') }}</div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.hotels') }}">{{ __('messages.nav.hotels') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.tickets') }}">{{ __('messages.nav.tickets') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.excursions') }}">{{ __('messages.nav.excursions') }}</a></div>
            <div class="searchmode searchmode-inactive"><a href="{{ route('search.cruises') }}">{{ __('messages.nav.cruises') }}</a></div>
        </div>

        <form action="{{ route('search.tours.search') }}" method="POST" id="tourSearchForm">
            @csrf

            {{-- Direction Panel --}}
            <div class="panel" style="border-radius: 0 8px 0 0; margin-bottom:0;">
                <table style="width:100%">
                    <tr>
                        <td style="width:50%; vertical-align:top;">
                            <table style="width:100%">
                                <tr>
                                    <td class="dir-label">{{ __('messages.search.departure_city') }}</td>
                                    <td style="padding:4px 8px;">
                                        <select id="departure_city_id" name="departure_city_id" required>
                                            <option value="">—</option>
                                            @foreach($cities as $city)
                                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="dir-label">{{ __('messages.search.country') }}</td>
                                    <td style="padding:4px 8px;">
                                        <select id="country_id" name="country_id" required>
                                            <option value="">—</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%; vertical-align:top;">
                            <table style="width:100%">
                                <tr>
                                    <td class="dir-label" style="width:50px;">{{ __('messages.search.tour_type') ?? 'тур' }}</td>
                                    <td style="padding:4px 8px;">
                                        <select name="program_type_id">
                                            <option value="">----</option>
                                            @foreach($programTypes as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Date / Nights / Tourists / Price Panel --}}
            <div class="panel" style="border-radius:0; margin-bottom:0; border-top:1px solid #ddd;">
                <table style="width:100%">
                    <tr>
                        <td style="width:50%;">
                            <table style="width:100%">
                                <tr>
                                    <td class="desc-label" style="width:70px;">{{ __('messages.search.departure_from') }}</td>
                                    <td style="padding:3px 4px; width:130px;">
                                        <input type="date" id="date_from" name="date_from" value="{{ date('Y-m-d') }}">
                                    </td>
                                    <td class="desc-label" style="width:70px;">{{ __('messages.search.nights_from') }}</td>
                                    <td style="padding:3px 4px; width:55px;">
                                        <select id="nights_from" name="nights_from">
                                            @for($i = 3; $i <= 21; $i++)
                                                <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="desc-label">{{ __('messages.search.departure_till') }}</td>
                                    <td style="padding:3px 4px;">
                                        <input type="date" id="date_to" name="date_to" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                                    </td>
                                    <td class="desc-label">{{ __('messages.search.nights_to') ?? 'до' }}</td>
                                    <td style="padding:3px 4px;">
                                        <select id="nights_to" name="nights_to">
                                            @for($i = 3; $i <= 21; $i++)
                                                <option value="{{ $i }}" {{ $i == 7 ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;">
                            <table style="width:100%">
                                <tr>
                                    <td class="desc-label" style="width:80px;">{{ __('messages.search.adults') }}</td>
                                    <td style="padding:3px 4px; width:55px;">
                                        <select id="adults" name="adults">
                                            @for($i = 1; $i <= 6; $i++)
                                                <option value="{{ $i }}" {{ $i == 2 ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td class="desc-label" style="width:45px;">{{ __('messages.search.currency') }}</td>
                                    <td style="padding:3px 4px; width:60px;">
                                        <select id="currency_id" name="currency_id">
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->id }}" {{ $currency->code == 'USD' ? 'selected' : '' }}>{{ $currency->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="desc-label" style="width:20px;">{{ __('messages.search.price_from') ?? 'от' }}</td>
                                    <td style="padding:3px 4px; width:70px;">
                                        <input type="number" name="price_from" min="0" placeholder="" style="width:100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="desc-label">{{ __('messages.search.children') }}</td>
                                    <td style="padding:3px 4px;">
                                        <select id="children" name="children">
                                            @for($i = 0; $i <= 4; $i++)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td class="desc-label" colspan="2"></td>
                                    <td class="desc-label">{{ __('messages.search.price_to') ?? 'до' }}</td>
                                    <td style="padding:3px 4px;">
                                        <input type="number" name="price_to" min="0" placeholder="" style="width:100%;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Child Ages (dynamic, hidden by default) --}}
            <div id="childAgesRow" class="panel" style="border-radius:0; margin-bottom:0; border-top:1px solid #ddd; display:none; padding:4px 10px;">
                <span style="color:#555;">{{ __('messages.search.child_age') }}:</span>
                <span id="childAges"></span>
            </div>

            {{-- 4-Column Filter Panel: Resorts | Stars+Category | Hotels | Meals --}}
            <div class="panel" style="border-radius:0; margin-bottom:0; border-top:1px solid #ddd; padding:5px;">
                <table style="width:100%">
                    <tr>
                        {{-- Column 1: Resorts/Cities (20%) --}}
                        <td style="width:20%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.resorts_regions') ?? 'город' }}</span>
                                <label class="any-check">
                                    <input type="checkbox" id="resorts_all" checked> {{ __('messages.search.all') ?? 'любой' }}
                                </label>
                            </div>
                            <div id="resortsContainer" class="checklistbox">
                                <label style="color:#999; text-align:center; padding:20px 0;">{{ __('messages.search.select_destination') }}</label>
                            </div>
                        </td>

                        {{-- Column 2: Stars/Category (20%) --}}
                        <td style="width:20%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.star_rating') ?? 'категория' }}</span>
                            </div>
                            <div class="checklistbox">
                                @foreach($hotelCategories as $category)
                                    <label>
                                        <input type="checkbox" name="hotel_category_ids[]" value="{{ $category->id }}">
                                        <span class="stars">@for($i = 0; $i < $category->stars; $i++)★@endfor</span>
                                    </label>
                                @endforeach
                            </div>
                        </td>

                        {{-- Column 3: Hotels (40%) --}}
                        <td style="width:40%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.hotels') ?? 'гостиница' }}</span>
                                <span style="display:flex; align-items:center; gap:6px;">
                                    <input type="text" id="hotelSearchInput" class="hotelsearch" placeholder="{{ __('messages.search.search_hotel') ?? 'Search...' }}">
                                    <label class="any-check">
                                        <input type="checkbox" id="hotels_all" checked> {{ __('messages.search.all') ?? 'любая' }}
                                    </label>
                                </span>
                            </div>
                            <div id="hotelsContainer" class="checklistbox">
                                <label style="color:#999; text-align:center; padding:20px 0;">{{ __('messages.search.select_resorts') ?? 'Select a country first' }}</label>
                            </div>
                        </td>

                        {{-- Column 4: Meal Types (20%) --}}
                        <td style="width:20%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.meal_types') ?? 'питание' }}</span>
                                <label class="any-check">
                                    <input type="checkbox" id="meals_all" checked> {{ __('messages.search.all') ?? 'любое' }}
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
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Bottom: Filters + Promotions --}}
            <div class="panel" style="border-radius:0; margin-bottom:0; border-top:1px solid #ddd; padding:5px;">
                <table style="width:100%">
                    <tr>
                        {{-- Filters --}}
                        <td style="width:50%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.filters') ?? 'Фильтры' }}</span>
                            </div>
                            <div class="checklistbox small-list" style="display:flex; flex-wrap:wrap;">
                                <label style="min-width:48%;">
                                    <input type="checkbox" name="instant_confirmation" value="1"> {{ __('messages.search.instant_confirmation') }}
                                </label>
                                <label style="min-width:48%;">
                                    <input type="checkbox" name="no_stop_sale" value="1"> {{ __('messages.search.no_stop_sale') }}
                                </label>
                                <label style="min-width:48%;">
                                    <input type="checkbox" name="with_flight" value="1"> {{ __('messages.search.with_flight') }}
                                </label>
                                <label style="min-width:48%;">
                                    <input type="checkbox" name="direct_flight" value="1"> {{ __('messages.search.direct_flight') }}
                                </label>
                                <label style="min-width:48%;">
                                    <input type="checkbox" name="is_hot" value="1"> {{ __('messages.search.hot_deals_only') }}
                                </label>
                            </div>
                        </td>

                        {{-- Promotions / Transport --}}
                        <td style="width:50%; vertical-align:top; padding:0 3px;">
                            <div class="filter-header">
                                <span class="title">{{ __('messages.search.transport_type') ?? 'Транспорт' }}</span>
                            </div>
                            <div class="checklistbox small-list">
                                @foreach($transportTypes as $type)
                                    <label>
                                        <input type="checkbox" name="transport_type_ids[]" value="{{ $type->id }}"> {{ $type->name }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Footer: Group by + Search button --}}
            <div class="panel" style="border-radius:0 0 8px 8px; padding:6px 10px; display:flex; align-items:center; justify-content:flex-end; gap:15px; border-top:1px solid #ddd;">
                <label style="cursor:pointer; display:flex; align-items:center; gap:4px; font-size:12px; color:#555;">
                    <input type="checkbox" name="group_by_hotel" value="1" style="accent-color:#c00; width:auto; height:auto;">
                    {{ __('messages.search.group_by_hotel') ?? 'группировать результаты' }}
                </label>
                <button type="submit" class="btn-search">
                    {{ __('messages.search.search_button') }}
                </button>
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
                    <button id="sort_dir_toggle" type="button" data-direction="asc" style="border:1px solid #999; border-radius:4px; background:#fff; cursor:pointer; padding:2px 5px; line-height:1;" title="{{ __('messages.toggle_sort_direction') }}">▲</button>
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
<script>
    const resortsByCountry = @json($resortsByCountry);
    const hotelsByResort = @json($hotelsByResort);

    // Country → Resorts
    document.getElementById('country_id').addEventListener('change', function() {
        const countryId = this.value;
        const rc = document.getElementById('resortsContainer');
        if (!countryId) {
            rc.innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">{{ __("messages.search.select_destination") }}</label>';
            document.getElementById('hotelsContainer').innerHTML = '<label style="color:#999;text-align:center;padding:20px 0;">{{ __("messages.search.select_resorts") ?? "Select a country first" }}</label>';
            return;
        }
        const resorts = resortsByCountry[countryId] || [];
        if (!resorts.length) { rc.innerHTML = '<label style="color:#999;padding:20px 0;">No resorts</label>'; return; }
        rc.innerHTML = resorts.map(r => `
            <label><input type="checkbox" name="resort_ids[]" value="${r.id}" class="resort-checkbox"> ${r.name_en || r.name}</label>
        `).join('');
        document.querySelectorAll('.resort-checkbox').forEach(cb => cb.addEventListener('change', updateHotels));
        updateResortsAllCheckbox();
    });

    // Resorts → Hotels
    function updateHotels() {
        const sel = Array.from(document.querySelectorAll('.resort-checkbox:checked')).map(cb => cb.value);
        const hc = document.getElementById('hotelsContainer');
        if (!sel.length) { hc.innerHTML = '<label style="color:#999;padding:20px 0;">{{ __("messages.search.select_resorts") ?? "Select resorts" }}</label>'; return; }
        let html = '';
        sel.forEach(rid => {
            (hotelsByResort[rid] || []).forEach(h => {
                const s = h.category ? '★'.repeat(h.category.stars) : '';
                html += `<label class="hotel-item"><input type="checkbox" name="hotel_ids[]" value="${h.id}" class="hotel-checkbox"> ${h.name} <span class="stars">${s}</span></label>`;
            });
        });
        hc.innerHTML = html || '<label style="color:#999;padding:20px 0;">No hotels</label>';
        updateHotelsAllCheckbox();
    }

    // "All" toggles
    document.getElementById('resorts_all').addEventListener('change', function() {
        document.querySelectorAll('.resort-checkbox').forEach(cb => cb.checked = this.checked);
        updateHotels();
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
        this.textContent = d === 'asc' ? '▲' : '▼';
        doSearch();
    });
    document.getElementById('group_by_hotel').addEventListener('change', function() { doSearch(); });

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
                if (tbody.style.display === 'none') { tbody.style.display = ''; icon.textContent = '▼'; }
                else { tbody.style.display = 'none'; icon.textContent = '►'; }
            });
        });
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                doSearch(this.dataset.page);
            });
        });
    }
</script>
@endpush
@endsection
