<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 30px; }
    h1 { font-size: 28px; font-weight: bold; margin: 0; }
    h2 { font-size: 14px; color: #006655; margin: 20px 0 6px; border-bottom: 2px solid #006655; padding-bottom: 3px; }
    .booking-num { font-size: 18px; color: #333; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { text-align: left; font-weight: bold; border-bottom: 1px solid #999; padding: 4px 6px; font-size: 10px; }
    td { padding: 4px 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
    .detail-table td { border: none; padding: 5px 10px; }
    .detail-table td:first-child { font-weight: bold; width: 180px; }
    .contacts { font-size: 9px; color: #666; margin-top: 10px; }
    hr { border: none; border-top: 3px solid #006655; margin: 10px 0 15px; }
</style>
</head>
<body>

<table style="width:100%;">
    <tr>
        <td style="vertical-align:top;"><h1>Hotel Voucher</h1><div class="booking-num">#{{ $order_number }}</div></td>
        <td style="text-align:right; vertical-align:top;">@include('documents._logo')</td>
    </tr>
</table>
<hr>

<table class="detail-table">
    <tr><td>Hotel</td><td>{{ $hotelStay->hotel_name }}, {{ $hotelStay->stars }}* ({{ $hotelStay->city }})</td></tr>
    <tr><td>Room</td><td>{{ $hotelStay->room_type }}</td></tr>
    <tr><td>Accommodation</td><td>DBL</td></tr>
    <tr><td>Meal</td><td>Bed & Breakfast</td></tr>
    <tr><td>Check-in</td><td>{{ $hotelStay->check_in ? date('d.m.Y', strtotime($hotelStay->check_in)) : '—' }} &nbsp; 14:00:00</td></tr>
    <tr><td>Check-out</td><td>{{ $hotelStay->check_out ? date('d.m.Y', strtotime($hotelStay->check_out)) : '—' }} &nbsp; 12:00:00</td></tr>
    <tr><td>Service Request</td><td>{{ $order->notes ?? '—' }}</td></tr>
    <tr><td>Confirmation #</td><td>—</td></tr>
</table>

@if($hotelStay->address || $hotelStay->phone)
<div class="contacts">
    @if($hotelStay->address) Hotel contacts: {{ $hotelStay->address }}<br> @endif
    @if($hotelStay->phone) Phone: {{ $hotelStay->phone }}<br> @endif
    @if($hotelStay->email) Email: {{ $hotelStay->email }}<br> @endif
</div>
@endif

@if($agency)
<div class="contacts">Partner: {{ strtoupper($agency->name) }}</div>
@endif

<h2>Tourists</h2>
<table>
    <thead>
        <tr><th>Name</th><th style="text-align:right;">Date of Birth</th></tr>
    </thead>
    <tbody>
        @foreach($tourists as $t)
        <tr>
            <td>{{ $t->title }} &nbsp; {{ strtoupper($t->last_name) }} {{ strtoupper($t->first_name) }}</td>
            <td style="text-align:right;">{{ $t->birth_date?->format('d.m.Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
