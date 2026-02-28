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
        .policy-number { background: #ebf8ff; border: 2px solid #3182ce; padding: 10px 20px; text-align: center; font-size: 18px; font-weight: bold; color: #2b6cb0; margin: 15px 0; }
        .coverage-box { background: #f0fff4; border: 1px solid #c6f6d5; padding: 10px 15px; margin: 10px 0; }
        .insurer { background: #f7fafc; border: 1px solid #e2e8f0; padding: 10px 15px; margin-top: 20px; font-size: 11px; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    @include('documents._header', ['order' => $order])

    <h2>{{ __('messages.doc_insurance') }}</h2>

    @if(!empty($policyData['policy_number']))
    <div class="policy-number">
        {{ __('messages.doc_policy_number') }}: {{ $policyData['policy_number'] }}
    </div>
    @endif

    <table class="info-table">
        <tr>
            <td class="label">{{ __('messages.doc_insured') }}</td>
            <td style="font-weight: bold; font-size: 14px;">{{ strtoupper($tourist->last_name . ' ' . $tourist->first_name . ($tourist->middle_name ? ' ' . $tourist->middle_name : '')) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_birth_date') }}</td>
            <td>{{ $tourist->birth_date?->format('d.m.Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_passport') }}</td>
            <td>{{ $tourist->passport_series }} {{ $tourist->passport_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_country') }}</td>
            <td>{{ $tour->country?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.doc_coverage_period') }}</td>
            <td>{{ $tour->date_from?->format('d.m.Y') }} — {{ $tour->date_to?->format('d.m.Y') }}</td>
        </tr>
    </table>

    <div class="coverage-box">
        <strong>{{ __('messages.doc_coverage') }}:</strong>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>{{ __('messages.doc_risk_accident') }}</li>
        </ul>
        @if(!empty($policyData['premium']))
        <p>{{ __('messages.doc_premium') }}: <strong>{{ $policyData['premium'] }}</strong></p>
        @endif
    </div>

    <div class="insurer">
        <strong>{{ __('messages.doc_insurer') }}:</strong> NeoInsurance<br>
        <strong>{{ __('messages.doc_website') }}:</strong> neoinsurance.uz
    </div>

    <div class="footer">
        LEVORA TRAVEL &mdash; {{ __('messages.doc_company_tagline') }}
    </div>
</body>
</html>
