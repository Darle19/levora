<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 30px; }
        h2 { color: #1a365d; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        h3 { color: #2d4a7a; font-size: 13px; margin-top: 15px; }
        .section { margin-bottom: 15px; padding: 10px; background: #f8fafc; border-left: 3px solid #1a365d; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { padding: 5px 10px; border: 1px solid #ddd; }
        .info-table .label { background: #f0f4f8; font-weight: bold; width: 35%; color: #1a365d; }
        ul { margin: 5px 0; padding-left: 20px; }
        li { margin-bottom: 3px; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
        .important { color: #c53030; font-weight: bold; }
    </style>
</head>
<body>
    @include('documents._header', ['order' => $order])

    <h2>{{ __('messages.doc_memo') }}</h2>

    <div class="section">
        <h3>{{ __('messages.doc_trip_info') }}</h3>
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
                <td>{{ $tour->date_from?->format('d.m.Y') }} — {{ $tour->date_to?->format('d.m.Y') }} ({{ $tour->nights }} {{ __('messages.doc_nights') }})</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ __('messages.doc_hotel_checkin') }}</h3>
        <p>{{ __('messages.doc_checkin_info') }}</p>
    </div>

    <div class="section">
        <h3>{{ __('messages.doc_important_info') }}</h3>
        <ul>
            <li>{{ __('messages.doc_memo_passport') }}</li>
            <li>{{ __('messages.doc_memo_insurance') }}</li>
            <li>{{ __('messages.doc_memo_customs') }}</li>
            <li>{{ __('messages.doc_memo_valuables') }}</li>
            <li>{{ __('messages.doc_memo_local_laws') }}</li>
        </ul>
    </div>

    <div class="section">
        <h3>{{ __('messages.doc_emergency_contacts') }}</h3>
        <p>{{ __('messages.doc_emergency_info') }}</p>
    </div>

    <div class="footer">
        LEVORA TRAVEL &mdash; {{ __('messages.doc_company_tagline') }}
    </div>
</body>
</html>
