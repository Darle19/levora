@extends('layouts.app')

@section('content')
<div class="py-8 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Welcome Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">{{ now()->format('H') < 12 ? __('messages.dashboard.good_morning') : (now()->format('H') < 17 ? __('messages.dashboard.good_afternoon') : __('messages.dashboard.good_evening')) }}, {{ Auth::user()->name }}!</h1>
                    <p class="mt-1 text-slate-500">{{ __('messages.dashboard.whats_happening') }}</p>
                </div>
                <div class="mt-4 lg:mt-0 flex items-center space-x-3">
                    <span class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm text-slate-600">
                        <span class="font-medium">{{ Auth::user()->agency->name ?? 'N/A' }}</span>
                    </span>
                    <a href="{{ route('search.tours') }}" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        {{ __('messages.dashboard.new_search') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Claims -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.total_claims') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">0</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('messages.dashboard.all_time') }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Bookings -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.active_bookings') }}</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">0</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('messages.dashboard.confirmed_tours') }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.pending') }}</p>
                        <p class="mt-2 text-3xl font-bold text-amber-600">0</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('messages.dashboard.awaiting') }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.revenue') }}</p>
                        <p class="mt-2 text-3xl font-bold text-violet-600">$0</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('messages.dashboard.this_month') }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-violet-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('messages.dashboard.quick_actions') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <a href="{{ route('search.tours') }}" class="bg-white rounded-2xl p-5 border border-slate-200 text-center card-hover group">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ __('messages.nav.tours') }}</span>
                </a>

                <a href="{{ route('search.hotels') }}" class="bg-white rounded-2xl p-5 border border-slate-200 text-center card-hover group">
                    <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ __('messages.nav.hotels') }}</span>
                </a>

                <a href="{{ route('search.tickets') }}" class="bg-white rounded-2xl p-5 border border-slate-200 text-center card-hover group">
                    <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ __('messages.nav.tickets') }}</span>
                </a>

                <a href="{{ route('search.excursions') }}" class="bg-white rounded-2xl p-5 border border-slate-200 text-center card-hover group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ __('messages.nav.excursions') }}</span>
                </a>

                <a href="{{ route('claims.index') }}" class="bg-white rounded-2xl p-5 border border-slate-200 text-center card-hover group">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ __('messages.nav.claims') }}</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">{{ __('messages.dashboard.recent_activity') }}</h3>
                    <a href="{{ route('claims.index') }}" class="text-sm text-green-700 hover:text-green-800 font-medium">{{ __('messages.dashboard.view_all') }}</a>
                </div>
                <div class="p-6">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <h4 class="text-slate-700 font-medium mb-1">{{ __('messages.dashboard.no_activity') }}</h4>
                        <p class="text-sm text-slate-500 mb-4">{{ __('messages.dashboard.start_searching') }}</p>
                        <a href="{{ route('search.tours') }}" class="text-sm text-green-700 hover:text-green-800 font-medium flex items-center">
                            {{ __('messages.dashboard.search_tours') }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Popular Destinations -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">{{ __('messages.dashboard.popular_destinations') }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <a href="{{ route('search.tours') }}" class="flex items-center p-3 rounded-xl hover:bg-slate-50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-white font-bold text-lg mr-4">
                            TR
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-slate-800 group-hover:text-green-700 transition">Turkey</h4>
                            <p class="text-xs text-slate-500">10 tours available</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-green-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('search.tours') }}" class="flex items-center p-3 rounded-xl hover:bg-slate-50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold text-lg mr-4">
                            AE
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-slate-800 group-hover:text-green-700 transition">UAE</h4>
                            <p class="text-xs text-slate-500">Luxury experiences</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-green-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('search.tours') }}" class="flex items-center p-3 rounded-xl hover:bg-slate-50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold text-lg mr-4">
                            EG
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-slate-800 group-hover:text-green-700 transition">Egypt</h4>
                            <p class="text-xs text-slate-500">Ancient wonders</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-green-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('search.tours') }}" class="flex items-center p-3 rounded-xl hover:bg-slate-50 transition group">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center text-white font-bold text-lg mr-4">
                            TH
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-slate-800 group-hover:text-green-700 transition">Thailand</h4>
                            <p class="text-xs text-slate-500">Tropical paradise</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-green-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl p-8 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-2xl font-bold mb-2">{{ __('messages.dashboard.need_help') }}</h3>
                    <p class="text-white/80">{{ __('messages.dashboard.support_available') }}</p>
                </div>
                <div class="flex space-x-4">
                    <a href="tel:+998712334455" class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-xl font-semibold transition flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ __('messages.dashboard.call_us') }}
                    </a>
                    <a href="mailto:info@levora.uz" class="px-6 py-3 bg-white text-green-700 hover:bg-white/90 rounded-xl font-semibold transition flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ __('messages.dashboard.email_us') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
