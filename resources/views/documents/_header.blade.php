<div style="border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 20px;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 60%;">
                <h1 style="margin: 0; font-size: 24px; color: #1a365d; font-weight: bold;">LEVORA TRAVEL</h1>
                <p style="margin: 2px 0; font-size: 10px; color: #666;">{{ __('messages.doc_company_tagline') }}</p>
            </td>
            <td style="width: 40%; text-align: right; font-size: 10px; color: #666;">
                <p style="margin: 1px 0;">{{ __('messages.doc_order') }}: {{ $order->order_number }}</p>
                <p style="margin: 1px 0;">{{ __('messages.doc_date') }}: {{ now()->format('d.m.Y') }}</p>
            </td>
        </tr>
    </table>
</div>
