<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 10px; border: 1px solid #ddd; }
        .info-table .label { background: #f0f4f8; font-weight: bold; width: 35%; color: #1a365d; }
        h2 { color: #1a365d; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .tourist-table { margin-top: 10px; }
        .tourist-table th { background: #1a365d; color: #fff; padding: 6px 10px; text-align: left; font-size: 11px; }
        .tourist-table td { padding: 5px 10px; border: 1px solid #ddd; font-size: 11px; }
        .tourist-table tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    @include('documents._header', ['order' => $order])

    <h2>{{ __('messages.doc_confirmation') }}</h2>

    <table class="info-table">
        <tr>
            <td class="label">{{ __('messages.doc_country') }}</td>
            <td>{{ $tour->country?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_resort') }}</td>
            <td>{{ $tour->resort?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_hotel') }}</td>
            <td>{{ $tour->hotel?->name ?? '—' }} {{ $tour->hotel?->category ? str_repeat('★', $tour->hotel->category->stars ?? 0) : '' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_dates') }}</td>
            <td>{{ $tour->date_from?->format('d.m.Y') }} — {{ $tour->date_to?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_nights') }}</td>
            <td>{{ $tour->nights }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_meal') }}</td>
            <td>{{ $tour->mealType?->code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_tourists_count') }}</td>
            <td>{{ $booking->tourists->count() }} ({{ $tour->adults }} {{ __('messages.doc_adults') }}{{ $tour->children ? ', ' . $tour->children . ' ' . __('messages.doc_children') : '' }})</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_total_price') }}</td>
            <td style="font-weight: bold; font-size: 14px;">{{ number_format($order->total_price, 2) }} {{ $order->currency?->code ?? '' }}</td>
        </tr>
    </table>

    <h2>{{ __('messages.doc_tourists') }}</h2>

    <table class="tourist-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('messages.doc_full_name') }}</th>
                <th>{{ __('messages.doc_birth_date') }}</th>
                <th>{{ __('messages.doc_passport') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($booking->tourists as $i => $tourist)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ strtoupper($tourist->last_name . ' ' . $tourist->first_name . ($tourist->middle_name ? ' ' . $tourist->middle_name : '')) }}</td>
                <td>{{ $tourist->birth_date?->format('d.m.Y') ?? '—' }}</td>
                <td>{{ $tourist->passport_series }} {{ $tourist->passport_number }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($agency)
    <h2>{{ __('messages.doc_agency') }}</h2>
    <table class="info-table">
        <tr>
            <td class="label">{{ __('messages.doc_agency_name') }}</td>
            <td>{{ $agency->name }}</td>
        </tr>
        @if($agency->phone)
        <tr>
            <td class="label">{{ __('messages.doc_phone') }}</td>
            <td>{{ $agency->phone }}</td>
        </tr>
        @endif
        @if($agency->email)
        <tr>
            <td class="label">{{ __('messages.doc_email') }}</td>
            <td>{{ $agency->email }}</td>
        </tr>
        @endif
    </table>
    @endif

    <div class="footer">
        LEVORA TRAVEL &mdash; {{ __('messages.doc_company_tagline') }}
    </div>
</body>
</html>
