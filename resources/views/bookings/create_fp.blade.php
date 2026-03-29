@extends('layouts.app')

@section('content')
<style>
    .book-page { max-width: 900px; margin: 40px auto; padding: 0 15px; font-family: Tahoma, Arial, sans-serif; font-size: 13px; }
    .book-section { background: #fff; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; }
    .book-section-title { background: #366383; color: #fff; padding: 8px 12px; font-weight: bold; font-size: 13px; }
    .book-section-body { padding: 12px; }
    .book-table { width: 100%; border-collapse: collapse; }
    .book-table td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 12px; }
    .book-table td:first-child { color: #666; width: 150px; }
    .book-table td:last-child { font-weight: bold; }
    .flight-leg { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
    .flight-leg:last-child { border-bottom: none; }
    .airport-code { font-weight: bold; font-size: 14px; color: #366383; }
    .flight-arrow { color: #999; }
    .flight-detail { font-size: 11px; color: #555; }
    .price-box { background: #f0f7e6; border: 2px solid #4db646; padding: 15px; text-align: center; border-radius: 4px; }
    .price-total { font-size: 24px; font-weight: bold; color: #1B6B2E; }
    .price-pp { font-size: 12px; color: #666; }
    .tourist-form { border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin-bottom: 10px; background: #fafafa; }
    .tourist-form h4 { margin: 0 0 10px; font-size: 13px; color: #366383; }
    .form-row { display: flex; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 150px; }
    .form-group label { display: block; font-size: 11px; color: #666; margin-bottom: 2px; }
    .form-group input, .form-group select { width: 100%; padding: 5px 8px; border: 1px solid #999; border-radius: 3px; font-size: 12px; box-sizing: border-box; }
    .btn-submit { background: #366383; color: #fff; border: none; padding: 10px 30px; font-size: 14px; font-weight: bold; border-radius: 3px; cursor: pointer; }
    .btn-submit:hover { background: #2a4f6a; }
    .btn-add-tourist { background: #4db646; color: #fff; border: none; padding: 5px 15px; font-size: 12px; border-radius: 3px; cursor: pointer; margin-bottom: 10px; }
</style>

<div class="book-page">
    <h2 style="color:#366383; margin-bottom:15px;">{{ $flightPath->route_name }} — {{ $flightPath->departure_date->format('d.m.Y, l') }}</h2>

    {{-- Flight legs --}}
    <div class="book-section">
        <div class="book-section-title">✈ Flights</div>
        <div class="book-section-body">
            @foreach($flightPath->legs->sortBy('leg_order') as $leg)
                <div class="flight-leg">
                    <span class="airport-code">{{ $leg->flight->fromAirport->code }}</span>
                    <span class="flight-arrow">→</span>
                    <span class="airport-code">{{ $leg->flight->toAirport->code }}</span>
                    <span class="flight-detail">
                        {{ $leg->flight->airline->name ?? '' }} {{ $leg->flight->flight_number }}
                        | {{ $leg->flight->departure_date?->format('d.m') }}
                        {{ $leg->flight->departure_time }} — {{ $leg->flight->arrival_time }}
                        | ${{ number_format($leg->flight->price_adult, 0) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Hotels --}}
    <div class="book-section">
        <div class="book-section-title">🏨 Hotels</div>
        <div class="book-section-body">
            <table class="book-table">
                @foreach($stayHotels as $sh)
                    <tr>
                        <td>{{ $sh['stay']->city->name_en ?? '' }} ({{ $sh['nights'] }}n)</td>
                        <td>
                            {{ $sh['hotel']->name ?? 'No hotel selected' }}
                            @if($sh['hotel']?->category)
                                <span style="color:#e8a500;">@for($i=0;$i<$sh['hotel']->category->stars;$i++)★@endfor</span>
                            @endif
                            @if($sh['hotel'])
                                — ${{ number_format($sh['hotel']->price_per_person, 0) }}/room × {{ $sh['nights'] }}n
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    {{-- Price breakdown --}}
    <div class="book-section">
        <div class="book-section-title">💰 Price</div>
        <div class="book-section-body">
            <table class="book-table">
                <tr><td>Flights</td><td>${{ number_format($flightPath->total_price, 0) }}</td></tr>
                <tr><td>Hotels (per person)</td><td>${{ number_format($hotelCost, 0) }}</td></tr>
                <tr><td>Transfer + fees</td><td>${{ number_format($hiddenFee + $agentFee, 0) }}</td></tr>
                <tr><td style="font-weight:bold;">Total per person</td><td style="font-size:16px; color:#1B6B2E;">${{ number_format($pricePerPerson, 0) }}</td></tr>
            </table>
        </div>
    </div>

    {{-- Tourist form --}}
    <div class="book-section">
        <div class="book-section-title">👤 Tourists</div>
        <div class="book-section-body">
            <form action="{{ route('bookings.store') }}" method="POST" id="bookingForm">
                @csrf
                <input type="hidden" name="flight_path_id" value="{{ $flightPath->id }}">
                <input type="hidden" name="hotel_ids" value="{{ collect($stayHotels)->pluck('hotel.id')->filter()->implode(',') }}">

                <div id="tourists-container">
                    <div class="tourist-form" data-index="0">
                        <h4>Tourist 1</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Title</label>
                                <select name="tourists[0][title]" required>
                                    <option value="MR">MR</option>
                                    <option value="MRS">MRS</option>
                                    <option value="CHD">CHD</option>
                                    <option value="INF">INF</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="tourists[0][first_name]" required placeholder="JOHN">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="tourists[0][last_name]" required placeholder="DOE">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="tourists[0][gender]" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Birth Date</label>
                                <input type="date" name="tourists[0][birth_date]" required>
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <select name="tourists[0][nationality]" required>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Passport Number</label>
                                <input type="text" name="tourists[0][passport_number]" required placeholder="AB1234567">
                            </div>
                            <div class="form-group">
                                <label>Passport Expiry</label>
                                <input type="date" name="tourists[0][passport_expiry]" required>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-add-tourist" onclick="addTourist()">+ Add Tourist</button>

                <div style="margin-top:15px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Name</label>
                            <input type="text" name="contact_name" required value="{{ auth()->user()->name ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" required value="{{ auth()->user()->email ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="tel" name="contact_phone" required value="{{ auth()->user()->phone ?? '' }}">
                        </div>
                    </div>
                </div>

                <div class="price-box" style="margin-top:15px;">
                    <div class="price-pp" id="totalDisplay">
                        <span id="touristCount">1</span> tourist(s) × ${{ number_format($pricePerPerson, 0) }}/person
                    </div>
                    <div class="price-total" id="totalPrice">
                        ${{ number_format($pricePerPerson, 0) }}
                    </div>
                </div>

                <div style="text-align:center; margin-top:15px;">
                    <button type="submit" class="btn-submit">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const pricePerPerson = {{ $pricePerPerson }};
    let touristIndex = 1;

    function addTourist() {
        const container = document.getElementById('tourists-container');
        const idx = touristIndex++;
        const html = `
        <div class="tourist-form" data-index="${idx}">
            <h4>Tourist ${idx + 1} <a href="#" onclick="this.parentElement.parentElement.remove(); updateTotal(); return false;" style="color:#c00;font-size:11px;float:right;">Remove</a></h4>
            <div class="form-row">
                <div class="form-group"><label>Title</label><select name="tourists[${idx}][title]" required><option value="MR">MR</option><option value="MRS">MRS</option><option value="CHD">CHD</option><option value="INF">INF</option></select></div>
                <div class="form-group"><label>First Name</label><input type="text" name="tourists[${idx}][first_name]" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="tourists[${idx}][last_name]" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Gender</label><select name="tourists[${idx}][gender]" required><option value="male">Male</option><option value="female">Female</option></select></div>
                <div class="form-group"><label>Birth Date</label><input type="date" name="tourists[${idx}][birth_date]" required></div>
                <div class="form-group"><label>Nationality</label><select name="tourists[${idx}][nationality]" required>@foreach($countries as $c)<option value="{{ $c->code }}" {{ $c->code === 'UZ' ? 'selected' : '' }}>{{ $c->name_en }}</option>@endforeach</select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Passport Number</label><input type="text" name="tourists[${idx}][passport_number]" required></div>
                <div class="form-group"><label>Passport Expiry</label><input type="date" name="tourists[${idx}][passport_expiry]" required></div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        updateTotal();
    }

    function updateTotal() {
        const count = document.querySelectorAll('.tourist-form').length;
        document.getElementById('touristCount').textContent = count;
        document.getElementById('totalPrice').textContent = '$' + (count * pricePerPerson).toLocaleString('en-US', {maximumFractionDigits: 0});
    }
</script>
@endsection
