@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Success Header -->
        <div class="bg-white shadow-sm rounded-lg p-8 mb-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h1>
            <p class="text-gray-600">Your tour booking has been successfully created and is pending confirmation.</p>
        </div>

        <!-- Booking Details -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Booking Details</h2>
            </div>

            <div class="p-6 space-y-6">
                <!-- Order Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Order Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Order Number</div>
                            <div class="font-medium text-gray-900">{{ $booking->order->order_number }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Status</div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($booking->status === 'confirmed') bg-green-100 text-green-800
                                @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Booking Date</div>
                            <div class="font-medium text-gray-900">{{ $booking->created_at->format('F d, Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Total Amount</div>
                            <div class="font-medium text-gray-900">{{ number_format($booking->price, 0) }} {{ $booking->currency->code ?? 'USD' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tour Information -->
                @if($booking->bookable instanceof \App\Models\Tour)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Tour Information</h3>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-600">Hotel</div>
                                    <div class="font-medium text-gray-900">{{ $booking->bookable->hotel->name ?? 'N/A' }}</div>
                                    @if($booking->bookable->hotel && $booking->bookable->hotel->category)
                                        <div class="flex items-center mt-1">
                                            @for($i = 0; $i < $booking->bookable->hotel->category->stars; $i++)
                                                <svg class="h-4 w-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-600">Destination</div>
                                    <div class="font-medium text-gray-900">
                                        {{ $booking->bookable->country->name_en ?? 'N/A' }}{{ $booking->bookable->resort ? ', ' . $booking->bookable->resort->name_en : '' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-600">Travel Dates</div>
                                    <div class="font-medium text-gray-900">
                                        {{ $booking->bookable->date_from ? $booking->bookable->date_from->format('F d, Y') : 'N/A' }} -
                                        {{ $booking->bookable->date_to ? $booking->bookable->date_to->format('F d, Y') : 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-600">Duration</div>
                                    <div class="font-medium text-gray-900">{{ $booking->bookable->nights ?? 0 }} nights</div>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-600">Meal Type</div>
                                    <div class="font-medium text-gray-900">{{ $booking->bookable->mealType->code ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Travelers Information -->
                @if($booking->tourists->count() > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Travelers ({{ $booking->tourists->count() }})</h3>
                        <div class="space-y-3">
                            @foreach($booking->tourists as $index => $tourist)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="font-medium text-gray-900 mb-2">Traveler {{ $index + 1 }}</div>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <div class="text-gray-600">Name</div>
                                            <div class="font-medium text-gray-900">{{ $tourist->first_name }} {{ $tourist->last_name }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-600">Birth Date</div>
                                            <div class="font-medium text-gray-900">{{ $tourist->birth_date ? $tourist->birth_date->format('d.m.Y') : 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-600">Gender</div>
                                            <div class="font-medium text-gray-900">{{ ucfirst($tourist->gender ?? 'N/A') }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-600">Passport</div>
                                            <div class="font-medium text-gray-900">{{ $tourist->passport_number ?? 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-600">Passport Expiry</div>
                                            <div class="font-medium text-gray-900">{{ $tourist->passport_expiry ? $tourist->passport_expiry->format('d.m.Y') : 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-600">Nationality</div>
                                            <div class="font-medium text-gray-900">{{ $tourist->nationality ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-green-900 mb-3">What's Next?</h3>
            <ul class="space-y-2 text-sm text-green-800">
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>You will receive a confirmation email with your booking details shortly.</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Our team will review your booking and confirm availability within 24 hours.</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>You can track your booking status in your claims section.</span>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('claims.show', $booking->order) }}"
                class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                View Order Details
            </a>
            <a href="{{ route('search.tours') }}"
                class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Search More Tours
            </a>
            <a href="{{ route('dashboard') }}"
                class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Go to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
