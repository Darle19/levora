@extends('layouts.app')

@section('content')
<style>
    .bk { font-family: inherit; font-size: 12px; color: #222; }
    .bk .section { background: #fff; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 12px; }
    .bk .section-title {
        background: linear-gradient(to bottom, #f5f7fa, #e8ecf1);
        border-bottom: 1px solid #ccc; padding: 6px 10px; font-size: 13px; font-weight: 700; color: #333;
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
    .bk input[type="text"] { text-transform: uppercase; }
    .bk input[type="date"] { width: 130px; }
    .bk select { cursor: pointer; }
    .bk .btn-search {
        background: linear-gradient(to bottom, #1B6B2E, #145222); color: #fff;
        border: 1px solid #0F3D1A; border-radius: 4px; padding: 6px 20px;
        font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .bk .btn-search:hover { background: linear-gradient(to bottom, #238636, #1A6B2E); }
    .bk .payment-terms { background: #fffde7; border: 1px solid #e0d080; border-radius: 4px; padding: 10px 14px; margin-bottom: 12px; font-size: 11px; line-height: 1.6; }
    .bk .payment-terms ul { margin: 6px 0; padding-left: 20px; }
    .bk .payment-terms li { margin-bottom: 4px; }
    .bk .price-box { background: #f0fff4; border: 1px solid #b0d8b8; border-radius: 4px; padding: 10px 14px; }
    .bk .price-big { font-size: 22px; font-weight: 700; color: #1B6B2E; }
    .bk .price-alt { font-size: 13px; color: #666; }
    .bk .tourist-section { border: 1px solid #b0d8b8; border-radius: 4px; margin-bottom: 12px; }
    .bk .tourist-header {
        background: linear-gradient(to bottom, #d0f0d8, #b8e8c4);
        border-bottom: 1px solid #a0ccb0; padding: 5px 10px;
        font-size: 12px; font-weight: 700; color: #1a5c2e;
        border-radius: 4px 4px 0 0;
    }
    .bk .tourist-body { padding: 8px 10px; }
    .bk .spo-code { display: inline-block; background: #e8ffe8; border: 1px solid #b0d8b0; border-radius: 3px; padding: 1px 6px; font-weight: 600; font-size: 11px; color: #1B6B2E; }
    .bk .btn-add {
        background: linear-gradient(to bottom, #eee, #ddd); color: #333;
        border: 1px solid #aaa; border-radius: 3px; padding: 4px 14px;
        font-size: 12px; cursor: pointer;
    }
    .bk .btn-add:hover { background: linear-gradient(to bottom, #f5f5f5, #e5e5e5); }
    .bk .btn-remove { color: #c00; font-size: 11px; cursor: pointer; text-decoration: underline; float: right; background: none; border: none; }
    .bk .notes-grid { display: flex; flex-wrap: wrap; gap: 6px 16px; }
    .bk .notes-grid label { font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; }
</style>

<div class="bk" style="max-width: 1100px; margin: 20px auto; padding: 0 10px;">

    {{-- ═══ TOUR DESCRIPTION ═══ --}}
    <div class="section">
        <div class="section-title">Тур / Tour</div>
        <div class="section-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Описание тура</th>
                        <th>СПО</th>
                        <th>Маршрут</th>
                        <th>Продолжительность</th>
                        <th>Ночей</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stayHotels as $sh)
                    <tr>
                        <td style="font-weight:600;">
                            {{ strtoupper($sh['hotel']->name ?? 'N/A') }}
                            @if($sh['hotel']?->category)
                                {{ $sh['hotel']->category->name }}
                            @endif
                            — {{ $sh['stay']->city->name_en ?? '' }}
                            @if($loop->first)
                                ({{ strtoupper($flightPath->departureCity->name_en ?? 'TAS') }})
                            @endif
                        </td>
                        <td>@if($loop->first)<span class="spo-code">LVR-FP{{ $flightPath->id }}</span>@endif</td>
                        <td>{{ $flightPath->route_name }}</td>
                        <td>{{ $flightPath->departure_date->format('d.m.Y') }} — {{ $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('d.m.Y') }}</td>
                        <td style="text-align:center;">{{ $sh['nights'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ PAYMENT TERMS ═══ --}}
    <div class="payment-terms">
        <strong>Примите, пожалуйста, во внимание условия оплаты:</strong>
        <ul>
            <li>Туры с авиаперелетом должны быть оплачены 100% в течении текущего дня с момента подтверждения заявки (если до даты начала тура от 1 до 10 дней);</li>
            <li>Туры с авиаперелетом должны быть оплачены 100% в течении 2х банковских дней с момента подтверждения заявки (если до даты начала тура от 10 до 20 дней);</li>
            <li>Туры с авиаперелетом должны быть оплачены в размере 30% в течение 3х банковских дней с момента подтверждения заявки (если до даты начала тура от 20 до 365 дней); Оставшаяся часть оплачивается не позднее 14 дней до даты начала тура.</li>
            <li>Заявки без авиаперелета (только Отель), должны быть оплачены в размере 30% в течение 3х банковских дней; Оставшаяся часть — не позднее 14 дней до даты начала тура.</li>
            <li>В случае отсутствия своевременной оплаты заявка подлежит аннуляции согласно Договору о сотрудничестве.</li>
            <li>Норма провозимого багажа: Эконом класс — 20 кг багажа (1 место) и 5 кг ручная кладь. Сверх нормы — 100 евро.</li>
            <li>Стоимость услуги выбор места на борту 50$ за пассажира в одну сторону.</li>
            <li>При получении подтверждения заявки необходимо проверить правильность данных туристов.</li>
            <li>Ответственность за правильность и точность указанной информации несет агентство.</li>
            <li>Изменения данных о туристах возможны со штрафом 25$ за каждое изменение за 2 и более дней до вылета.</li>
            <li>Аннуляция резервации или любые изменения ОБЯЗАТЕЛЬНО должны быть отправлены в личный кабинет, а также на электронную почту и телеграм куратора.</li>
        </ul>
        Спасибо за резервацию, надеемся на дальнейшее сотрудничество.
    </div>

    <form action="{{ route('bookings.store') }}" method="POST" id="bookingForm">
        @csrf
        <input type="hidden" name="flight_path_id" value="{{ $flightPath->id }}">
        <input type="hidden" name="hotel_ids" value="{{ collect($stayHotels)->pluck('hotel.id')->filter()->implode(',') }}">

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
            <div class="section-title">Проживание / Accommodation</div>
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
                        @foreach($stayHotels as $sh)
                        <tr>
                            <td style="font-weight:600;">
                                {{ $sh['hotel']->name ?? 'N/A' }}
                                @if($sh['hotel']?->category)
                                    <span style="color:#c90;">@for($i=0;$i<$sh['hotel']->category->stars;$i++)★@endfor</span>
                                @endif
                            </td>
                            <td>{{ $sh['stay']->city->name_en ?? '' }}</td>
                            <td>DBL</td>
                            <td id="{{ $loop->first ? 'paxSummary' : '' }}">2ADL</td>
                            <td><strong>BB</strong></td>
                            <td>{{ $flightPath->departure_date->copy()->addDays($loop->index > 0 ? collect($stayHotels)->take($loop->index)->sum('nights') : 0)->format('d.m.Y') }} — {{ $flightPath->departure_date->copy()->addDays(collect($stayHotels)->take($loop->index + 1)->sum('nights'))->format('d.m.Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ TRANSPORT ═══ --}}
        <div class="section">
            <div class="section-title">Транспорт / Transport</div>
            <div class="section-body">
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
                        @foreach($flightPath->legs->sortBy('leg_order') as $leg)
                            <tr>
                                <td>{{ $leg->direction === 'outbound' ? '→ Туда' : '← Обратно' }}</td>
                                <td><strong>{{ $leg->flight->airline->name ?? '' }}</strong> {{ $leg->flight->flight_number }}</td>
                                <td>{{ $leg->flight->departure_date?->format('d.m.Y') }}</td>
                                <td>
                                    {{ $leg->flight->fromAirport->code ?? '' }}
                                    ({{ $leg->flight->fromAirport->city->name_en ?? '' }})
                                    →
                                    {{ $leg->flight->toAirport->code ?? '' }}
                                    ({{ $leg->flight->toAirport->city->name_en ?? '' }})
                                </td>
                                <td>{{ $leg->flight->departure_time ? substr($leg->flight->departure_time, 0, 5) : '' }} — {{ $leg->flight->arrival_time ? substr($leg->flight->arrival_time, 0, 5) : '' }}</td>
                                <td>{{ ucfirst($leg->flight->class_type ?? 'economy') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ ADDITIONAL SERVICES ═══ --}}
        <div class="section">
            <div class="section-title">Доп. услуги / Additional Services</div>
            <div class="section-body">
                @if(collect($stayServices)->flatMap(fn($s) => $s['mandatory']->merge($s['optional']))->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr><th></th><th>Услуга</th><th>Дата</th><th>Кол-во</th><th style="text-align:right;">Стоимость</th></tr>
                        </thead>
                        <tbody>
                        @foreach($stayServices as $si => $ss)
                            @php
                                $stayStart = $flightPath->departure_date->copy()->addDays(collect($stayHotels)->take($si)->sum('nights'));
                            @endphp
                            @foreach($ss['mandatory'] as $svc)
                            <tr style="background:#f0fff0;">
                                <td style="width:30px;">
                                    <input type="checkbox" checked disabled>
                                    <input type="hidden" name="services[]" value="{{ $svc->id }}">
                                </td>
                                <td>{{ $svc->name_en }} <span style="color:#888; font-size:11px;">({{ $ss['stay']->city->name_en }})</span></td>
                                <td>{{ $stayStart->format('d.m.Y') }}</td>
                                <td><span class="svc-pax">{{ $svc->is_per_person ? '2 ADL' : '—' }}</span></td>
                                <td style="text-align:right; font-weight:600;">${{ number_format($svc->price, 0) }}</td>
                            </tr>
                            @endforeach
                            @foreach($ss['optional'] as $svc)
                            <tr>
                                <td style="width:30px;">
                                    <input type="checkbox" name="services[]" value="{{ $svc->id }}" class="optional-service" data-price="{{ $svc->price }}" data-per-person="{{ $svc->is_per_person ? 1 : 0 }}">
                                </td>
                                <td>{{ $svc->name_en }} <span style="color:#888; font-size:11px;">({{ $ss['stay']->city->name_en }})</span></td>
                                <td>{{ $stayStart->format('d.m.Y') }}</td>
                                <td><span class="svc-pax">{{ $svc->is_per_person ? '2 ADL' : '—' }}</span></td>
                                <td style="text-align:right; font-weight:600;">${{ number_format($svc->price, 0) }}</td>
                            </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color:#888; margin:0;">Нет дополнительных услуг</p>
                @endif

                {{-- One-time services (insurance, etc.) --}}
                @if($oneTimeServices->isNotEmpty())
                    <table class="data-table" style="margin-top:10px;">
                        <thead>
                            <tr><th></th><th>Услуга (на весь тур)</th><th>Период</th><th>Кол-во</th><th style="text-align:right;"></th></tr>
                        </thead>
                        <tbody>
                            @foreach($oneTimeServices as $svc)
                            <tr style="background:#f0fff0;">
                                <td style="width:30px;">
                                    <input type="checkbox" checked disabled>
                                    <input type="hidden" name="services[]" value="{{ $svc->id }}">
                                </td>
                                <td>{{ $svc->name_en }}</td>
                                <td>{{ $flightPath->departure_date->format('d.m.Y') }} — {{ $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('d.m.Y') }}</td>
                                <td><span class="svc-pax">{{ $svc->is_per_person ? '2 ADL' : '—' }}</span></td>
                                <td style="text-align:right; color:#888; font-size:11px;">Включено / Included</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- ═══ INSURANCE ═══ --}}
        <div class="section">
            <div class="section-title">Страховка / Insurance</div>
            <div class="section-body">
                @if(isset($insurances) && $insurances->isNotEmpty())
                    <table class="data-table">
                        <thead>
                            <tr><th></th><th>Наименование</th><th>Период</th><th>Кол-во</th><th style="text-align:right;">Стоимость</th></tr>
                        </thead>
                        <tbody>
                            @foreach($insurances as $ins)
                            <tr>
                                <td style="width:30px;">
                                    <input type="checkbox" name="insurances[]" value="{{ $ins->id }}" class="optional-service"
                                        data-price="{{ $ins->price }}" data-per-person="{{ $ins->is_per_person ? 1 : 0 }}"
                                        {{ $ins->is_mandatory ? 'checked disabled' : '' }}>
                                    @if($ins->is_mandatory)
                                        <input type="hidden" name="insurances[]" value="{{ $ins->id }}">
                                    @endif
                                </td>
                                <td>{{ $ins->name_en }}</td>
                                <td>{{ $flightPath->departure_date->format('d.m.Y') }} — {{ $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('d.m.Y') }}</td>
                                <td><span class="svc-pax">2 ADL</span></td>
                                <td style="text-align:right; font-weight:600;">${{ number_format($ins->price, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color:#888; margin:0;">Нет страховых программ. Добавьте страховки в <a href="/admin/additional-services">Доп. услуги</a> с типом "insurance".</p>
                @endif
            </div>
        </div>

        {{-- ═══ TOURISTS ═══ --}}
        <div class="section">
            <div class="section-title">Информация о туристах / Tourist Info ({{ $adults }})</div>
            <div class="section-body">
                <div id="tourists-container">
                    @for($idx = 0; $idx < $adults; $idx++)
                    <div class="tourist-section" data-index="{{ $idx }}">
                        <div class="tourist-header">Tourist info {{ $idx + 1 }} @if($idx > 0)<button type="button" class="btn-remove" onclick="this.closest('.tourist-section').remove(); updateTotal(); updatePax();">Удалить</button>@endif</div>
                        <div class="tourist-body">
                            <table class="form-table">
                                <tr>
                                    <td class="lbl">MR/MRS/CHD/INF:</td>
                                    <td>
                                        <select name="tourists[{{ $idx }}][title]" onchange="updatePax()" style="width:80px;">
                                            <option value="MR">MR</option>
                                            <option value="MRS">MRS</option>
                                            <option value="CHD">CHD</option>
                                            <option value="INF">INF</option>
                                        </select>
                                    </td>
                                    <td class="lbl">Пол / Sex:</td>
                                    <td>
                                        <select name="tourists[{{ $idx }}][gender]" style="width:100px;">
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lbl">Фамилия / Lastname:</td>
                                    <td><input type="text" name="tourists[{{ $idx }}][last_name]" required placeholder="LASTNAME" style="width:180px;"></td>
                                    <td class="lbl">Имя / Firstname:</td>
                                    <td><input type="text" name="tourists[{{ $idx }}][first_name]" required placeholder="FIRSTNAME" style="width:180px;"></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Дата рождения:</td>
                                    <td><input type="date" name="tourists[{{ $idx }}][birth_date]" required></td>
                                    <td class="lbl">Страна рождения:</td>
                                    <td>
                                        <select name="tourists[{{ $idx }}][birth_country]" style="width:180px;">
                                            <option value="">—</option>
                                            @foreach($countries as $c)
                                                <option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lbl">Гражданство:</td>
                                    <td>
                                        <select name="tourists[{{ $idx }}][nationality]" style="width:180px;">
                                            @foreach($countries as $c)
                                                <option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="lbl">Тип документа:</td>
                                    <td>
                                        <select name="tourists[{{ $idx }}][document_type]" style="width:180px;">
                                            <option value="passport">Загранпаспорт</option>
                                            <option value="id_card">ID карта</option>
                                            <option value="birth_cert">Свидетельство о рождении</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lbl">Серия документа:</td>
                                    <td><input type="text" name="tourists[{{ $idx }}][document_series]" placeholder="DOCUMENT SERIES" style="width:180px;"></td>
                                    <td class="lbl">Номер документа:</td>
                                    <td><input type="text" name="tourists[{{ $idx }}][passport_number]" required placeholder="DOCUMENT NUMBER" style="width:180px;"></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Действ. до / Valid to:</td>
                                    <td><input type="date" name="tourists[{{ $idx }}][passport_expiry]" required></td>
                                    <td class="lbl">Дата выдачи:</td>
                                    <td><input type="date" name="tourists[{{ $idx }}][passport_issued]"></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Кем выдан / Issued by:</td>
                                    <td colspan="3"><input type="text" name="tourists[{{ $idx }}][issued_by]" placeholder="ISSUED BY" style="width:100%;"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @endfor
                </div>
                <button type="button" class="btn-add" onclick="addTourist()">+ Добавить туриста</button>
            </div>
        </div>

        {{-- ═══ NOTES ═══ --}}
        <div class="section">
            <div class="section-title">Примечания / Notes</div>
            <div class="section-body">
                <div class="notes-grid">
                    <label><input type="checkbox" name="notes[]" value="just_married"> just married</label>
                    <label><input type="checkbox" name="notes[]" value="sea_view"> sea view</label>
                    <label><input type="checkbox" name="notes[]" value="quiet_place"> quiet place</label>
                    <label><input type="checkbox" name="notes[]" value="regular_guest"> regular guest</label>
                    <label><input type="checkbox" name="notes[]" value="cradle"> cradle for the baby</label>
                    <label><input type="checkbox" name="notes[]" value="twin_bed"> Twin bed</label>
                    <label><input type="checkbox" name="notes[]" value="birthday"> Birthday</label>
                    <label><input type="checkbox" name="notes[]" value="high_floor"> high floor</label>
                </div>
                <div style="margin-top:8px;">
                    <textarea name="notes_text" rows="2" placeholder="Дополнительные пожелания..." style="width:100%; font-size:12px; border:1px solid #999; border-radius:3px; padding:4px; resize:vertical;"></textarea>
                </div>
            </div>
        </div>

        {{-- ═══ CONTACT INFO ═══ --}}
        <div class="section">
            <div class="section-title">Контактные данные</div>
            <div class="section-body">
                <table class="form-table">
                    <tr>
                        <td class="lbl">Контактное лицо:</td>
                        <td><input type="text" name="contact_name" required value="{{ auth()->user()->name ?? '' }}" style="width:250px;"></td>
                    </tr>
                    <tr>
                        <td class="lbl">E-mail:</td>
                        <td><input type="email" name="contact_email" required value="{{ auth()->user()->email ?? '' }}" style="width:250px;"></td>
                    </tr>
                    <tr>
                        <td class="lbl">Телефон:</td>
                        <td><input type="tel" name="contact_phone" required value="{{ auth()->user()->phone ?? '' }}" style="width:250px;"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ═══ PRICE ═══ --}}
        <div class="section">
            <div class="section-title">Стоимость / Price</div>
            <div class="section-body">
                <div class="price-box" style="text-align:center;">
                    <div class="price-alt"><span id="touristCount">{{ $adults }}</span> турист(ов) × ${{ number_format($pricePerPerson, 0) }}/чел</div>
                    <div class="price-big" id="totalPrice">${{ number_format($pricePerPerson * $adults, 0) }} USD</div>
                    <div id="optionalServicesNote" style="display:none; font-size:12px; color:#666; margin-top:4px;">+ доп. услуги: $<span id="optionalServicesCost">0</span></div>
                    <div style="font-size:12px; color:#888; margin-top:6px;">Агентское вознаграждение / Agency fee: ${{ number_format($agentFee, 0) }} на чел.</div>
                </div>
            </div>
        </div>

        {{-- ═══ SUBMIT ═══ --}}
        <div style="text-align:center; padding: 15px 0;">
            <button type="submit" class="btn-search">Оформить заявку / Booking</button>
        </div>
    </form>
</div>

<script>
// Price components (per person unless noted)
const flightTotal = {{ $flightPath->flight_total }};
const hotelRoomTotal = {{ $hotelRoomTotal }}; // full DBL room cost (not per person)
const hiddenFee = {{ $hiddenFee }};
const agentFee = {{ $agentFee }};
const mandatoryCost = {{ $mandatoryServicesCost }};

let pricePerPerson = 0;
let optionalCostPerPerson = 0;
let optionalCostFlat = 0;
let touristIndex = {{ $adults }};

function calcPricePerPerson(touristCount) {
    // ceil(people/2) rooms: 1p=1room, 2p=1room, 3p=2rooms, 4p=2rooms
    const rooms = Math.ceil(touristCount / 2);
    const hotelPerPerson = (rooms * hotelRoomTotal) / Math.max(touristCount, 1);
    return flightTotal + hotelPerPerson + hiddenFee + agentFee + mandatoryCost + optionalCostPerPerson;
}

document.querySelectorAll('.optional-service').forEach(cb => {
    cb.addEventListener('change', recalcOptionalServices);
});

function recalcOptionalServices() {
    optionalCostPerPerson = 0;
    optionalCostFlat = 0;
    document.querySelectorAll('.optional-service:checked').forEach(cb => {
        const price = parseFloat(cb.dataset.price) || 0;
        if (cb.dataset.perPerson === '1') {
            optionalCostPerPerson += price;
        } else {
            optionalCostFlat += price;
        }
    });
    const count = document.querySelectorAll('.tourist-section').length;
    pricePerPerson = calcPricePerPerson(count);

    const note = document.getElementById('optionalServicesNote');
    const costEl = document.getElementById('optionalServicesCost');
    const totalOptional = optionalCostPerPerson + optionalCostFlat;
    if (totalOptional > 0) {
        note.style.display = '';
        costEl.textContent = Math.round(totalOptional);
    } else {
        note.style.display = 'none';
    }
    updateTotal();
}

function addTourist() {
    const container = document.getElementById('tourists-container');
    const idx = touristIndex++;
    const html = `
    <div class="tourist-section" data-index="${idx}">
        <div class="tourist-header">Tourist info ${idx + 1} <button type="button" class="btn-remove" onclick="this.closest('.tourist-section').remove(); updateTotal(); updatePax();">Удалить</button></div>
        <div class="tourist-body">
            <table class="form-table">
                <tr>
                    <td class="lbl">MR/MRS/CHD/INF:</td>
                    <td><select name="tourists[${idx}][title]" onchange="updatePax()" style="width:80px;"><option value="MR">MR</option><option value="MRS">MRS</option><option value="CHD">CHD</option><option value="INF">INF</option></select></td>
                    <td class="lbl">Пол / Sex:</td>
                    <td><select name="tourists[${idx}][gender]" style="width:100px;"><option value="male">Male</option><option value="female">Female</option></select></td>
                </tr>
                <tr>
                    <td class="lbl">Фамилия / Lastname:</td>
                    <td><input type="text" name="tourists[${idx}][last_name]" required placeholder="LASTNAME" style="width:180px;"></td>
                    <td class="lbl">Имя / Firstname:</td>
                    <td><input type="text" name="tourists[${idx}][first_name]" required placeholder="FIRSTNAME" style="width:180px;"></td>
                </tr>
                <tr>
                    <td class="lbl">Дата рождения:</td>
                    <td><input type="date" name="tourists[${idx}][birth_date]" required></td>
                    <td class="lbl">Страна рождения:</td>
                    <td><select name="tourists[${idx}][birth_country]" style="width:180px;"><option value="">—</option>@foreach($countries as $c)<option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>@endforeach</select></td>
                </tr>
                <tr>
                    <td class="lbl">Гражданство:</td>
                    <td><select name="tourists[${idx}][nationality]" style="width:180px;">@foreach($countries as $c)<option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>@endforeach</select></td>
                    <td class="lbl">Тип документа:</td>
                    <td><select name="tourists[${idx}][document_type]" style="width:180px;"><option value="passport">Загранпаспорт</option><option value="id_card">ID карта</option><option value="birth_cert">Свидетельство о рождении</option></select></td>
                </tr>
                <tr>
                    <td class="lbl">Серия документа:</td>
                    <td><input type="text" name="tourists[${idx}][document_series]" placeholder="DOCUMENT SERIES" style="width:180px;"></td>
                    <td class="lbl">Номер документа:</td>
                    <td><input type="text" name="tourists[${idx}][passport_number]" required placeholder="DOCUMENT NUMBER" style="width:180px;"></td>
                </tr>
                <tr>
                    <td class="lbl">Действ. до / Valid to:</td>
                    <td><input type="date" name="tourists[${idx}][passport_expiry]" required></td>
                    <td class="lbl">Дата выдачи:</td>
                    <td><input type="date" name="tourists[${idx}][passport_issued]"></td>
                </tr>
                <tr>
                    <td class="lbl">Кем выдан / Issued by:</td>
                    <td colspan="3"><input type="text" name="tourists[${idx}][issued_by]" placeholder="ISSUED BY" style="width:100%;"></td>
                </tr>
            </table>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    updateTotal();
}

function updateTotal() {
    const count = document.querySelectorAll('.tourist-section').length;
    pricePerPerson = calcPricePerPerson(count);
    document.getElementById('touristCount').textContent = count;
    const total = (count * pricePerPerson) + optionalCostFlat;
    document.getElementById('totalPrice').textContent = '$' + Math.round(total).toLocaleString('en-US') + ' USD';
    // Update per-person display
    const perPersonEl = document.querySelector('.price-alt');
    if (perPersonEl) {
        perPersonEl.innerHTML = count + ' турист(ов) × $' + Math.round(pricePerPerson).toLocaleString('en-US') + '/чел';
    }
}

function updatePax() {
    const titles = document.querySelectorAll('select[name$="[title]"]');
    let adults = 0, children = 0, infants = 0;
    titles.forEach(s => {
        if (s.value === 'MR' || s.value === 'MRS') adults++;
        else if (s.value === 'CHD') children++;
        else if (s.value === 'INF') infants++;
    });
    const el = document.getElementById('paxSummary');
    if (el) el.textContent = adults + 'ADL' + (children ? '+' + children + 'CHD' : '') + (infants ? '+' + infants + 'INF' : '');
    // Update all service PAX displays
    document.querySelectorAll('.svc-pax').forEach(el => {
        el.textContent = adults + ' ADL' + (children ? '(' + children + ')' : '') + (infants ? '(' + infants + ')' : '');
    });
}

// Calculate on page load with actual tourist count
updateTotal();
updatePax();
</script>
@endsection
