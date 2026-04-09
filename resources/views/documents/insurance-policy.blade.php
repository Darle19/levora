<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 30px; }
    h1 { font-size: 14px; text-align: center; margin: 0 0 5px; text-transform: uppercase; }
    h2 { font-size: 12px; text-align: center; margin: 0 0 15px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th { text-align: left; font-weight: bold; border: 1px solid #999; padding: 5px 8px; font-size: 10px; background: #f0f0f0; }
    td { padding: 5px 8px; border: 1px solid #ccc; font-size: 10px; }
    .ptn { text-align: right; font-size: 11px; margin-bottom: 10px; }
    .memo { text-align: center; font-size: 10px; margin: 20px 0 10px; color: #c00; font-weight: bold; text-transform: uppercase; }
    .memo-sub { text-align: center; font-size: 9px; color: #c00; font-weight: bold; margin-bottom: 10px; }
    .emergency { font-size: 9px; color: #333; margin: 10px 0; }
    .emergency-table td { border: none; padding: 4px 10px; text-align: center; }
    .footer { font-size: 8px; color: #666; margin-top: 15px; }
</style>
</head>
<body>

<table style="width:100%;">
    <tr>
        <td style="vertical-align:top; width:60%;">
            <h1>SAYOHATCHILARNI UMUMIY SUG'URTA QILISH POLISI</h1>
            <h2>COMBINED TRAVEL INSURANCE POLICY</h2>
        </td>
        <td style="text-align:right; vertical-align:top;">@include('documents._logo')</td>
    </tr>
</table>

<div class="ptn">PTN {{ $order_number }}</div>

{{-- Policyholder --}}
<table>
    <tr>
        <th>Sug'urta qildiruvchi / Policyholder</th>
        <th>Telefon raqami / Phone number</th>
    </tr>
    <tr>
        <td>{{ strtoupper($tourist->last_name) }} {{ strtoupper($tourist->first_name) }}</td>
        <td>{{ $order->user->phone ?? '+998919777735' }}</td>
    </tr>
</table>

{{-- Insured persons --}}
<table>
    <tr>
        <th>Sug'urtalangan Shaxs<br>Insured person</th>
        <th>Tug'ilgan sana<br>Date of birth</th>
        <th>Pasport#<br>Passport#</th>
    </tr>
    @foreach($tourists as $t)
    <tr>
        <td>{{ strtoupper($t->last_name) }} {{ strtoupper($t->first_name) }}</td>
        <td>{{ $t->birth_date?->format('d-m-Y') }}</td>
        <td>{{ $t->passport_number }}</td>
    </tr>
    @endforeach
</table>

{{-- Travel details --}}
<table>
    <tr>
        <th>Sayohat mamlakati<br>Host country</th>
        <th>Dan<br>From</th>
        <th>Gacha<br>To</th>
        <th>Kunlar<br>Days</th>
    </tr>
    <tr>
        <td>{{ $destination_country ?? '—' }}</td>
        <td>{{ $departure_date?->format('d.m.Y') }}</td>
        <td>{{ $return_date?->format('d.m.Y') }}</td>
        <td>{{ $total_nights + 1 }}</td>
    </tr>
</table>

{{-- Insurance program --}}
<table>
    <tr>
        <th>Sug'urta dasturi<br>Insurance program</th>
        <th>Har bir sug'urtalangan shaxsning sug'urta puli/<br>Sum insured per person</th>
    </tr>
    <tr>
        <td>Econom</td>
        <td>10 000 USD</td>
    </tr>
</table>

{{-- Memo --}}
<div class="memo">SUG'URTALANGAN SHAXS UCHUN ESLATMA</div>
<div class="memo-sub">ПАМЯТКА ЗАСТРАХОВАННОМУ ЛИЦУ</div>

<div class="emergency">
    SHOSHILINCH TIBBIY YORDAM UCHUN 24 SOATLIK XIZMAT KO'RSATISH MARKAZIGA QUYIDAGI RAQAMLAR ORQALI MUROJAAT QILING /
    ДЛЯ ПОЛУЧЕНИЯ ЭКСТРЕННОЙ МЕДИЦИНСКОЙ ПОМОЩИ МОЖЕТЕ ОБРАТИТЬСЯ В КРУГЛОСУТОЧНУЮ СЕРВИСНУЮ КОМПАНИЮ ПО НОМЕРАМ:
</div>

<table class="emergency-table">
    <tr>
        <td>
            <strong>Telegram</strong><br>
            +7 (985) 109-85-77
        </td>
        <td>
            <strong>WhatsApp</strong><br>
            +7 (985) 109-85-77
        </td>
        <td>
            <strong>Phone call</strong><br>
            +7 (495) 133-76-67
        </td>
    </tr>
</table>

<div class="footer">
    Murojaat uchun manzil:<br>
    Toshkent sh., Sh.Rustaveli ko'chasi, 158-A uy.<br>
    Tel.: +998 95 850 00 11<br>
    E-mail: info@neoinsurance.uz
</div>

</body>
</html>
