@extends('layouts.app')

@section('content')
<style>
    .hs { font-family: inherit; font-size: 13px; }
    .hs .search-bar { background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
    .hs .search-bar label { font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 3px; }
    .hs .search-bar select, .hs .search-bar input { border: 1px solid #aaa; border-radius: 4px; padding: 5px 8px; font-size: 13px; height: 32px; }
    .hs .search-bar select { min-width: 180px; }
    .hs .btn-search { background: #1B6B2E; color: #fff; border: none; border-radius: 4px; padding: 6px 20px; font-size: 13px; font-weight: 600; cursor: pointer; height: 32px; }
    .hs .btn-search:hover { background: #238636; }
    .hs .region-title { background: #3B6E8F; color: #fff; padding: 6px 12px; font-weight: 700; font-size: 13px; border-radius: 4px 4px 0 0; margin-top: 16px; }
    .hs table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ccc; }
    .hs table th { background: #e8ecf1; border: 1px solid #bbb; padding: 6px 10px; font-size: 12px; font-weight: 600; text-align: left; }
    .hs table td { border: 1px solid #ddd; padding: 6px 10px; font-size: 12px; }
    .hs table tr:hover td { background: #f5f8fa; }
    .hs .stars { color: #c90; }
    .hs .price { font-weight: 700; color: #1B6B2E; }
    .hs .meal { background: #e8ffe8; color: #1B6B2E; padding: 1px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .hs .room-badge { background: #eef; color: #336; padding: 1px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .hs .empty { text-align: center; padding: 40px; color: #888; }
    .hs .tabs { display: flex; gap: 4px; margin-bottom: 12px; flex-wrap: wrap; }
    .hs .tab { padding: 6px 14px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; color: #333; background: #f5f5f5; }
    .hs .tab:hover { background: #e8ecf1; }
    .hs .tab-active { background: #3B6E8F; color: #fff; border-color: #3B6E8F; }
</style>

<div class="hs" style="max-width:1300px; margin:20px auto; padding:0 10px;">

    <h2 style="margin:0 0 12px; font-size:18px; color:#333;">Hotel Search</h2>

    {{-- Search Bar --}}
    <form method="GET" action="{{ route('search.hotels') }}" class="search-bar">
        <div>
            <label>Country</label>
            <select name="country" onchange="this.form.submit()">
                <option value="">— Select Country —</option>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" {{ $selectedCountryId == $c->id ? 'selected' : '' }}>{{ $c->name_en }}</option>
                @endforeach
            </select>
        </div>

        @if($resorts->isNotEmpty())
        <div>
            <label>Region</label>
            <select name="resort" onchange="this.form.submit()">
                <option value="">All regions</option>
                @foreach($resorts as $r)
                    <option value="{{ $r->id }}" {{ $selectedResortId == $r->id ? 'selected' : '' }}>{{ $r->name_en }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label>Nights</label>
            <input type="number" name="nights" value="{{ $nights }}" min="1" max="30" style="width:70px;">
        </div>

        <div>
            <button type="submit" class="btn-search">Search</button>
        </div>
    </form>

    @if(! $selectedCountryId)
        <div class="empty">
            <p style="font-size:16px; margin-bottom:8px;">Select a country to see available hotels</p>
            <p>{{ $countries->count() }} countries with hotels available</p>
        </div>
    @elseif($hotels->isEmpty())
        <div class="empty">
            <p>No hotels found for this selection.</p>
        </div>
    @else
        {{-- Region tabs --}}
        @if($resorts->isNotEmpty() && ! $selectedResortId)
        <div class="tabs">
            <a href="{{ route('search.hotels', ['country' => $selectedCountryId, 'nights' => $nights]) }}" class="tab tab-active">All ({{ $hotels->count() }})</a>
            @foreach($resorts as $r)
                @php $count = $hotels->where('resort_id', $r->id)->count(); @endphp
                @if($count > 0)
                <a href="{{ route('search.hotels', ['country' => $selectedCountryId, 'resort' => $r->id, 'nights' => $nights]) }}" class="tab">{{ $r->name_en }} ({{ $count }})</a>
                @endif
            @endforeach
        </div>
        @endif

        {{-- Hotels grouped by region --}}
        @php $grouped = $hotels->groupBy('resort_id'); @endphp
        @foreach($grouped as $resortId => $regionHotels)
            @php $resort = $regionHotels->first()->resort; @endphp
            <div class="region-title">{{ $resort->name_en ?? 'Other' }} — {{ $regionHotels->first()->city->name_en ?? '' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Hotel</th>
                        <th>Stars</th>
                        <th>Room</th>
                        <th>Meal</th>
                        <th style="text-align:right;">Per Night</th>
                        <th style="text-align:right;">{{ $nights }} nights</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($regionHotels as $hotel)
                        @php $activeRooms = $hotel->roomTypes->where('pivot.is_active', true); @endphp
                        @foreach($activeRooms as $room)
                        <tr>
                            @if($loop->first)
                            <td rowspan="{{ $activeRooms->count() }}" style="font-weight:600; vertical-align:top;">
                                {{ $hotel->name_en }}
                            </td>
                            <td rowspan="{{ $activeRooms->count() }}" style="vertical-align:top;">
                                <span class="stars">@for($s=0;$s<($hotel->category->stars ?? 0);$s++)★@endfor</span>
                            </td>
                            @endif
                            <td><span class="room-badge">{{ $room->code }}</span> {{ $room->name_en }}</td>
                            <td>
                                @foreach($hotel->mealTypes as $mt)
                                    <span class="meal">{{ $mt->code }}</span>
                                @endforeach
                            </td>
                            <td style="text-align:right;" class="price">${{ number_format($room->pivot->price_per_night + ($commission / $nights), 0) }}</td>
                            <td style="text-align:right;" class="price">${{ number_format(($room->pivot->price_per_night * $nights) + $commission, 0) }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endforeach

        <div style="margin-top:12px; font-size:11px; color:#888;">
            Prices shown per room per stay. Breakfast included where indicated.
        </div>
    @endif
</div>
@endsection
