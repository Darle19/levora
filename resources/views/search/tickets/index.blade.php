@extends('layouts.app')

@section('content')
<div class="py-8 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Flight Ticket Search</h1>
                    <p class="mt-1 text-slate-500">Find and book the best flight deals</p>
                </div>
                <div class="mt-4 md:mt-0 flex items-center space-x-2 text-sm text-slate-500">
                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>Search flights worldwide</span>
                </div>
            </div>
        </div>

        <!-- Search Form Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <form action="{{ route('search.tickets.search') }}" method="POST" id="ticketSearchForm">
                @csrf

                <!-- Trip Type Selection -->
                <div class="p-6 border-b border-slate-100">
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="trip_type" value="one_way" class="w-4 h-4 text-violet-600 border-slate-300 focus:ring-violet-500">
                            <span class="ml-2 text-sm font-medium text-slate-700 group-hover:text-violet-600">One-way</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="trip_type" value="round_trip" checked class="w-4 h-4 text-violet-600 border-slate-300 focus:ring-violet-500">
                            <span class="ml-2 text-sm font-medium text-slate-700 group-hover:text-violet-600">Round trip</span>
                        </label>
                    </div>
                </div>

                <!-- Main Search Section -->
                <div class="p-6 border-b border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Origin Airport -->
                        <div>
                            <label for="origin" class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    From <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <select id="origin" name="origin" required
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 focus:outline-none">
                                <option value="">Select departure airport</option>
                                @foreach($airports as $airport)
                                    @if($airport->is_active)
                                        <option value="{{ $airport->code }}">{{ $airport->code }} - {{ $airport->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <!-- Destination Airport -->
                        <div>
                            <label for="destination" class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    To <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <select id="destination" name="destination" required
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 focus:outline-none">
                                <option value="">Select arrival airport</option>
                                @foreach($airports as $airport)
                                    @if($airport->is_active)
                                        <option value="{{ $airport->code }}">{{ $airport->code }} - {{ $airport->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <!-- Departure Date -->
                        <div>
                            <label for="departure_date" class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Departure Date <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <input type="date" id="departure_date" name="departure_date" required value="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 focus:outline-none">
                        </div>

                        <!-- Return Date -->
                        <div id="return_date_container">
                            <label for="return_date" class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Return Date
                                </span>
                            </label>
                            <input type="date" id="return_date" name="return_date" value="{{ date('Y-m-d', strtotime('+8 days')) }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Passengers & Travel Class -->
                <div class="p-6 bg-slate-50/50 border-b border-slate-100">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Passengers -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Passengers
                            </h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="adults" class="block text-xs font-medium text-slate-500 mb-1">Adults (12+)</label>
                                    <select id="adults" name="adults"
                                        class="input-modern w-full px-3 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none">
                                        @for($i = 1; $i <= 9; $i++)
                                            <option value="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div>
                                    <label for="children" class="block text-xs font-medium text-slate-500 mb-1">Children (2-11)</label>
                                    <select id="children" name="children"
                                        class="input-modern w-full px-3 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none">
                                        @for($i = 0; $i <= 8; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div>
                                    <label for="infants" class="block text-xs font-medium text-slate-500 mb-1">Infants (0-2)</label>
                                    <select id="infants" name="infants"
                                        class="input-modern w-full px-3 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none">
                                        @for($i = 0; $i <= 4; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Travel Class & Options -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                                Travel Class & Options
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="travel_class" class="block text-xs font-medium text-slate-500 mb-1">Cabin Class</label>
                                    <select id="travel_class" name="travel_class"
                                        class="input-modern w-full px-3 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none">
                                        <option value="ECONOMY" selected>Economy</option>
                                        <option value="PREMIUM_ECONOMY">Premium Economy</option>
                                        <option value="BUSINESS">Business Class</option>
                                        <option value="FIRST">First Class</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="flex items-center cursor-pointer group">
                                        <input type="checkbox" name="non_stop" value="1"
                                            class="w-4 h-4 rounded border-slate-300 text-green-600 focus:ring-green-500">
                                        <span class="ml-2 text-sm text-slate-700 group-hover:text-green-700">Non-stop flights only</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="flex items-center text-sm text-slate-500">
                        <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Searching both local and global flight databases</span>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="button" onclick="resetForm()"
                            class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                            Reset
                        </button>
                        <button type="submit" class="btn-primary px-8 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Flights
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Information Section -->
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 card-hover">
                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Best Price Guarantee</h3>
                <p class="text-sm text-slate-500">We search both local and global databases to find you the best flight deals.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 card-hover">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Instant Confirmation</h3>
                <p class="text-sm text-slate-500">Get your flight tickets confirmed immediately upon booking.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 card-hover">
                <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">24/7 Support</h3>
                <p class="text-sm text-slate-500">Our support team is available around the clock to assist you.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Handle trip type change
    document.querySelectorAll('input[name="trip_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const returnDateContainer = document.getElementById('return_date_container');
            const returnDateInput = document.getElementById('return_date');

            if (this.value === 'one_way') {
                returnDateContainer.classList.add('opacity-50');
                returnDateInput.disabled = true;
                returnDateInput.required = false;
            } else {
                returnDateContainer.classList.remove('opacity-50');
                returnDateInput.disabled = false;
                returnDateInput.required = false;
            }
        });
    });

    // Validate departure and return dates
    document.getElementById('departure_date').addEventListener('change', function() {
        const departureDate = new Date(this.value);
        const returnDateInput = document.getElementById('return_date');
        const returnDate = new Date(returnDateInput.value);

        if (returnDate <= departureDate) {
            const newReturnDate = new Date(departureDate);
            newReturnDate.setDate(newReturnDate.getDate() + 7);
            returnDateInput.value = newReturnDate.toISOString().split('T')[0];
        }
    });

    // Validate infant count doesn't exceed adult count
    document.getElementById('infants').addEventListener('change', function() {
        const adults = parseInt(document.getElementById('adults').value);
        const infants = parseInt(this.value);

        if (infants > adults) {
            alert('Number of infants cannot exceed number of adults');
            this.value = adults;
        }
    });

    document.getElementById('adults').addEventListener('change', function() {
        const infants = parseInt(document.getElementById('infants').value);
        const adults = parseInt(this.value);

        if (infants > adults) {
            document.getElementById('infants').value = adults;
        }
    });

    // Reset form function
    function resetForm() {
        document.getElementById('ticketSearchForm').reset();
        document.getElementById('return_date_container').classList.remove('opacity-50');
        document.getElementById('return_date').disabled = false;
    }

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('departure_date').setAttribute('min', today);
    document.getElementById('return_date').setAttribute('min', today);
</script>
@endpush
@endsection
