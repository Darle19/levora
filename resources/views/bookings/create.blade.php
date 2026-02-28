@extends('layouts.app')

@section('content')
<style>
    .bk { font-family: inherit; font-size: 12px; color: #222; }
    .bk .section { background: #fff; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 12px; }
    .bk .section-title {
        background: linear-gradient(to bottom, #f5f7fa, #e8ecf1);
        border-bottom: 1px solid #ccc;
        padding: 6px 10px; font-size: 13px; font-weight: 700; color: #333;
        border-radius: 4px 4px 0 0;
    }
    .bk .section-body { padding: 10px; }
    .bk table { border-collapse: collapse; width: 100%; }
    .bk table.data-table th {
        background: #e8ecf1; border: 1px solid #bbb; padding: 4px 8px;
        font-size: 12px; font-weight: 600; color: #333; text-align: left;
    }
    .bk table.data-table td {
        border: 1px solid #ccc; padding: 4px 8px; font-size: 12px; vertical-align: middle;
    }
    .bk table.data-table tr:nth-child(even) td { background: #fafbfc; }
    .bk table.form-table td { padding: 3px 6px; font-size: 12px; vertical-align: middle; }
    .bk table.form-table .lbl { text-align: right; color: #555; padding-right: 8px; white-space: nowrap; width: 1%; }
    .bk select, .bk input[type="text"], .bk input[type="date"], .bk input[type="number"], .bk input[type="email"], .bk input[type="tel"] {
        background: #fff; border: 1px solid #999; border-radius: 3px; padding: 2px 4px;
        font-size: 12px; height: 22px; line-height: 18px; box-sizing: border-box;
    }
    .bk input[type="text"], .bk input[type="email"], .bk input[type="tel"] { text-transform: uppercase; }
    .bk input[type="date"] { width: 130px; }
    .bk select { cursor: pointer; }
    .bk textarea { border: 1px solid #999; border-radius: 3px; padding: 4px 6px; font-size: 12px; width: 100%; box-sizing: border-box; resize: vertical; }
    .bk .btn-search {
        background: linear-gradient(to bottom, #1B6B2E, #145222); color: #fff;
        border: 1px solid #0F3D1A; border-radius: 4px; padding: 6px 20px;
        font-size: 13px; font-weight: 600; cursor: pointer; letter-spacing: 0.3px;
    }
    .bk .btn-search:hover { background: linear-gradient(to bottom, #238636, #1A6B2E); }
    .bk .btn-recalc {
        background: linear-gradient(to bottom, #eee, #ddd); color: #333;
        border: 1px solid #aaa; border-radius: 3px; padding: 4px 14px;
        font-size: 12px; cursor: pointer;
    }
    .bk .btn-recalc:hover { background: linear-gradient(to bottom, #f5f5f5, #e5e5e5); }
    .bk .payment-terms { background: #fffde7; border: 1px solid #e0d080; border-radius: 4px; padding: 10px 14px; margin-bottom: 12px; font-size: 11px; line-height: 1.6; }
    .bk .payment-terms ul { margin: 6px 0; padding-left: 20px; }
    .bk .payment-terms li { margin-bottom: 4px; }
    .bk .price-box {
        background: #f0fff4; border: 1px solid #b0d8b8; border-radius: 4px; padding: 10px 14px;
    }
    .bk .price-big { font-size: 22px; font-weight: 700; color: #1B6B2E; }
    .bk .price-alt { font-size: 13px; color: #666; }
    .bk .check-row { display: flex; align-items: center; gap: 6px; padding: 2px 0; }
    .bk .check-row input[type="checkbox"] { margin: 0; }
    .bk .check-row label { cursor: pointer; font-size: 12px; }
    .bk .tourist-section { border: 1px solid #b0d8b8; border-radius: 4px; margin-bottom: 12px; }
    .bk .tourist-header {
        background: linear-gradient(to bottom, #d0f0d8, #b8e8c4);
        border-bottom: 1px solid #a0ccb0; padding: 5px 10px;
        font-size: 12px; font-weight: 700; color: #1a5c2e;
        border-radius: 4px 4px 0 0;
    }
    .bk .tourist-body { padding: 8px 10px; }
    .bk .spo-code { display: inline-block; background: #e8ffe8; border: 1px solid #b0d8b0; border-radius: 3px; padding: 1px 6px; font-weight: 600; font-size: 11px; color: #1B6B2E; }
    .bk a.link { color: #1B6B2E; text-decoration: none; }
    .bk a.link:hover { text-decoration: underline; color: #F64214; }
</style>

<div class="bk" style="max-width: 1100px; margin: 20px auto; padding: 0 10px;">

    {{-- ═══ TOUR DESCRIPTION ═══ --}}
    <div class="section">
        <div class="section-title">Тур</div>
        <div class="section-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Описание тура</th>
                        <th>СПО</th>
                        <th>Страна</th>
                        <th>Продолжительность</th>
                        <th>ночей</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;">
                            {{ strtoupper($tour->hotel->name ?? 'N/A') }}
                            {{ $tour->hotel->category ? $tour->hotel->category->name : '' }}
                            — {{ $tour->resort->name_en ?? '' }}, {{ $tour->country->name_en ?? '' }}
                            ({{ strtoupper($tour->departureCity->name_en ?? 'TAS') }})
                        </td>
                        <td><span class="spo-code">LVR{{ $tour->id }}</span></td>
                        <td>{{ $tour->country->{'name_' . app()->getLocale()} ?? $tour->country->name_en ?? '' }}</td>
                        <td>{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '' }}—{{ $tour->date_to ? $tour->date_to->format('d.m.Y') : '' }}</td>
                        <td style="text-align:center;">{{ $tour->nights }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ PAYMENT TERMS ═══ --}}
    <div class="payment-terms">
        <strong>Примите, пожалуйста, во внимание условия оплаты:</strong>
        <ul>
            <li><strong>Предоплата в размере 30%</strong> должна быть произведена в течение 2 банковских дней с момента подтверждения заявки;</li>
            <li><strong>Полная оплата (100%)</strong> должна быть произведена не позднее 14 дней до даты вылета;</li>
            <li>В случае отсутствия своевременной оплаты заявка подлежит аннуляции согласно Договору о сотрудничестве;</li>
            <li>Норма провозимого багажа: Эконом класс — 20 кг багажа (1 место) и 5 кг ручная кладь, для Бизнес класса — 30 кг багажа (1 место) и 8 кг ручная кладь. Сверх нормы багажа оплачивается как дополнительное место 100 евро.</li>
            <li>Доплата за инфанта 50$ в эконом классе и 100$ в бизнес классе.</li>
            <li>Стоимость услуги выбор места на борту 50$ за пассажира в одну сторону.</li>
            <li>При получении подтверждения заявки необходимо проверить правильность данных туристов, т.к. они будут внесены во все оформляемые документы;</li>
            <li>Ответственность за правильность и точность указанной информации несет агентство.</li>
            <li>Изменения данных о туристах возможны со штрафом 25$ за каждое изменение за 2 и более дней до вылета;</li>
            <li>Замена туриста производится со штрафом 50$ за 1 человека;</li>
            <li>В случае отмены бронирования применяются штрафные санкции согласно Договору о сотрудничестве;</li>
            <li>Аннуляция резервации или любые изменения в заявке ОБЯЗАТЕЛЬНО должны быть отправлены в личный кабинет, а также на электронную почту и телеграм куратора. В противном случае резервация считается действительной и подлежит оплате в полном объеме.</li>
            <li>В "непредвиденных случаях", а также при овербукинге отеля, мы оставляем за собой право заменять отель на отель того же уровня или категорией выше.</li>
        </ul>
        <div style="margin-top:6px;color:#555;">Спасибо за резервацию, надеемся на дальнейшее сотрудничество.</div>
    </div>

    <form action="{{ route('bookings.store') }}" method="POST" id="bookingForm">
        @csrf
        <input type="hidden" name="tour_id" value="{{ $tour->id }}">

        @if($errors->any())
            <div style="background:#fee;border:1px solid #e88;border-radius:4px;padding:8px 12px;margin-bottom:12px;font-size:12px;color:#900;">
                <ul style="margin:0;padding-left:16px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ═══ ACCOMMODATION ═══ --}}
        <div class="section">
            <div class="section-title">Проживание</div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Гостиница</th>
                            <th>Город</th>
                            <th>Номер</th>
                            <th>Размещение</th>
                            <th>Питание</th>
                            <th>Период проживания</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight:600;">
                                {{ $tour->hotel->name ?? 'N/A' }}
                                @if($tour->hotel && $tour->hotel->category)
                                    <span style="color:#c90;">{{ str_repeat('★', (int) filter_var($tour->hotel->category->name, FILTER_SANITIZE_NUMBER_INT)) }}</span>
                                @endif
                            </td>
                            <td>{{ $tour->resort->name_en ?? $tour->country->name_en ?? '' }}</td>
                            <td>
                                @if($tour->tourPrices->isNotEmpty())
                                    <select name="room_type_id" id="roomTypeSelect" onchange="updatePrice()" style="width:140px;">
                                        @foreach($tour->tourPrices as $tp)
                                            <option value="{{ $tp->room_type_id }}"
                                                {{ old('room_type_id') == $tp->room_type_id ? 'selected' : '' }}>
                                                {{ $tp->roomType->name_en ?? $tp->roomType->code ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    STANDARD
                                @endif
                            </td>
                            <td id="paxSummary">{{ $tour->adults }}ADL{{ $tour->children > 0 ? '+' . $tour->children . 'CHD' : '' }}</td>
                            <td><strong>{{ $tour->mealType->code ?? 'BB' }}</strong></td>
                            <td>{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '' }}—{{ $tour->date_to ? $tour->date_to->format('d.m.Y') : '' }}</td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:6px;font-size:11px;color:#555;">
                    Проживают:
                    @for($i = 0; $i < $tour->adults; $i++)
                        Турист №{{ $i + 1 }}{{ $i < $tour->adults - 1 ? ',' : '' }}
                    @endfor
                </div>
            </div>
        </div>

        {{-- ═══ TRANSPORT ═══ --}}
        <div class="section">
            <div class="section-title">Транспорт</div>
            <div class="section-body">
                @if($tour->flights && $tour->flights->count())
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Направление</th>
                                <th>Рейс</th>
                                <th>Дата</th>
                                <th>Маршрут</th>
                                <th>Время</th>
                                <th>Класс</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tour->flights as $flight)
                                <tr>
                                    <td>{{ $flight->pivot->direction === 'outbound' ? '→ Туда' : '← Обратно' }}</td>
                                    <td><strong>{{ $flight->airline->name ?? '' }}</strong> {{ $flight->flight_number }}</td>
                                    <td>{{ $flight->departure_date ? \Carbon\Carbon::parse($flight->departure_date)->format('d.m.Y') : '' }}</td>
                                    <td>
                                        {{ $flight->fromAirport->code ?? '' }}
                                        ({{ $flight->fromAirport->city->name_en ?? '' }})
                                        →
                                        {{ $flight->toAirport->code ?? '' }}
                                        ({{ $flight->toAirport->city->name_en ?? '' }})
                                    </td>
                                    <td>{{ $flight->departure_time ? substr($flight->departure_time, 0, 5) : '' }} — {{ $flight->arrival_time ? substr($flight->arrival_time, 0, 5) : '' }}</td>
                                    <td>{{ ucfirst($flight->class_type ?? 'economy') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="color:#888;padding:4px;">Информация о транспорте отсутствует</div>
                @endif
            </div>
        </div>

        {{-- ═══ ADDITIONAL SERVICES ═══ --}}
        @if($tour->additionalServices->count())
        <div class="section">
            <div class="section-title">Дополнительные услуги</div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Кол-во</th>
                            <th style="text-align:right;">Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tour->additionalServices as $service)
                            @php
                                $servicePrice = $service->pivot->price_override ?? $service->price;
                                $isIncluded = $service->pivot->is_included;
                                $serviceCurrency = $service->currency->code ?? 'USD';
                            @endphp
                            <tr>
                                <td style="text-align:center;width:30px;">
                                    <input type="checkbox"
                                        name="additional_services[]"
                                        value="{{ $service->id }}"
                                        class="service-checkbox"
                                        data-price="{{ $servicePrice }}"
                                        data-per-person="{{ $service->is_per_person ? '1' : '0' }}"
                                        data-included="{{ $isIncluded ? '1' : '0' }}"
                                        {{ $isIncluded ? 'checked disabled' : '' }}
                                    >
                                    @if($isIncluded)
                                        <input type="hidden" name="additional_services[]" value="{{ $service->id }}">
                                    @endif
                                </td>
                                <td>
                                    {{ $service->localizedName() }}
                                    @if($service->is_per_person)
                                        <span style="color:#888;font-size:11px;">[на чел.]</span>
                                    @endif
                                    @if($isIncluded)
                                        <span style="color:#090;font-size:11px;">(включено)</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($service->service_type) }}</td>
                                <td>
                                    @if($service->is_per_person)
                                        {{ $tour->adults }} ADL({{ $tour->adults }})
                                    @else
                                        1
                                    @endif
                                </td>
                                <td style="text-align:right;font-weight:600;">
                                    @if($isIncluded)
                                        <span style="color:#090;">Включено</span>
                                    @else
                                        {{ number_format($servicePrice, 2) }} {{ $serviceCurrency }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ═══ INSURANCE ═══ --}}
        <div class="section">
            <div class="section-title">Страхование</div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Даты</th>
                            <th>Количество</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $tour->country->name_en ?? '' }}, INSURANCE (10000 USD)</td>
                            <td>{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '' }} — {{ $tour->date_to ? $tour->date_to->format('d.m.Y') : '' }}</td>
                            <td>{{ $tour->adults + $tour->children }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ TOURIST INFORMATION ═══ --}}
        <div id="touristsContainer">
            @for($t = 0; $t < max($tour->adults, 1); $t++)
            <div class="tourist-section tourist-block" data-index="{{ $t }}">
                <div class="tourist-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Информация о туристе {{ $t + 1 }}</span>
                    @if($t > 0)
                        <a href="javascript:void(0)" onclick="removeTourist(this)" style="color:#900;font-size:11px;font-weight:400;">✕ Удалить</a>
                    @endif
                </div>
                <div class="tourist-body">
                    <table class="form-table" style="width:100%;">
                        <tr>
                            <td class="lbl">MR/MRS/CHD/INF</td>
                            <td>
                                <select name="tourists[{{ $t }}][title]" required style="width:100px;" onchange="updatePrice()">
                                    <option value="">—</option>
                                    <option value="MR" {{ old("tourists.$t.title") == 'MR' ? 'selected' : '' }}>MR</option>
                                    <option value="MRS" {{ old("tourists.$t.title") == 'MRS' ? 'selected' : '' }}>MRS</option>
                                    <option value="CHD" {{ old("tourists.$t.title") == 'CHD' ? 'selected' : '' }}>CHD</option>
                                    <option value="INF" {{ old("tourists.$t.title") == 'INF' ? 'selected' : '' }}>INF</option>
                                </select>
                            </td>
                            <td class="lbl">Пол</td>
                            <td>
                                <select name="tourists[{{ $t }}][gender]" required style="width:120px;">
                                    <option value="">—</option>
                                    <option value="male" {{ old("tourists.$t.gender") == 'male' ? 'selected' : '' }}>Мужской</option>
                                    <option value="female" {{ old("tourists.$t.gender") == 'female' ? 'selected' : '' }}>Женский</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Фамилия по-латински</td>
                            <td colspan="3">
                                <input type="text" name="tourists[{{ $t }}][last_name]" required placeholder="ФАМИЛИЯ ПО-ЛАТИНСКИ"
                                    value="{{ old("tourists.$t.last_name") }}" style="width:100%;">
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Имя по-латински</td>
                            <td colspan="3">
                                <input type="text" name="tourists[{{ $t }}][first_name]" required placeholder="ИМЯ ПО-ЛАТИНСКИ"
                                    value="{{ old("tourists.$t.first_name") }}" style="width:100%;">
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Дата рождения</td>
                            <td>
                                <input type="date" name="tourists[{{ $t }}][birth_date]" required
                                    value="{{ old("tourists.$t.birth_date") }}">
                            </td>
                            <td class="lbl">Страна рождения</td>
                            <td>
                                <select name="tourists[{{ $t }}][birth_country]" style="width:100%;">
                                    <option value="">—</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->name_en }}" {{ old("tourists.$t.birth_country") == $c->name_en ? 'selected' : '' }}>{{ $c->{'name_' . app()->getLocale()} }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Гражданство</td>
                            <td colspan="3">
                                <select name="tourists[{{ $t }}][nationality]" required style="width:200px;">
                                    <option value="">—</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->name_en }}" {{ old("tourists.$t.nationality", 'Uzbekistan') == $c->name_en ? 'selected' : '' }}>{{ $c->{'name_' . app()->getLocale()} }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Тип документа</td>
                            <td colspan="3">
                                <select name="tourists[{{ $t }}][document_type]" style="width:200px;">
                                    <option value="passport" {{ old("tourists.$t.document_type", 'passport') == 'passport' ? 'selected' : '' }}>Заграничный паспорт</option>
                                    <option value="birth_certificate" {{ old("tourists.$t.document_type") == 'birth_certificate' ? 'selected' : '' }}>Свидетельство о рождении</option>
                                    <option value="id_card" {{ old("tourists.$t.document_type") == 'id_card' ? 'selected' : '' }}>ID-карта</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Серия документа</td>
                            <td>
                                <input type="text" name="tourists[{{ $t }}][passport_series]" placeholder="СЕРИЯ ДОКУМЕНТА"
                                    value="{{ old("tourists.$t.passport_series") }}" style="width:120px;">
                            </td>
                            <td class="lbl">Номер документа</td>
                            <td>
                                <input type="text" name="tourists[{{ $t }}][passport_number]" required placeholder="НОМЕР ДОКУМЕНТА"
                                    value="{{ old("tourists.$t.passport_number") }}" style="width:100%;">
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Действителен до</td>
                            <td>
                                <input type="date" name="tourists[{{ $t }}][passport_expiry]" required
                                    value="{{ old("tourists.$t.passport_expiry") }}">
                            </td>
                            <td class="lbl">Документ выдан</td>
                            <td>
                                <input type="date" name="tourists[{{ $t }}][passport_issued]"
                                    value="{{ old("tourists.$t.passport_issued") }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Кем выдан</td>
                            <td colspan="3">
                                <input type="text" name="tourists[{{ $t }}][passport_issued_by]" placeholder="КЕМ ВЫДАН"
                                    value="{{ old("tourists.$t.passport_issued_by") }}" style="width:100%;">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            @endfor
        </div>

        <div style="margin-bottom:12px;">
            <a href="javascript:void(0)" onclick="addTourist()" class="link" style="font-size:12px;">+ Добавить туриста</a>
        </div>

        {{-- ═══ NOTES / SPECIAL REQUESTS ═══ --}}
        <div class="section">
            <div class="section-title">Примечание к заявке</div>
            <div class="section-body">
                <div style="display:flex;flex-wrap:wrap;gap:4px 20px;margin-bottom:8px;">
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="honeymoon" id="sr_honey"><label for="sr_honey">молодожены</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="sea_view" id="sr_sea"><label for="sr_sea">вид на море</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="quiet" id="sr_quiet"><label for="sr_quiet">тихое место</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="regular_guest" id="sr_reg"><label for="sr_reg">постоянный гость отеля</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="baby_crib" id="sr_crib"><label for="sr_crib">люлька для младенца в номер</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="twin_beds" id="sr_twin"><label for="sr_twin">Разделные кровати</label></div>
                    <div class="check-row"><input type="checkbox" name="special_requests[]" value="birthday" id="sr_bday"><label for="sr_bday">День рождения</label></div>
                </div>
                <div style="margin-top:6px;">
                    <div style="color:#555;margin-bottom:4px;">Примечание к заявке</div>
                    <textarea name="notes" rows="3" placeholder="Дополнительные пожелания...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══ PRICE ═══ --}}
        <div class="section">
            <div class="section-title">Цена</div>
            <div class="section-body">
                <div id="priceBreakdown" style="margin-bottom:8px;font-size:12px;color:#555;display:none;">
                    <table class="data-table" style="width:auto;">
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>Кол-во</th>
                                <th style="text-align:right;">За чел.</th>
                                <th style="text-align:right;">Итого</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="priceRowAdult" style="display:none;">
                                <td>Взрослый (ADL)</td>
                                <td id="priceAdultCount">0</td>
                                <td id="priceAdultPer" style="text-align:right;">0</td>
                                <td id="priceAdultTotal" style="text-align:right;font-weight:600;">0</td>
                            </tr>
                            <tr id="priceRowChild" style="display:none;">
                                <td>Ребёнок (CHD)</td>
                                <td id="priceChildCount">0</td>
                                <td id="priceChildPer" style="text-align:right;">0</td>
                                <td id="priceChildTotal" style="text-align:right;font-weight:600;">0</td>
                            </tr>
                            <tr id="priceRowInfant" style="display:none;">
                                <td>Младенец (INF)</td>
                                <td id="priceInfantCount">0</td>
                                <td id="priceInfantPer" style="text-align:right;">0</td>
                                <td id="priceInfantTotal" style="text-align:right;font-weight:600;">0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="price-box">
                    <div class="price-big" id="totalPrice">{{ number_format($tour->price, 2) }} {{ $tour->currency->code ?? 'USD' }}</div>
                    <div class="price-alt" style="margin-top:4px;">
                        Предоплата 30% в течение 2 банковских дней с момента подтверждения.<br>
                        Полная оплата (100%) не позднее 14 дней до даты вылета.
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ CONTACT EMAIL ═══ --}}
        <div class="section">
            <div class="section-body">
                <table class="form-table">
                    <tr>
                        <td class="lbl">Контактное лицо</td>
                        <td><input type="text" name="contact_name" required value="{{ old('contact_name', auth()->user()->name) }}" style="width:250px;"></td>
                    </tr>
                    <tr>
                        <td class="lbl">Электронный адрес</td>
                        <td><input type="email" name="contact_email" required value="{{ old('contact_email', auth()->user()->email) }}" style="width:250px;text-transform:none;"></td>
                    </tr>
                    <tr>
                        <td class="lbl">Телефон</td>
                        <td><input type="tel" name="contact_phone" required value="{{ old('contact_phone', auth()->user()->phone ?? '') }}" style="width:200px;text-transform:none;"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ═══ SUBMIT ═══ --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 30px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <input type="checkbox" id="terms" required>
                <label for="terms" style="font-size:12px;cursor:pointer;">Я принимаю условия бронирования и договор о сотрудничестве</label>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('search.tours') }}" class="btn-recalc">← Назад к поиску</a>
                <button type="submit" class="btn-search">Забронировать</button>
            </div>
        </div>

    </form>
</div>

<script>
let touristIndex = {{ max($tour->adults, 1) }};
const fallbackPrice = {{ $tour->price }};
const currencyCode = '{{ $tour->currency->code ?? "USD" }}';
const markupPercent = {{ $tour->markup_percent ?? \App\Models\Setting::getValue('tour_markup_percent', 15) }};

// Tour prices by room type (hotel portion, admin-set per-person)
const tourPrices = {
    @foreach($tour->tourPrices as $tp)
    {{ $tp->room_type_id }}: {
        name: @json($tp->roomType->name_en ?? $tp->roomType->code ?? 'N/A'),
        adult: {{ (float) $tp->price_adult }},
        child: {{ (float) ($tp->price_child ?? 0) }},
        infant: {{ (float) ($tp->price_infant ?? 0) }},
        currency_id: {{ $tp->currency_id }},
    },
    @endforeach
};

// Flight prices by age category
const flightPrices = [
    @foreach($tour->flights as $flight)
    {
        adult: {{ (float) $flight->price_adult }},
        child: {{ (float) ($flight->price_child ?? $flight->price_adult) }},
        infant: {{ (float) ($flight->price_infant ?? 0) }},
        currency_id: {{ $flight->currency_id }},
    },
    @endforeach
];

const hasTourPrices = Object.keys(tourPrices).length > 0;

function addTourist() {
    const container = document.getElementById('touristsContainer');
    const idx = touristIndex;
    const div = document.createElement('div');
    div.className = 'tourist-section tourist-block';
    div.dataset.index = idx;
    div.innerHTML = `
        <div class="tourist-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Информация о туристе ${idx + 1}</span>
            <a href="javascript:void(0)" onclick="removeTourist(this)" style="color:#900;font-size:11px;font-weight:400;">✕ Удалить</a>
        </div>
        <div class="tourist-body">
            <table class="form-table" style="width:100%;">
                <tr>
                    <td class="lbl">MR/MRS/CHD/INF</td>
                    <td>
                        <select name="tourists[${idx}][title]" required style="width:100px;" onchange="updatePrice()">
                            <option value="">—</option>
                            <option value="MR">MR</option>
                            <option value="MRS">MRS</option>
                            <option value="CHD">CHD</option>
                            <option value="INF">INF</option>
                        </select>
                    </td>
                    <td class="lbl">Пол</td>
                    <td>
                        <select name="tourists[${idx}][gender]" required style="width:120px;">
                            <option value="">—</option>
                            <option value="male">Мужской</option>
                            <option value="female">Женский</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Фамилия по-латински</td>
                    <td colspan="3"><input type="text" name="tourists[${idx}][last_name]" required placeholder="ФАМИЛИЯ ПО-ЛАТИНСКИ" style="width:100%;"></td>
                </tr>
                <tr>
                    <td class="lbl">Имя по-латински</td>
                    <td colspan="3"><input type="text" name="tourists[${idx}][first_name]" required placeholder="ИМЯ ПО-ЛАТИНСКИ" style="width:100%;"></td>
                </tr>
                <tr>
                    <td class="lbl">Дата рождения</td>
                    <td><input type="date" name="tourists[${idx}][birth_date]" required></td>
                    <td class="lbl">Страна рождения</td>
                    <td>
                        <select name="tourists[${idx}][birth_country]" style="width:100%;">
                            <option value="">—</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->name_en }}">{{ $c->{'name_' . app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Гражданство</td>
                    <td colspan="3">
                        <select name="tourists[${idx}][nationality]" required style="width:200px;">
                            <option value="">—</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->name_en }}" {{ $c->name_en == 'Uzbekistan' ? 'selected' : '' }}>{{ $c->{'name_' . app()->getLocale()} }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Тип документа</td>
                    <td colspan="3">
                        <select name="tourists[${idx}][document_type]" style="width:200px;">
                            <option value="passport" selected>Заграничный паспорт</option>
                            <option value="birth_certificate">Свидетельство о рождении</option>
                            <option value="id_card">ID-карта</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Серия документа</td>
                    <td><input type="text" name="tourists[${idx}][passport_series]" placeholder="СЕРИЯ ДОКУМЕНТА" style="width:120px;"></td>
                    <td class="lbl">Номер документа</td>
                    <td><input type="text" name="tourists[${idx}][passport_number]" required placeholder="НОМЕР ДОКУМЕНТА" style="width:100%;"></td>
                </tr>
                <tr>
                    <td class="lbl">Действителен до</td>
                    <td><input type="date" name="tourists[${idx}][passport_expiry]" required></td>
                    <td class="lbl">Документ выдан</td>
                    <td><input type="date" name="tourists[${idx}][passport_issued]"></td>
                </tr>
                <tr>
                    <td class="lbl">Кем выдан</td>
                    <td colspan="3"><input type="text" name="tourists[${idx}][passport_issued_by]" placeholder="КЕМ ВЫДАН" style="width:100%;"></td>
                </tr>
            </table>
        </div>
    `;
    container.appendChild(div);
    touristIndex++;
    updatePrice();
}

function removeTourist(el) {
    const blocks = document.querySelectorAll('.tourist-block');
    if (blocks.length > 1) {
        el.closest('.tourist-block').remove();
        // Renumber headers
        document.querySelectorAll('.tourist-block').forEach((b, i) => {
            b.querySelector('.tourist-header span').textContent = 'Информация о туристе ' + (i + 1);
        });
        updatePrice();
    }
}

function countTouristsByAge() {
    let adults = 0, children = 0, infants = 0;
    document.querySelectorAll('.tourist-block').forEach(block => {
        const sel = block.querySelector('select[name*="[title]"]');
        if (!sel) return;
        const v = sel.value;
        if (v === 'MR' || v === 'MRS') adults++;
        else if (v === 'CHD') children++;
        else if (v === 'INF') infants++;
        else adults++; // default unselected counts as adult for price estimate
    });
    return { adults, children, infants };
}

function fmt(n) {
    return parseFloat(n.toFixed(2)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function updatePrice() {
    const count = document.querySelectorAll('.tourist-block').length;
    const ages = countTouristsByAge();
    const roomSelect = document.getElementById('roomTypeSelect');
    const roomTypeId = roomSelect ? parseInt(roomSelect.value) : null;
    const tp = (roomTypeId && tourPrices[roomTypeId]) ? tourPrices[roomTypeId] : null;

    let total = 0;
    const breakdown = document.getElementById('priceBreakdown');

    if (hasTourPrices && tp) {
        // Hotel portion (from tour_prices, per-person for the whole tour)
        const hotelAdult = tp.adult;
        const hotelChild = tp.child;
        const hotelInfant = tp.infant;

        // Flight portion (sum all flights, per-person)
        let flightAdult = 0, flightChild = 0, flightInfant = 0;
        flightPrices.forEach(f => {
            flightAdult += f.adult;
            flightChild += f.child;
            flightInfant += f.infant;
        });

        const perAdult = hotelAdult + flightAdult;
        const perChild = hotelChild + flightChild;
        const perInfant = hotelInfant + flightInfant;

        const baseCost = (perAdult * ages.adults) + (perChild * ages.children) + (perInfant * ages.infants);
        total = baseCost * (1 + markupPercent / 100);

        // Update breakdown table
        if (breakdown) {
            breakdown.style.display = 'block';
            const showRow = (id, count, perPerson) => {
                const row = document.getElementById(id);
                if (count > 0) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            };

            const adultTotal = perAdult * ages.adults * (1 + markupPercent / 100);
            const childTotal = perChild * ages.children * (1 + markupPercent / 100);
            const infantTotal = perInfant * ages.infants * (1 + markupPercent / 100);

            document.getElementById('priceAdultCount').textContent = ages.adults;
            document.getElementById('priceAdultPer').textContent = fmt(perAdult * (1 + markupPercent / 100)) + ' ' + currencyCode;
            document.getElementById('priceAdultTotal').textContent = fmt(adultTotal) + ' ' + currencyCode;
            showRow('priceRowAdult', ages.adults);

            document.getElementById('priceChildCount').textContent = ages.children;
            document.getElementById('priceChildPer').textContent = fmt(perChild * (1 + markupPercent / 100)) + ' ' + currencyCode;
            document.getElementById('priceChildTotal').textContent = fmt(childTotal) + ' ' + currencyCode;
            showRow('priceRowChild', ages.children);

            document.getElementById('priceInfantCount').textContent = ages.infants;
            document.getElementById('priceInfantPer').textContent = fmt(perInfant * (1 + markupPercent / 100)) + ' ' + currencyCode;
            document.getElementById('priceInfantTotal').textContent = fmt(infantTotal) + ' ' + currencyCode;
            showRow('priceRowInfant', ages.infants);
        }

        // Update pax summary in accommodation table
        const paxEl = document.getElementById('paxSummary');
        if (paxEl) {
            let parts = [];
            if (ages.adults > 0) parts.push(ages.adults + 'ADL');
            if (ages.children > 0) parts.push(ages.children + 'CHD');
            if (ages.infants > 0) parts.push(ages.infants + 'INF');
            paxEl.textContent = parts.join('+') || count + 'PAX';
        }
    } else {
        // Fallback: flat price × tourist count
        total = fallbackPrice * count;
        if (breakdown) breakdown.style.display = 'none';
    }

    // Add selected additional services
    document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
        const price = parseFloat(cb.dataset.price) || 0;
        const perPerson = cb.dataset.perPerson === '1';
        const included = cb.dataset.included === '1';
        if (!included) {
            total += perPerson ? price * count : price;
        }
    });

    document.getElementById('totalPrice').textContent = fmt(total) + ' ' + currencyCode;
}

// Recalculate when services are toggled
document.querySelectorAll('.service-checkbox').forEach(cb => {
    cb.addEventListener('change', updatePrice);
});

// Calculate correct total on page load
updatePrice();
</script>
@endsection
