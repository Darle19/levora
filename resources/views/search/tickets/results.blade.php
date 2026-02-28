@extends('layouts.app')

@section('content')
<div class="py-8 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Flight Search Results</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Found {{ count($flights) }} {{ count($flights) === 1 ? 'flight' : 'flights' }}
                    </p>
                </div>
                <a href="{{ route('search.tickets') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to search
                </a>
            </div>
        </div>

        <!-- Search Parameters Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex flex-wrap items-center gap-6 text-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span class="text-slate-500">Route:</span>
                    <span class="ml-2 font-semibold text-slate-800">{{ $validated['origin'] }} → {{ $validated['destination'] }}</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-slate-500">Date:</span>
                    <span class="ml-2 font-semibold text-slate-800">{{ \Carbon\Carbon::parse($validated['departure_date'])->format('d M Y') }}</span>
                    @if(!empty($validated['return_date']))
                        <span class="mx-2 text-slate-400">→</span>
                        <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($validated['return_date'])->format('d M Y') }}</span>
                    @endif
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-slate-500">Passengers:</span>
                    <span class="ml-2 font-semibold text-slate-800">
                        {{ $validated['adults'] }} {{ $validated['adults'] == 1 ? 'Adult' : 'Adults' }}
                        @if($validated['children'] > 0), {{ $validated['children'] }} {{ $validated['children'] == 1 ? 'Child' : 'Children' }}@endif
                        @if($validated['infants'] > 0), {{ $validated['infants'] }} {{ $validated['infants'] == 1 ? 'Infant' : 'Infants' }}@endif
                    </span>
                </div>
                <div class="flex items-center">
                    <span class="text-slate-500">Class:</span>
                    <span class="ml-2 font-semibold text-slate-800">{{ ucfirst(str_replace('_', ' ', $validated['travel_class'])) }}</span>
                </div>
            </div>
        </div>

        <!-- Results -->
        @if(count($flights) > 0)
            <div class="space-y-4">
                @foreach($flights as $flight)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
                        <div class="p-6">
                            <!-- Flight Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-800">{{ $flight->airlineName }}</h3>
                                        <p class="text-sm text-slate-500">{{ $flight->flightNumber }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($flight->isAmadeus)
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-violet-100 text-violet-700">
                                            Amadeus
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Local
                                        </span>
                                    @endif
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                        {{ $flight->cabinClass }}
                                    </span>
                                </div>
                            </div>

                            <!-- Outbound Flight -->
                            <div class="mb-4 pb-4 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-slate-500 uppercase">Outbound Flight</span>
                                    <span class="text-xs text-slate-500">{{ $flight->stops == 0 ? 'Non-stop' : $flight->stops . ' ' . ($flight->stops == 1 ? 'stop' : 'stops') }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-4 items-center">
                                    <!-- Departure -->
                                    <div>
                                        <div class="text-2xl font-bold text-slate-800">{{ $flight->departureTime }}</div>
                                        <div class="text-sm font-medium text-slate-600">{{ $flight->origin }}</div>
                                        <div class="text-xs text-slate-500">{{ $flight->originName }}</div>
                                        <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($flight->departureDate)->format('d M Y') }}</div>
                                    </div>

                                    <!-- Flight Duration -->
                                    <div class="text-center">
                                        <div class="relative">
                                            <div class="border-t-2 border-slate-300 absolute top-1/2 left-0 right-0"></div>
                                            <div class="relative inline-block bg-white px-3">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-4">{{ $flight->duration }}</div>
                                    </div>

                                    <!-- Arrival -->
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-slate-800">{{ $flight->arrivalTime }}</div>
                                        <div class="text-sm font-medium text-slate-600">{{ $flight->destination }}</div>
                                        <div class="text-xs text-slate-500">{{ $flight->destinationName }}</div>
                                        <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($flight->arrivalDate)->format('d M Y') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Return Flight (if applicable) -->
                            @if($flight->returnDepartureDate)
                                <div class="mb-4 pb-4 border-b border-slate-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-slate-500 uppercase">Return Flight</span>
                                        <span class="text-xs text-slate-500">{{ $flight->returnStops == 0 ? 'Non-stop' : $flight->returnStops . ' ' . ($flight->returnStops == 1 ? 'stop' : 'stops') }}</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 items-center">
                                        <!-- Departure -->
                                        <div>
                                            <div class="text-2xl font-bold text-slate-800">{{ $flight->returnDepartureTime }}</div>
                                            <div class="text-sm font-medium text-slate-600">{{ $flight->destination }}</div>
                                            <div class="text-xs text-slate-500">{{ $flight->destinationName }}</div>
                                            <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($flight->returnDepartureDate)->format('d M Y') }}</div>
                                        </div>

                                        <!-- Flight Duration -->
                                        <div class="text-center">
                                            <div class="relative">
                                                <div class="border-t-2 border-slate-300 absolute top-1/2 left-0 right-0"></div>
                                                <div class="relative inline-block bg-white px-3">
                                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-4">{{ $flight->returnDuration }}</div>
                                        </div>

                                        <!-- Arrival -->
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-slate-800">{{ $flight->returnArrivalTime }}</div>
                                            <div class="text-sm font-medium text-slate-600">{{ $flight->origin }}</div>
                                            <div class="text-xs text-slate-500">{{ $flight->originName }}</div>
                                            <div class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($flight->returnArrivalDate)->format('d M Y') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Price and Actions -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm text-slate-500 mb-1">
                                        Price per adult: <span class="font-semibold text-slate-700">{{ number_format($flight->pricePerAdult, 2) }} {{ $flight->currency }}</span>
                                        @if($flight->pricePerChild)
                                            <span class="ml-3">per child: <span class="font-semibold text-slate-700">{{ number_format($flight->pricePerChild, 2) }} {{ $flight->currency }}</span></span>
                                        @endif
                                        @if($flight->pricePerInfant)
                                            <span class="ml-3">per infant: <span class="font-semibold text-slate-700">{{ number_format($flight->pricePerInfant, 2) }} {{ $flight->currency }}</span></span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ $flight->availableSeats }} seats available
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-slate-500 mb-1">Total Price</div>
                                    <div class="text-3xl font-bold text-violet-600">
                                        {{ number_format($flight->priceTotal, 2) }}
                                    </div>
                                    <div class="text-sm text-slate-600 mb-3">{{ $flight->currency }}</div>
                                    <button type="button" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold text-white inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                        </svg>
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- No Results -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-800 mb-2">No flights found</h3>
                <p class="text-slate-500 mb-6 max-w-md mx-auto">
                    We couldn't find any flights matching your search criteria. Try adjusting your search parameters or selecting different dates.
                </p>
                <a href="{{ route('search.tickets') }}" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold text-white inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    New Search
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
