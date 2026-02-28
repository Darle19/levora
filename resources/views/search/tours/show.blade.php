@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to results
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <!-- Header Section -->
            <div class="relative">
                <!-- Hero Image -->
                <div class="h-96 bg-gradient-to-br from-green-600 via-green-700 to-emerald-700 flex items-center justify-center">
                    <svg class="h-32 w-32 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <!-- Badges -->
                <div class="absolute top-4 left-4 space-y-2">
                    @if($tour->is_hot)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white">
                            Hot Deal
                        </span>
                    @endif
                    @if($tour->instant_confirmation)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500 text-white block">
                            Instant Confirmation
                        </span>
                    @endif
                    @if($tour->no_stop_sale)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white block">
                            No Stop Sale
                        </span>
                    @endif
                </div>
            </div>

            <!-- Main Content -->
            <div class="p-6 md:p-8">
                <div class="lg:grid lg:grid-cols-3 lg:gap-8">
                    <!-- Left Column - Details -->
                    <div class="lg:col-span-2">
                        <!-- Hotel Name and Stars -->
                        <div class="mb-6">
                            <div class="flex items-center mb-2">
                                <h1 class="text-3xl font-bold text-gray-900">
                                    {{ $tour->hotel->name ?? 'N/A' }}
                                </h1>
                                @if($tour->hotel && $tour->hotel->category)
                                    <div class="ml-3 flex items-center">
                                        @for($i = 0; $i < $tour->hotel->category->stars; $i++)
                                            <svg class="h-5 w-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center text-gray-600">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-lg">{{ $tour->country->name_en ?? 'N/A' }}{{ $tour->resort ? ', ' . $tour->resort->name_en : '' }}</span>
                            </div>
                        </div>

                        <!-- Quick Info Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Tour Type</div>
                                <div class="font-semibold text-gray-900">{{ $tour->tourType->name_en ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Program</div>
                                <div class="font-semibold text-gray-900">{{ $tour->programType->name_en ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Transport</div>
                                <div class="font-semibold text-gray-900">{{ $tour->transportType->name_en ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Meal Type</div>
                                <div class="font-semibold text-gray-900">{{ $tour->mealType->code ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <!-- Tour Information Tabs -->
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex space-x-8">
                                <button onclick="showTab('details')" id="tab-details"
                                    class="tab-button border-b-2 border-green-600 py-4 px-1 text-sm font-medium text-green-700">
                                    Details
                                </button>
                                <button onclick="showTab('hotel')" id="tab-hotel"
                                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                    Hotel Info
                                </button>
                                <button onclick="showTab('conditions')" id="tab-conditions"
                                    class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                    Conditions
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Contents -->
                        <div id="content-details" class="tab-content">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Tour Details</h2>

                            <div class="space-y-4">
                                <!-- Dates -->
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-gray-900">Travel Dates</h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $tour->date_from ? $tour->date_from->format('F d, Y') : 'N/A' }} -
                                            {{ $tour->date_to ? $tour->date_to->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-gray-900">Duration</h3>
                                        <p class="mt-1 text-sm text-gray-600">{{ $tour->nights ?? 0 }} nights</p>
                                    </div>
                                </div>

                                <!-- Departure -->
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-gray-900">Departure City</h3>
                                        <p class="mt-1 text-sm text-gray-600">{{ $tour->departureCity->name_en ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <!-- Guests -->
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-gray-900">Guests</h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $tour->adults ?? 0 }} Adults{{ $tour->children > 0 ? ', ' . $tour->children . ' Children' : '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="content-hotel" class="tab-content hidden">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Hotel Information</h2>

                            @if($tour->hotel)
                                <div class="space-y-4">
                                    <!-- Hotel Name and Category -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $tour->hotel->name }}</h3>
                                        @if($tour->hotel->category)
                                            <div class="flex items-center mb-3">
                                                <span class="text-sm text-gray-600 mr-2">Category:</span>
                                                <div class="flex">
                                                    @for($i = 0; $i < $tour->hotel->category->stars; $i++)
                                                        <svg class="h-5 w-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                        </svg>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Address -->
                                    @if($tour->hotel->address)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 mb-1">Address</h4>
                                            <p class="text-sm text-gray-600">{{ $tour->hotel->address }}</p>
                                        </div>
                                    @endif

                                    <!-- Description -->
                                    @if($tour->hotel->description)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 mb-1">Description</h4>
                                            <p class="text-sm text-gray-600">{{ $tour->hotel->description }}</p>
                                        </div>
                                    @endif

                                    <!-- Facilities -->
                                    @if($tour->hotel->facilities)
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 mb-2">Facilities</h4>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach(json_decode($tour->hotel->facilities, true) ?? [] as $facility)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $facility }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-gray-600">Hotel information not available.</p>
                            @endif
                        </div>

                        <div id="content-conditions" class="tab-content hidden">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Terms and Conditions</h2>

                            <div class="space-y-6">
                                <!-- Booking Conditions -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Booking Conditions</h3>
                                    <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                                        <li>Passport must be valid for at least 6 months from the date of departure</li>
                                        <li>Full payment is required at the time of booking</li>
                                        <li>Booking confirmation is subject to availability</li>
                                        @if($tour->instant_confirmation)
                                            <li class="text-green-600 font-medium">This tour offers instant confirmation</li>
                                        @endif
                                    </ul>
                                </div>

                                <!-- Cancellation Policy -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Cancellation Policy</h3>
                                    <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                                        <li>Cancellation 30+ days before departure: 10% penalty</li>
                                        <li>Cancellation 15-29 days before departure: 50% penalty</li>
                                        <li>Cancellation less than 15 days: 100% penalty</li>
                                    </ul>
                                </div>

                                <!-- Important Notes -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Important Notes</h3>
                                    <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
                                        <li>Prices are subject to change until booking is confirmed</li>
                                        <li>Additional fees may apply for visa processing</li>
                                        <li>Travel insurance is recommended but not included</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Booking Card -->
                    <div class="lg:col-span-1 mt-8 lg:mt-0">
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm sticky top-4">
                            <div class="p-6">
                                <!-- Price -->
                                <div class="mb-6">
                                    <div class="text-sm text-gray-500 mb-1">Price per person from</div>
                                    <div class="flex items-end">
                                        <span class="text-4xl font-bold text-green-700">{{ number_format($tour->price, 0) }}</span>
                                        <span class="text-xl text-gray-600 ml-2 mb-1">{{ $tour->currency->code ?? 'USD' }}</span>
                                    </div>

                                    @php
                                        $activeTourPrices = $tour->tourPrices->where('is_active', true);
                                        $markup = $tour->markup_percent ?? \App\Models\Setting::getValue('tour_markup_percent', 15);
                                    @endphp

                                    @if($activeTourPrices->isNotEmpty())
                                        {{-- Room type pricing table --}}
                                        <div class="mt-3 pt-3 border-t border-gray-100">
                                            <div class="text-xs font-medium text-gray-700 mb-2">Room Types & Pricing</div>
                                            <div class="space-y-2">
                                                @foreach($activeTourPrices as $tp)
                                                    <div class="p-2 bg-gray-50 rounded text-xs">
                                                        <div class="font-semibold text-gray-900 mb-1">{{ $tp->roomType->name_en ?? $tp->roomType->code }}</div>
                                                        <div class="grid grid-cols-3 gap-1 text-gray-600">
                                                            <div>
                                                                <span class="text-gray-400">Adult:</span>
                                                                <span class="font-medium text-gray-800">{{ number_format($tp->price_adult, 0) }}</span>
                                                            </div>
                                                            @if($tp->price_child)
                                                                <div>
                                                                    <span class="text-gray-400">Child:</span>
                                                                    <span class="font-medium text-gray-800">{{ number_format($tp->price_child, 0) }}</span>
                                                                </div>
                                                            @endif
                                                            @if($tp->price_infant)
                                                                <div>
                                                                    <span class="text-gray-400">Infant:</span>
                                                                    <span class="font-medium text-gray-800">{{ number_format($tp->price_infant, 0) }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @foreach($tour->flights as $flight)
                                                <div class="flex justify-between text-xs text-gray-500 mt-2">
                                                    <span>{{ ucfirst($flight->pivot->direction) }} flight (adult)</span>
                                                    <span>{{ number_format($flight->price_adult, 0) }} {{ $flight->currency->code ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex justify-between text-xs font-medium text-gray-700 pt-1 mt-1 border-t border-gray-100">
                                                <span>+ Markup</span>
                                                <span>{{ $markup }}%</span>
                                            </div>
                                        </div>
                                    @elseif($tour->hotel && $tour->hotel->price_per_person)
                                        <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                                            <div class="flex justify-between">
                                                <span>Hotel (per person)</span>
                                                <span>{{ number_format($tour->hotel->price_per_person, 0) }} {{ $tour->hotel->currency->code ?? '' }}</span>
                                            </div>
                                            @foreach($tour->flights as $flight)
                                                <div class="flex justify-between">
                                                    <span>{{ ucfirst($flight->pivot->direction) }} flight</span>
                                                    <span>{{ number_format($flight->price_adult, 0) }} {{ $flight->currency->code ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex justify-between font-medium text-gray-700 pt-1 border-t border-gray-100">
                                                <span>+ Markup</span>
                                                <span>{{ $markup }}%</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Quick Info -->
                                <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Duration</span>
                                        <span class="font-medium text-gray-900">{{ $tour->nights ?? 0 }} nights</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Departure</span>
                                        <span class="font-medium text-gray-900">{{ $tour->departureCity->name_en ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Date</span>
                                        <span class="font-medium text-gray-900">{{ $tour->date_from ? $tour->date_from->format('d.m.Y') : 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Meal</span>
                                        <span class="font-medium text-gray-900">{{ $tour->mealType->code ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <!-- Booking Button -->
                                <a href="{{ route('bookings.create', $tour) }}"
                                    class="block w-full text-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                    Book This Tour
                                </a>

                                <!-- Contact Info -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <p class="text-sm text-gray-600 text-center mb-3">Need help with booking?</p>
                                    <a href="#" class="block w-full text-center px-6 py-2 border border-green-700 text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                        Contact Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active state from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-green-600', 'text-green-700');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Set active state on selected tab button
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add('border-green-600', 'text-green-700');
}
</script>
@endsection
