<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        h2 { color: #1a365d; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { padding: 6px 10px; border: 1px solid #ddd; }
        .info-table .label { background: #f0f4f8; font-weight: bold; width: 35%; color: #1a365d; }
        .hotel-name { font-size: 18px; color: #1a365d; font-weight: bold; margin: 10px 0; }
        .stars { color: #d69e2e; font-size: 16px; }
        .tourist-table { margin-top: 10px; width: 100%; border-collapse: collapse; }
        .tourist-table th { background: #1a365d; color: #fff; padding: 6px 10px; text-align: left; font-size: 11px; }
        .tourist-table td { padding: 5px 10px; border: 1px solid #ddd; font-size: 11px; }
        .tourist-table tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
        .ref-number { background: #edf2f7; padding: 8px 15px; border: 1px solid #cbd5e0; display: inline-block; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
    @include('documents._header', ['order' => $order])

    <h2>{{ __('messages.doc_voucher') }}</h2>

    <div class="ref-number">
        {{ __('messages.doc_booking_ref') }}: {{ $order->order_number }}-{{ $booking->id }}
    </div>

    <p class="hotel-name">
        {{ $tour->hotel?->name ?? '—' }}
        @if($tour->hotel?->category)
            <span class="stars">{{ str_repeat('★', $tour->hotel->category->stars ?? 0) }}</span>
        @endif
    </p>

    <table class="info-table">
        <tr>
            <td class="label">{{ __('messages.doc_resort') }}</td>
            <td>{{ $tour->resort?->name ?? '—' }}, {{ $tour->country?->name ?? '' }}</td>
        </tr>
        @if($tour->hotel?->address)
        <tr>
            <td class="label">{{ __('messages.doc_address') }}</td>
            <td>{{ $tour->hotel->address }}</td>
        </tr>
        @endif
        @if($tour->hotel?->phone)
        <tr>
            <td class="label">{{ __('messages.doc_phone') }}</td>
            <td>{{ $tour->hotel->phone }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">{{ __('messages.doc_checkin') }}</td>
            <td style="font-weight: bold;">{{ $tour->date_from?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_checkout') }}</td>
            <td style="font-weight: bold;">{{ $tour->date_to?->format('d.m.Y') }}</td>
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
            <td class="label">{{ __('messages.doc_rooms') }}</td>
            <td>{{ $tour->adults }} {{ __('messages.doc_adults') }}{{ $tour->children ? ', ' . $tour->children . ' ' . __('messages.doc_children') : '' }}</td>
        </tr>
    </table>

    <h2>{{ __('messages.doc_guests') }}</h2>

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
                <td>{{ strtoupper($tourist->last_name . ' ' . $tourist->first_name) }}</td>
                <td>{{ $tourist->birth_date?->format('d.m.Y') ?? '—' }}</td>
                <td>{{ $tourist->passport_series }} {{ $tourist->passport_number }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        LEVORA TRAVEL &mdash; {{ __('messages.doc_company_tagline') }}
    </div>
</body>
</html>
