<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 30px; }
    h1 { font-size: 28px; color: #006655; margin: 0; display: inline-block; }
    h2 { font-size: 14px; color: #006655; margin: 18px 0 6px; border-bottom: 2px solid #006655; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { text-align: left; font-weight: bold; border-bottom: 1px solid #999; padding: 4px 6px; font-size: 10px; }
    td { padding: 4px 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
    .header { margin-bottom: 20px; }
    .header-right { text-align: right; font-size: 18px; font-weight: bold; }
    .tourist-list { font-size: 10px; color: #444; margin-bottom: 10px; }
    .hotline { font-size: 10px; color: #444; margin-bottom: 15px; }
</style>
</head>
<body>

{{-- LOGO --}}
@include('documents._logo')

{{-- HEADER --}}
<table class="header">
    <tr>
        <td style="width:60%; vertical-align:bottom;">
            <h1>Tourist Voucher</h1>
        </td>
        <td style="width:40%; vertical-align:bottom; text-align:right;">
            <div style="font-size:18px; font-weight:bold;">№{{ $order_number }}</div>
        </td>
    </tr>
</table>
<div style="border-bottom: 2px solid #006655; margin-bottom: 15px;"></div>

<div class="tourist-list">
    @foreach($tourists as $i => $t)
        {{ $i + 1 }}. {{ $t->title }} {{ strtoupper($t->last_name) }} {{ strtoupper($t->first_name) }}<br>
    @endforeach
    {{ $departure_date?->format('d.m.Y') ?? '' }}
</div>

@if(isset($city_contacts) && $city_contacts->isNotEmpty())
<div class="hotline">
    @foreach($city_contacts as $cc)
        {{ $cc->city }}@if($cc->agent_name) ({{ $cc->agent_name }})@endif: {{ $cc->agent_phone }}<br>
    @endforeach
</div>
@elseif($destination_country)
<div class="hotline">{{ $destination_country }} Destination Hotline Number: +998 91 977 77 35</div>
@endif

{{-- FLIGHTS --}}
@if($flights->isNotEmpty())
<h2>Flights</h2>
<table>
    <thead>
        <tr><th>Date</th><th>Flight #</th><th>Departure</th><th>Arrival</th><th>Class</th><th>Seats</th></tr>
    </thead>
    <tbody>
        @foreach($flights as $f)
        <tr>
            <td>{{ $f->date?->format('d.m.Y') }}</td>
            <td>{{ $f->flight_number }}</td>
            <td>{{ $f->departure_airport }} ({{ $f->departure_city }}), {{ $f->departure_time }}</td>
            <td>{{ $f->arrival_airport }} ({{ $f->arrival_city }}), {{ $f->arrival_time }}</td>
            <td>{{ $f->class_code }}</td>
            <td>{{ $f->seats }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- HOTELS --}}
@if(! empty($hotels))
<h2>Hotels</h2>
<table>
    <thead>
        <tr><th>Check-in</th><th>Nights</th><th>Hotel</th><th>Location</th><th>Room Type</th><th>Meal</th><th>Rooms</th></tr>
    </thead>
    <tbody>
        @foreach($hotels as $h)
        <tr>
            <td>{{ $h->check_in ? date('d.m.Y', strtotime($h->check_in)) : '—' }}</td>
            <td>{{ $h->nights }}</td>
            <td>{{ $h->hotel_name }} / {{ $h->stars }}*</td>
            <td>{{ $h->city }}</td>
            <td>{{ $h->room_type }}</td>
            <td>{{ $h->meal }}</td>
            <td>{{ $h->rooms }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- TRANSFERS --}}
@if($transfers->isNotEmpty())
<h2>Transfers</h2>
<table>
    <thead>
        <tr><th>Date</th><th>Type</th><th>Direction</th></tr>
    </thead>
    <tbody>
        @foreach($transfers as $tr)
        <tr>
            <td>{{ $tr->date }}</td>
            <td>{{ $tr->type }}</td>
            <td>{{ $tr->direction }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- INSURANCES --}}
@if($insurances->isNotEmpty())
<h2>Insurances</h2>
<table>
    <thead>
        <tr><th>Period</th><th>Name</th></tr>
    </thead>
    <tbody>
        @foreach($insurances as $ins)
        <tr>
            <td>{{ $ins->period }}</td>
            <td>{{ $ins->name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- TOURISTS --}}
<h2>Tourists</h2>
<table>
    <thead>
        <tr><th>Title</th><th>Name</th><th>Date of Birth</th><th>Travel Document #</th></tr>
    </thead>
    <tbody>
        @foreach($tourists as $t)
        <tr>
            <td>{{ $t->title }}</td>
            <td>{{ strtoupper($t->last_name) }} {{ strtoupper($t->first_name) }}</td>
            <td>{{ $t->birth_date?->format('d.m.Y') }}</td>
            <td>{{ $t->passport_number }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
