<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        h2 { color: #1a365d; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .passenger { font-size: 16px; font-weight: bold; color: #1a365d; margin: 10px 0; text-transform: uppercase; }
        .flight-card { border: 1px solid #cbd5e0; margin-bottom: 15px; }
        .flight-header { background: #1a365d; color: #fff; padding: 8px 15px; font-size: 13px; }
        .flight-body { padding: 10px 15px; }
        .route { display: table; width: 100%; margin: 10px 0; }
        .route-from, .route-to { display: table-cell; width: 35%; vertical-align: top; }
        .route-arrow { display: table-cell; width: 30%; text-align: center; vertical-align: middle; font-size: 20px; color: #1a365d; }
        .airport-code { font-size: 22px; font-weight: bold; color: #1a365d; }
        .airport-name { font-size: 10px; color: #666; }
        .flight-time { font-size: 16px; font-weight: bold; }
        .flight-date { font-size: 11px; color: #666; }
        .info-row { display: table; width: 100%; margin-top: 8px; border-top: 1px solid #eee; padding-top: 8px; }
        .info-cell { display: table-cell; width: 33%; }
        .info-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .info-value { font-size: 12px; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    @include('documents._header', ['order' => $order])

    <h2>{{ __('messages.doc_ticket') }}</h2>

    <p class="passenger">{{ strtoupper($tourist->last_name . ' ' . $tourist->first_name) }}</p>

    @foreach($flights as $flight)
    <div class="flight-card">
        <div class="flight-header">
            {{ $flight->airline?->name ?? '' }} &mdash; {{ $flight->flight_number }}
            @if($flight->pivot?->direction)
                ({{ $flight->pivot->direction === 'outbound' ? __('messages.doc_outbound') : __('messages.doc_return') }})
            @endif
        </div>
        <div class="flight-body">
            <div class="route">
                <div class="route-from">
                    <div class="airport-code">{{ $flight->fromAirport?->code ?? '' }}</div>
                    <div class="airport-name">{{ $flight->fromAirport?->name_en ?? '' }}</div>
                    <div class="flight-time">{{ $flight->departure_time ?? '' }}</div>
                    <div class="flight-date">{{ $flight->departure_date?->format('d.m.Y') ?? '' }}</div>
                </div>
                <div class="route-arrow">&#9992;</div>
                <div class="route-to" style="text-align: right;">
                    <div class="airport-code">{{ $flight->toAirport?->code ?? '' }}</div>
                    <div class="airport-name">{{ $flight->toAirport?->name_en ?? '' }}</div>
                    <div class="flight-time">{{ $flight->arrival_time ?? '' }}</div>
                    <div class="flight-date">{{ $flight->arrival_date?->format('d.m.Y') ?? '' }}</div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">{{ __('messages.doc_class') }}</div>
                    <div class="info-value">{{ ucfirst($flight->class_type ?? 'Economy') }}</div>
                </div>
                <div class="info-cell">
                    <div class="info-label">{{ __('messages.doc_flight_number') }}</div>
                    <div class="info-value">{{ $flight->flight_number }}</div>
                </div>
                <div class="info-cell">
                    <div class="info-label">{{ __('messages.doc_airline') }}</div>
                    <div class="info-value">{{ $flight->airline?->name ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="footer">
        LEVORA TRAVEL &mdash; {{ __('messages.doc_company_tagline') }}
    </div>
</body>
</html>
