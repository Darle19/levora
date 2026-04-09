<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 30px; }
    h1 { font-size: 18px; color: #006655; margin: 0 0 15px; }
    h2 { font-size: 14px; color: #333; margin: 18px 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th { text-align: left; font-weight: bold; border-bottom: 1px solid #999; padding: 5px 6px; font-size: 10px; background: #f5f5f5; }
    td { padding: 5px 6px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .prepared { font-size: 13px; margin-bottom: 12px; }
    .prepared strong { font-style: italic; }
    .info-table td { border: 1px solid #ccc; padding: 6px 10px; }
    .info-table td:first-child { font-weight: bold; width: 45%; background: #fafafa; }
    .disclaimer { font-size: 8px; color: #555; border: 1px solid #ccc; padding: 8px; margin-top: 15px; line-height: 1.4; }
    .disclaimer strong { display: block; margin-bottom: 4px; }
    hr { border: none; border-top: 2px solid #006655; margin: 10px 0; }
</style>
</head>
<body>

@include('documents._logo')
<h1>Квитанция электронного билета (eTicket)</h1>
<hr>

<div class="prepared">
    <strong>Подготовлен для</strong> &nbsp;
    {{ strtoupper($tourist->last_name) }} {{ strtoupper($tourist->first_name) }}
    @if($tourist->middle_name) {{ strtoupper($tourist->middle_name) }} @endif
    / {{ $tourist->gender === 'male' ? 'М' : 'Ж' }}
</div>

<table class="info-table">
    <tr><td>КОД БРОНИРОВАНИЯ</td><td>{{ $order_number }}</td></tr>
    <tr><td>ДАТА ВЫДАЧИ БИЛЕТА</td><td>{{ $issue_date?->format('d.m.Y') }}</td></tr>
</table>

<h2>Сведения о маршруте</h2>
<table>
    <thead>
        <tr><th>ДАТА</th><th>АВИАКОМПАНИЯ</th><th>ОТПРАВЛЕНИЕ</th><th>ПРИБЫТИЕ</th><th>ДРУГИЕ ПРИМЕЧАНИЯ</th></tr>
    </thead>
    <tbody>
        @foreach($flights as $f)
        <tr>
            <td><strong>{{ $f->date?->format('d') }}{{ $f->date ? mb_strtolower($f->date->locale('ru')->isoFormat('MMM')) : '' }}.{{ $f->date?->format('y') }}</strong></td>
            <td>{{ $f->flight_number }}</td>
            <td>
                <strong>{{ $f->departure_city }}</strong><br>
                ({{ $f->departure_airport }})<br><br>
                Время<br><strong>{{ $f->departure_time }}</strong>
            </td>
            <td>
                <strong>{{ $f->arrival_city }}</strong><br>
                ({{ $f->arrival_airport }})<br><br>
                Время<br><strong>{{ $f->arrival_time }}</strong>
            </td>
            <td>
                Класс ЭКОНОМИЧЕСКИЙ<br>
                Baggage {{ $f->baggage }}<br>
                Базовый тариф IT
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2>Подробности платежа</h2>
<table class="info-table">
    <tr><td>Форма оплаты</td><td><strong>НАЛИЧНЫЕ</strong></td></tr>
    <tr><td>Передаточные надписи в билете</td><td>PF{{ $tourist->passport_number }}/NON REF</td></tr>
    <tr><td>Тариф</td><td>IT</td></tr>
    <tr><td>Налоги / пошлины / сборы</td><td></td></tr>
    <tr><td>Общая стоимость</td><td>USD IT</td></tr>
</table>

<div class="disclaimer">
    <strong>Для регистрации в аэропорту необходимо предъявить паспорт</strong>
    <strong>Объявление:</strong>
    ПАССАЖИРЫ, ПЕРЕВОЗКА КОТОРЫХ ИМЕЕТ ПУНКТ НАЗНАЧЕНИЯ ИЛИ ОСТАНОВКУ НЕ В СТРАНЕ ОТПРАВЛЕНИЯ,
    УВЕДОМЛЯЮТСЯ О ТОМ, ЧТО ПОЛОЖЕНИЯ МЕЖДУНАРОДНЫХ ДОГОВОРОВ, ИЗВЕСТНЫХ КАК МОНРЕАЛЬСКАЯ КОНВЕНЦИЯ
    ИЛИ ПРЕДШЕСТВУЮЩАЯ ЕЙ ВАРШАВСКАЯ КОНВЕНЦИЯ С ДОПОЛНИТЕЛЬНЫМИ СОГЛАШЕНИЯМИ К НЕЙ, МОГУТ ПРИМЕНЯТЬСЯ
    В ОТНОШЕНИИ ВСЕЙ ПЕРЕВОЗКИ, ВКЛЮЧАЯ ЛЮБОЙ ОТРЕЗОК, НАХОДЯЩИЙСЯ В ПРЕДЕЛАХ ТЕРРИТОРИИ СТРАНЫ. ДЛЯ
    ТАКИХ ПАССАЖИРОВ ПРИМЕНИМАЯ КОНВЕНЦИЯ, ВКЛЮЧАЯ ОСОБЫЕ УСЛОВИЯ ПЕРЕВОЗКИ, ОБУСЛОВЛЕННЫЕ
    ПРИМЕНЯЕМЫМИ ТАРИФАМИ, РЕГУЛИРУЮТ И МОГУТ ОГРАНИЧИВАТЬ ОТВЕТСТВЕННОСТЬ ПЕРЕВОЗЧИКА.
</div>

</body>
</html>
