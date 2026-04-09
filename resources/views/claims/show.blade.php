@extends('layouts.app')

@section('content')
<style>
    .claim { font-family: inherit; font-size: 12px; color: #333; max-width: 1300px; margin: 20px auto; padding: 0 10px; }
    .claim a { color: #007355; }
    .claim .hdr { background: #3B6E8F; color: #fff; padding: 8px 12px; font-weight: 700; font-size: 13px; margin-bottom: 0; }
    .claim .hdr-green { background: #4A8C5C; }
    .claim .hdr-dark { background: #34495E; }
    .claim .blk { background: #fff; border: 1px solid #ccc; margin-bottom: 12px; }
    .claim .blk-body { padding: 10px 12px; }
    .claim table { width: 100%; border-collapse: collapse; }
    .claim table th { background: #e8ecf1; border: 1px solid #bbb; padding: 5px 8px; font-size: 12px; font-weight: 600; text-align: left; }
    .claim table td { border: 1px solid #ddd; padding: 5px 8px; font-size: 12px; vertical-align: middle; }
    .claim table tr:hover td { background: #f5f8fa; }
    .claim .meta { display: flex; gap: 24px; flex-wrap: wrap; padding: 10px 12px; background: #f8f9fb; border-bottom: 1px solid #ddd; }
    .claim .meta-item { }
    .claim .meta-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.03em; }
    .claim .meta-value { font-size: 13px; font-weight: 600; color: #222; }
    .claim .badge { display: inline-block; padding: 2px 10px; border-radius: 3px; font-size: 11px; font-weight: 700; }
    .claim .badge-confirmed { background: #d4edda; color: #155724; }
    .claim .badge-pending { background: #fff3cd; color: #856404; }
    .claim .badge-cancelled { background: #f8d7da; color: #721c24; }
    .claim .badge-paid { background: #d4edda; color: #155724; border: 1px solid #b8d4be; }
    .claim .svc-row td:first-child { padding-left: 20px; }
    .claim .tourist-card { border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 8px; background: #fafbfc; }
    .claim .tourist-card h4 { margin: 0 0 6px; font-size: 13px; color: #2c5f2d; font-weight: 700; }
    .claim .tourist-card table { border: none; }
    .claim .tourist-card td { border: none; padding: 2px 8px; }
    .claim .tourist-card .lbl { color: #888; }
    .claim .back-link { display: inline-block; margin-bottom: 12px; font-size: 13px; }
</style>

<div class="claim">
    <a href="{{ route('claims.index') }}" class="back-link">&larr; Back to claims</a>

    {{-- ═══ HEADER ═══ --}}
    <div class="blk">
        <div class="meta">
            <div class="meta-item">
                <div class="meta-label">Reservation</div>
                <div class="meta-value">#{{ $order->order_number }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Created</div>
                <div class="meta-value">{{ $order->created_at->format('d.m.Y H:i') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Agency</div>
                <div class="meta-value">{{ $order->agency->name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Manager</div>
                <div class="meta-value">{{ $order->user->name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Total</div>
                <div class="meta-value">${{ number_format($order->total_price, 0) }} {{ $order->currency->code ?? 'USD' }}</div>
            </div>
            @if($flightPath)
            <div class="meta-item">
                <div class="meta-label">Tour</div>
                <div class="meta-value">{{ $flightPath->route_name }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Dates</div>
                <div class="meta-value">{{ $flightPath->departure_date->format('d.m.Y') }} — {{ $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('d.m.Y') }}</div>
            </div>
            @endif
            @if($hotel)
            <div class="meta-item">
                <div class="meta-label">Hotel</div>
                <div class="meta-value">{{ $hotel->name_en }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">City</div>
                <div class="meta-value">{{ $hotel->city->name_en ?? '' }}, {{ $hotel->city->country->name_en ?? '' }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($hotel)
    {{-- ═══ HOTEL ACCOMMODATION ═══ --}}
    <div class="blk">
        <div class="hdr">Accommodation</div>
        <div class="blk-body">
            <table>
                <thead>
                    <tr><th>Hotel</th><th>City</th><th>Room</th><th>Meal</th><th>Date</th><th style="text-align:right;">Tourists</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;">
                            {{ $hotel->name_en }}
                            @if($hotel->category)
                                <span style="color:#c90;">@for($s=0;$s<$hotel->category->stars;$s++)★@endfor</span>
                            @endif
                        </td>
                        <td>{{ $hotel->city->name_en ?? '' }}</td>
                        <td>{{ $booking->roomType->code ?? 'DBL' }}</td>
                        <td><strong>BB</strong></td>
                        <td>{{ $booking->date?->format('d.m.Y') }}</td>
                        <td style="text-align:right;">{{ $booking->tourists->count() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($flightPath)
    {{-- ═══ ACCOMMODATION ═══ --}}
    <div class="blk">
        <div class="hdr">Accommodation</div>
        <div class="blk-body">
            <table>
                <thead>
                    <tr>
                        <th>Hotel</th>
                        <th>City</th>
                        <th>Room</th>
                        <th>Accommodation</th>
                        <th>Meal</th>
                        <th>Period</th>
                        <th style="text-align:right;">Tourists</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stayHotels as $i => $sh)
                    @php
                        $checkIn = $flightPath->departure_date->copy()->addDays(collect($stayHotels)->take($i)->sum('nights'));
                        $checkOut = $checkIn->copy()->addDays($sh['nights']);
                    @endphp
                    <tr>
                        <td style="font-weight:600;">
                            {{ $sh['hotel']->name ?? 'N/A' }}
                            @if($sh['hotel']?->category)
                                <span style="color:#c90;">@for($s=0;$s<$sh['hotel']->category->stars;$s++)★@endfor</span>
                            @endif
                        </td>
                        <td>{{ $sh['stay']->city->name_en ?? '' }}</td>
                        <td>DBL</td>
                        <td>{{ $booking?->tourists->count() ?? 2 }} ADL</td>
                        <td><strong>BB</strong></td>
                        <td>{{ $checkIn->format('d.m.Y') }} — {{ $checkOut->format('d.m.Y') }}</td>
                        <td style="text-align:right;">{{ $booking?->tourists->count() ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ TRANSPORT ═══ --}}
    <div class="blk">
        <div class="hdr">Transport</div>
        <div class="blk-body">
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Flight</th>
                        <th>Date</th>
                        <th>Route</th>
                        <th>Time</th>
                        <th>Class</th>
                        <th style="text-align:right;">Tourists</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flightPath->legs->sortBy('leg_order') as $leg)
                    <tr>
                        <td>
                            @if($leg->leg_order <= $flightPath->legs->count() / 2)
                                <span style="color:#4A8C5C;">&#9992;</span>
                            @else
                                <span style="color:#3B6E8F;">&#9992;</span>
                            @endif
                        </td>
                        <td><strong>{{ $leg->flight->airline->code ?? '' }} {{ $leg->flight->flight_number }}</strong> {{ $leg->flight->airline->name ?? '' }}</td>
                        <td>{{ $leg->flight->departure_date?->format('d.m.Y') }}</td>
                        <td>{{ $leg->flight->fromAirport->code ?? '' }} ({{ $leg->flight->fromAirport->city->name_en ?? '' }}) &rarr; {{ $leg->flight->toAirport->code ?? '' }} ({{ $leg->flight->toAirport->city->name_en ?? '' }})</td>
                        <td>{{ $leg->flight->departure_time ? substr($leg->flight->departure_time, 0, 5) : '' }} — {{ $leg->flight->arrival_time ? substr($leg->flight->arrival_time, 0, 5) : '' }}</td>
                        <td>{{ ucfirst($leg->flight->class_type ?? 'economy') }}</td>
                        <td style="text-align:right;">{{ $booking?->tourists->count() ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ SERVICES ═══ --}}
    @if($services->isNotEmpty())
    <div class="blk">
        <div class="hdr">Services</div>
        <div class="blk-body">
            <table>
                <thead>
                    <tr><th>Service</th><th>City</th><th>Date</th><th style="text-align:right;">Tourists</th><th style="text-align:right;">Price</th></tr>
                </thead>
                <tbody>
                    @foreach($services as $svc)
                    <tr>
                        <td>{{ $svc->name_en }}</td>
                        <td>{{ $svc->city->name_en ?? 'Global' }}</td>
                        <td>{{ $flightPath->departure_date->format('d.m.Y') }}</td>
                        <td style="text-align:right;">{{ $svc->is_per_person ? ($booking?->tourists->count() ?? 0) : '—' }}</td>
                        <td style="text-align:right; font-weight:600;">${{ number_format($svc->price, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══ INSURANCE ═══ --}}
    @if($insurances->isNotEmpty())
    <div class="blk">
        <div class="hdr" style="background:#8B4A6B;">&#9829; Insurance</div>
        <div class="blk-body">
            @foreach($insurances as $ins)
            <div style="padding:4px 0;">
                {{ $ins->name_en }}
                &nbsp; <strong>${{ number_format($ins->price, 0) }}</strong>
                &nbsp; {{ $flightPath->departure_date->format('d.m.Y') }} - {{ $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('d.m.Y') }}
                &nbsp; <span style="color:#888;">{{ $booking?->tourists->count() ?? 0 }} tourists</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    {{-- ═══ TOURISTS ═══ --}}
    @if($booking && $booking->tourists->isNotEmpty())
    <div class="blk">
        <div class="hdr hdr-green">Tourists</div>
        <div class="blk-body">
            <table>
                <thead>
                    <tr><th></th><th>Name</th><th>Date of birth</th><th>Nationality</th><th>Document</th><th>Valid to</th></tr>
                </thead>
                <tbody>
                    @foreach($booking->tourists as $t)
                    <tr>
                        <td>{{ $t->title }}</td>
                        <td style="font-weight:600;">{{ $t->last_name }} {{ $t->first_name }}</td>
                        <td>{{ $t->birth_date?->format('d.m.Y') ?? '—' }}</td>
                        <td>{{ $t->nationality ?? '—' }}</td>
                        <td>{{ $t->passport_series ?? '' }} {{ $t->passport_number ?? '—' }}</td>
                        <td>{{ $t->passport_expiry?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══ NOTES ═══ --}}
    @if($order->notes)
    <div class="blk">
        <div class="hdr hdr-dark">Notes</div>
        <div class="blk-body">{{ $order->notes }}</div>
    </div>
    @endif

    {{-- ═══ PAYMENTS ═══ --}}
    <div class="blk">
        <div class="hdr hdr-dark">Payments</div>
        <div class="blk-body">
            @if($order->payments->isNotEmpty())
            <table>
                <thead>
                    <tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($order->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d.m.Y') }}</td>
                        <td style="font-weight:600;">{{ number_format($payment->amount, 2) }} {{ $payment->currency->code ?? 'USD' }}</td>
                        <td>{{ $payment->payment_method }}</td>
                        <td><span class="badge badge-{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="color:#888;">No payments yet</p>
            @endif
        </div>
    </div>

    {{-- ═══ DOCUMENTS ═══ --}}
    @php $allDocuments = $order->bookings->flatMap->documents; @endphp
    @if($allDocuments->isNotEmpty())
    <div class="blk">
        <div class="hdr hdr-dark">Documents</div>
        <div class="blk-body">
            @foreach($allDocuments as $doc)
            <div style="padding:4px 0; display:flex; justify-content:space-between; align-items:center;">
                <span>{{ $doc->getTypeLabel() }} @if($doc->getDescription()) — <span style="color:#888;">{{ $doc->getDescription() }}</span>@endif</span>
                @if($order->status === 'paid')
                <a href="{{ route('documents.download', $doc) }}" style="font-size:12px;">Download</a>
                @else
                <span style="color:#999; font-size:11px;">Locked (status: {{ $order->status }})</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══ PRICE ═══ --}}
    <div class="blk">
        <div class="hdr" style="background:#2c5f2d;">Price</div>
        <div class="blk-body" style="text-align:center; padding:16px;">
            <div style="font-size:24px; font-weight:700; color:#2c5f2d;">${{ number_format($order->total_price, 0) }} USD</div>
        </div>
    </div>
</div>
@endsection
