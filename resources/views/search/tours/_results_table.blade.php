@if($tours->count() > 0)
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        @if($groupByHotel)
            {{-- Grouped by Hotel View --}}
            @php
                $groupedTours = $tours->groupBy('hotel_id');
            @endphp

            @foreach($groupedTours as $hotelId => $hotelTours)
                @php
                    $firstTour = $hotelTours->first();
                    $hotel = $firstTour->hotel;
                @endphp

                <table class="min-w-full divide-y divide-gray-200">
                    {{-- Hotel Group Header --}}
                    <thead>
                        <tr class="hotel-group-header bg-gradient-to-r from-green-50 to-green-100 cursor-pointer hover:from-green-100 hover:to-green-200 transition-colors"
                            data-hotel-id="{{ $hotelId }}">
                            <th colspan="9" class="px-6 py-4 text-left">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="toggle-icon text-green-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h3 class="text-lg font-bold text-gray-900">
                                                    {{ $hotel->name ?? __('messages.unknown_hotel') }}
                                                </h3>
                                                @if($hotel && $hotel->category)
                                                    <div class="flex items-center space-x-0.5">
                                                        @for($i = 0; $i < $hotel->category->stars; $i++)
                                                            <svg class="h-4 w-4 text-yellow-500 fill-current" viewBox="0 0 20 20">
                                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                            </svg>
                                                        @endfor
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                @if($firstTour->resort)
                                                    <span class="flex items-center">
                                                        <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                        {{ $firstTour->resort->name_en }}, {{ $firstTour->country->name_en ?? '' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $hotelTours->count() }} {{ __('messages.tours') }}
                                    </div>
                                </div>
                            </th>
                        </tr>

                        {{-- Column Headers --}}
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                <a href="#" class="sortable-header hover:text-green-700" data-sort="date_from">
                                    {{ __('messages.check_in') }}
                                    @if($sortBy == 'date_from')
                                        <span class="inline-block ml-1">
                                            @if($sortDir == 'asc')
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.tour') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                <a href="#" class="sortable-header hover:text-green-700" data-sort="nights">
                                    {{ __('messages.nights') }}
                                    @if($sortBy == 'nights')
                                        <span class="inline-block ml-1">
                                            @if($sortDir == 'asc')
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.avail') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.meal') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.room') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                <a href="#" class="sortable-header hover:text-green-700" data-sort="price">
                                    {{ __('messages.price') }}
                                    @if($sortBy == 'price')
                                        <span class="inline-block ml-1">
                                            @if($sortDir == 'asc')
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.transport') }}
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-t border-gray-200">
                                {{ __('messages.actions') }}
                            </th>
                        </tr>
                    </thead>

                    {{-- Tour Rows --}}
                    <tbody id="hotel-group-{{ $hotelId }}" class="bg-white divide-y divide-gray-100">
                        @foreach($hotelTours as $tour)
                            <tr class="card-hover hover:bg-green-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900">
                                        {{ $tour->tourType->name_en ?? '-' }}
                                    </div>
                                    @if($tour->programType)
                                        <div class="text-xs text-gray-600">
                                            {{ $tour->programType->name_en }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $tour->nights ?? 0 }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    @if($tour->availability_status == 'available' || $tour->available_seats > 5)
                                        <span class="inline-block w-3 h-3 bg-green-500 rounded-full" title="{{ __('messages.available') }}"></span>
                                    @elseif($tour->available_seats > 0)
                                        <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full" title="{{ __('messages.limited') }}"></span>
                                    @else
                                        <span class="inline-block w-3 h-3 bg-red-500 rounded-full" title="{{ __('messages.sold_out') }}"></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($tour->mealType)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($tour->mealType->code == 'AI') bg-purple-100 text-purple-800
                                            @elseif($tour->mealType->code == 'FB') bg-blue-100 text-blue-800
                                            @elseif($tour->mealType->code == 'HB') bg-green-100 text-green-800
                                            @elseif($tour->mealType->code == 'BB') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $tour->mealType->code }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    @if($tour->tourPrices && $tour->tourPrices->first())
                                        {{ $tour->tourPrices->first()->roomType->name_en ?? __('messages.standard') }}
                                    @else
                                        {{ __('messages.standard') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="text-lg font-bold text-green-700">
                                        {{ number_format($tour->price, 0) }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        {{ $tour->currency->code ?? 'USD' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="flex items-center">
                                        @if($tour->transportType)
                                            @if(str_contains(strtolower($tour->transportType->name_en), 'flight') || str_contains(strtolower($tour->transportType->name_en), 'plane'))
                                                <svg class="h-4 w-4 mr-1 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @elseif(str_contains(strtolower($tour->transportType->name_en), 'bus'))
                                                <svg class="h-4 w-4 mr-1 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            @endif
                                            <span class="text-xs">{{ $tour->transportType->name_en }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('tours.show', $tour) }}"
                                            class="text-green-700 hover:text-green-900 text-xs font-medium"
                                            title="{{ __('messages.view_details') }}">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('bookings.create', $tour) }}"
                                            class="btn-primary inline-flex items-center px-3 py-1 bg-green-700 hover:bg-green-800 text-white text-xs font-medium rounded shadow-sm"
                                            title="{{ __('messages.book_now') }}">
                                            {{ __('messages.book') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

        @else
            {{-- Standard Table View (Not Grouped) --}}
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <a href="#" class="sortable-header hover:text-green-700" data-sort="date_from">
                                {{ __('messages.check_in') }}
                                @if($sortBy == 'date_from')
                                    <span class="inline-block ml-1">
                                        @if($sortDir == 'asc')
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        @else
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.tour') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <a href="#" class="sortable-header hover:text-green-700" data-sort="nights">
                                {{ __('messages.nights') }}
                                @if($sortBy == 'nights')
                                    <span class="inline-block ml-1">
                                        @if($sortDir == 'asc')
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        @else
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <a href="#" class="sortable-header hover:text-green-700" data-sort="hotel_name">
                                {{ __('messages.hotel') }}
                                @if($sortBy == 'hotel_name')
                                    <span class="inline-block ml-1">
                                        @if($sortDir == 'asc')
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        @else
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.avail') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.meal') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.room') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <a href="#" class="sortable-header hover:text-green-700" data-sort="price">
                                {{ __('messages.price') }}
                                @if($sortBy == 'price')
                                    <span class="inline-block ml-1">
                                        @if($sortDir == 'asc')
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        @else
                                            <svg class="h-3 w-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.transport') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            {{ __('messages.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($tours as $tour)
                        <tr class="card-hover hover:bg-green-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $tour->date_from ? $tour->date_from->format('d.m.Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $tour->tourType->name_en ?? '-' }}
                                </div>
                                @if($tour->programType)
                                    <div class="text-xs text-gray-600">
                                        {{ $tour->programType->name_en }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $tour->nights ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $tour->hotel->name ?? '-' }}
                                    @if($tour->hotel && $tour->hotel->category)
                                        <div class="inline-flex items-center space-x-0.5 ml-1">
                                            @for($i = 0; $i < $tour->hotel->category->stars; $i++)
                                                <svg class="h-3 w-3 text-yellow-500 fill-current" viewBox="0 0 20 20">
                                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    @endif
                                </div>
                                @if($tour->resort)
                                    <div class="text-xs text-gray-600">{{ $tour->resort->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($tour->availability_status == 'available' || $tour->available_seats > 5)
                                    <span class="inline-block w-3 h-3 bg-green-500 rounded-full" title="{{ __('messages.available') }}"></span>
                                @elseif($tour->available_seats > 0)
                                    <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full" title="{{ __('messages.limited') }}"></span>
                                @else
                                    <span class="inline-block w-3 h-3 bg-red-500 rounded-full" title="{{ __('messages.sold_out') }}"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($tour->mealType)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @if($tour->mealType->code == 'AI') bg-purple-100 text-purple-800
                                        @elseif($tour->mealType->code == 'FB') bg-blue-100 text-blue-800
                                        @elseif($tour->mealType->code == 'HB') bg-green-100 text-green-800
                                        @elseif($tour->mealType->code == 'BB') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $tour->mealType->code }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @if($tour->tourPrices && $tour->tourPrices->first())
                                    {{ $tour->tourPrices->first()->roomType->name_en ?? __('messages.standard') }}
                                @else
                                    {{ __('messages.standard') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="text-lg font-bold text-green-700">
                                    {{ number_format($tour->price, 0) }}
                                </div>
                                <div class="text-xs text-gray-600">
                                    {{ $tour->currency->code ?? 'USD' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="flex items-center">
                                    @if($tour->transportType)
                                        @if(str_contains(strtolower($tour->transportType->name_en), 'flight') || str_contains(strtolower($tour->transportType->name_en), 'plane'))
                                            <svg class="h-4 w-4 mr-1 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @elseif(str_contains(strtolower($tour->transportType->name_en), 'bus'))
                                            <svg class="h-4 w-4 mr-1 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        @endif
                                        <span class="text-xs">{{ $tour->transportType->name_en }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('tours.show', $tour) }}"
                                        class="text-green-700 hover:text-green-900 text-xs font-medium"
                                        title="{{ __('messages.view_details') }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('bookings.create', $tour) }}"
                                        class="btn-primary inline-flex items-center px-3 py-1 bg-green-700 hover:bg-green-800 text-white text-xs font-medium rounded shadow-sm"
                                        title="{{ __('messages.book_now') }}">
                                        {{ __('messages.book') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@else
    {{-- No Results --}}
    <div class="bg-white shadow-sm rounded-lg p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">{{ __('messages.no_tours_found') }}</h3>
        <p class="mt-2 text-sm text-gray-500">{{ __('messages.try_adjusting_filters') }}</p>
        <div class="mt-6">
            <a href="{{ route('search.tours') }}"
                class="btn-primary inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                {{ __('messages.start_new_search') }}
            </a>
        </div>
    </div>
@endif
