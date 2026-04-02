@extends('layouts.app')

@section('content')
<style>
    .bk { font-family: inherit; font-size: 12px; color: #222; }
    .bk .section { background: #fff; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 12px; }
    .bk .section-title { background: linear-gradient(to bottom, #f5f7fa, #e8ecf1); border-bottom: 1px solid #ccc; padding: 6px 10px; font-size: 13px; font-weight: 700; color: #333; border-radius: 4px 4px 0 0; }
    .bk .section-body { padding: 10px; }
    .bk table { border-collapse: collapse; width: 100%; }
    .bk table.data-table th { background: #e8ecf1; border: 1px solid #bbb; padding: 4px 8px; font-size: 12px; font-weight: 600; text-align: left; }
    .bk table.data-table td { border: 1px solid #ccc; padding: 4px 8px; font-size: 12px; }
    .bk table.form-table td { padding: 3px 6px; font-size: 12px; vertical-align: middle; }
    .bk table.form-table .lbl { text-align: right; color: #555; padding-right: 8px; white-space: nowrap; width: 1%; }
    .bk select, .bk input[type="text"], .bk input[type="date"], .bk input[type="email"], .bk input[type="tel"] { background: #fff; border: 1px solid #999; border-radius: 3px; padding: 2px 4px; font-size: 12px; height: 22px; }
    .bk input[type="text"] { text-transform: uppercase; }
    .bk .btn-search { background: linear-gradient(to bottom, #1B6B2E, #145222); color: #fff; border: 1px solid #0F3D1A; border-radius: 4px; padding: 6px 20px; font-size: 13px; font-weight: 600; cursor: pointer; }
    .bk .price-box { background: #f0fff4; border: 1px solid #b0d8b8; border-radius: 4px; padding: 10px 14px; }
    .bk .price-big { font-size: 22px; font-weight: 700; color: #1B6B2E; }
    .bk .price-alt { font-size: 13px; color: #666; }
    .bk .tourist-section { border: 1px solid #b0d8b8; border-radius: 4px; margin-bottom: 12px; }
    .bk .tourist-header { background: linear-gradient(to bottom, #d0f0d8, #b8e8c4); border-bottom: 1px solid #a0ccb0; padding: 5px 10px; font-size: 12px; font-weight: 700; color: #1a5c2e; border-radius: 4px 4px 0 0; }
    .bk .tourist-body { padding: 8px 10px; }
    .bk .btn-add { background: linear-gradient(to bottom, #eee, #ddd); color: #333; border: 1px solid #aaa; border-radius: 3px; padding: 4px 14px; font-size: 12px; cursor: pointer; }
    .bk .btn-remove { color: #c00; font-size: 11px; cursor: pointer; text-decoration: underline; float: right; background: none; border: none; }
    .bk .notes-grid { display: flex; flex-wrap: wrap; gap: 6px 16px; }
    .bk .notes-grid label { font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; }
</style>

<div class="bk" style="max-width: 1100px; margin: 20px auto; padding: 0 10px;">

    {{-- ═══ HOTEL INFO ═══ --}}
    <div class="section">
        <div class="section-title">Отель / Hotel</div>
        <div class="section-body">
            <table class="data-table">
                <thead>
                    <tr><th>Hotel</th><th>City</th><th>Room</th><th>Meal</th><th>Period</th><th>Nights</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;">
                            {{ $hotel->name_en }}
                            @if($hotel->category)
                                <span style="color:#c90;">@for($i=0;$i<$hotel->category->stars;$i++)★@endfor</span>
                            @endif
                        </td>
                        <td>{{ $hotel->city->name_en ?? '' }}, {{ $hotel->city->country->name_en ?? '' }}</td>
                        <td>{{ $roomType->code ?? 'DBL' }} — {{ $roomType->name_en ?? 'Double' }}</td>
                        <td><strong>BB</strong></td>
                        <td>{{ date('d.m.Y', strtotime($checkin)) }} — {{ date('d.m.Y', strtotime($checkout)) }}</td>
                        <td style="text-align:center;">{{ $nights }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <form action="{{ route('bookings.store') }}" method="POST">
        @csrf
        <input type="hidden" name="hotel_id" value="{{ $hotel->id }}">
        <input type="hidden" name="room_type_id" value="{{ $roomType->id ?? '' }}">
        <input type="hidden" name="nights" value="{{ $nights }}">
        <input type="hidden" name="check_in_date" value="{{ $checkin }}">

        @if($errors->any())
            <div style="background:#fee;border:1px solid #e88;border-radius:4px;padding:8px 12px;margin-bottom:12px;font-size:12px;color:#900;">
                <ul style="margin:0;padding-left:16px;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

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
                                    <td><select name="tourists[{{ $idx }}][title]" onchange="updatePax()" style="width:80px;"><option value="MR">MR</option><option value="MRS">MRS</option><option value="CHD">CHD</option><option value="INF">INF</option></select></td>
                                    <td class="lbl">Пол / Sex:</td>
                                    <td><select name="tourists[{{ $idx }}][gender]" style="width:100px;"><option value="male">Male</option><option value="female">Female</option></select></td>
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
                                    <td class="lbl">Гражданство:</td>
                                    <td><select name="tourists[{{ $idx }}][nationality]" style="width:180px;">@foreach($countries as $c)<option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>@endforeach</select></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Номер паспорта:</td>
                                    <td><input type="text" name="tourists[{{ $idx }}][passport_number]" required placeholder="DOCUMENT NUMBER" style="width:180px;"></td>
                                    <td class="lbl">Действ. до:</td>
                                    <td><input type="date" name="tourists[{{ $idx }}][passport_expiry]" required></td>
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
                    <label><input type="checkbox" name="notes[]" value="sea_view"> sea view</label>
                    <label><input type="checkbox" name="notes[]" value="quiet_place"> quiet place</label>
                    <label><input type="checkbox" name="notes[]" value="high_floor"> high floor</label>
                    <label><input type="checkbox" name="notes[]" value="twin_bed"> Twin bed</label>
                    <label><input type="checkbox" name="notes[]" value="just_married"> just married</label>
                    <label><input type="checkbox" name="notes[]" value="birthday"> Birthday</label>
                </div>
                <textarea name="notes_text" rows="2" placeholder="Дополнительные пожелания..." style="width:100%; font-size:12px; border:1px solid #999; border-radius:3px; padding:4px; margin-top:6px;"></textarea>
            </div>
        </div>

        {{-- ═══ CONTACT ═══ --}}
        <div class="section">
            <div class="section-title">Контактные данные</div>
            <div class="section-body">
                <table class="form-table">
                    <tr><td class="lbl">Контактное лицо:</td><td><input type="text" name="contact_name" required value="{{ auth()->user()->name ?? '' }}" style="width:250px;"></td></tr>
                    <tr><td class="lbl">E-mail:</td><td><input type="email" name="contact_email" required value="{{ auth()->user()->email ?? '' }}" style="width:250px;"></td></tr>
                    <tr><td class="lbl">Телефон:</td><td><input type="tel" name="contact_phone" required value="{{ auth()->user()->phone ?? '' }}" style="width:250px;"></td></tr>
                </table>
            </div>
        </div>

        {{-- ═══ PRICE ═══ --}}
        <div class="section">
            <div class="section-title">Стоимость / Price</div>
            <div class="section-body">
                <div class="price-box" style="text-align:center;">
                    <div class="price-alt"><span id="touristCount">—</span> турист(ов) × $<span id="perPersonPrice">—</span>/чел</div>
                    <div class="price-big" id="totalPrice">—</div>
                </div>
            </div>
        </div>

        <div style="text-align:center; padding: 15px 0;">
            <button type="submit" class="btn-search">Оформить заявку / Booking</button>
        </div>
    </form>
</div>

<script>
const roomRate = {{ $roomRate }};
const nights = {{ $nights }};
const commission = {{ $commission }};
let touristIndex = {{ $adults }};

function calcTotal(count) {
    const rooms = Math.ceil(count / 2);
    const hotelTotal = rooms * roomRate * nights + commission;
    return Math.round(hotelTotal);
}

function updateTotal() {
    const count = document.querySelectorAll('.tourist-section').length || 1;
    const total = calcTotal(count);
    const pp = Math.round(total / count);
    document.getElementById('touristCount').textContent = count;
    document.getElementById('perPersonPrice').textContent = pp.toLocaleString('en-US');
    document.getElementById('totalPrice').textContent = '$' + total.toLocaleString('en-US') + ' USD';
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
                    <td class="lbl">Гражданство:</td>
                    <td><select name="tourists[${idx}][nationality]" style="width:180px;">@foreach($countries as $c)<option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>@endforeach</select></td>
                </tr>
                <tr>
                    <td class="lbl">Номер паспорта:</td>
                    <td><input type="text" name="tourists[${idx}][passport_number]" required placeholder="DOCUMENT NUMBER" style="width:180px;"></td>
                    <td class="lbl">Действ. до:</td>
                    <td><input type="date" name="tourists[${idx}][passport_expiry]" required></td>
                </tr>
            </table>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    updateTotal();
}

function updatePax() {
    const titles = document.querySelectorAll('select[name$="[title]"]');
    let adults = 0, children = 0, infants = 0;
    titles.forEach(s => {
        if (s.value === 'MR' || s.value === 'MRS') adults++;
        else if (s.value === 'CHD') children++;
        else if (s.value === 'INF') infants++;
    });
}

updateTotal();
</script>
@endsection
