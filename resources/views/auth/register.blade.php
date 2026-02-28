@extends('layouts.app')

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="w-16 h-16 rounded-2xl gradient-primary flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ __('messages.auth.register_title') }}</h1>
            <p class="text-slate-500">{{ __('messages.auth.register_description') }}</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
        <div class="mb-8 p-4 rounded-xl bg-red-50 border border-red-100">
            <div class="flex items-start">
                <div class="shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Step Indicator -->
            <div class="flex items-center justify-center space-x-4 mb-8">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full gradient-primary flex items-center justify-center text-white font-semibold">1</div>
                    <span class="ml-3 text-sm font-medium text-slate-700">{{ __('messages.auth.personal_info') }}</span>
                </div>
                <div class="w-16 h-0.5 bg-slate-200"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-semibold">2</div>
                    <span class="ml-3 text-sm font-medium text-slate-500">{{ __('messages.auth.agency_info') }}</span>
                </div>
            </div>

            <!-- User Information Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-slate-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">{{ __('messages.auth.personal_info') }}</h3>
                            <p class="text-sm text-slate-500">{{ __('messages.auth.sign_in_description') }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.full_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('name') border-red-300 @enderror"
                                placeholder="John Doe">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.email') }} <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('email') border-red-300 @enderror"
                                placeholder="you@example.com">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.phone') }}</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('phone') border-red-300 @enderror"
                                placeholder="+998 90 123 45 67">
                        </div>

                        <div></div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.password') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="password" required
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('password') border-red-300 @enderror"
                                placeholder="Min. 8 characters">
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.confirm_password') }} <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none"
                                placeholder="Repeat password">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agency Information Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-amber-50 border-b border-slate-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">{{ __('messages.auth.agency_info') }}</h3>
                            <p class="text-sm text-slate-500">{{ __('messages.auth.agency_info') }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="agency_name" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.agency_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="agency_name" id="agency_name" required value="{{ old('agency_name') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('agency_name') border-red-300 @enderror"
                                placeholder="Your Agency">
                        </div>

                        <div>
                            <label for="legal_name" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.legal_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="legal_name" id="legal_name" required value="{{ old('legal_name') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('legal_name') border-red-300 @enderror"
                                placeholder="Your Agency LLC">
                        </div>

                        <div class="md:col-span-2">
                            <label for="legal_address" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.legal_address') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="legal_address" id="legal_address" required value="{{ old('legal_address') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('legal_address') border-red-300 @enderror"
                                placeholder="123 Business Street, Tashkent">
                        </div>

                        <div>
                            <label for="agency_phone" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.agency_phone') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="agency_phone" id="agency_phone" required value="{{ old('agency_phone') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('agency_phone') border-red-300 @enderror"
                                placeholder="+998 71 234 56 78">
                        </div>

                        <div>
                            <label for="agency_mobile" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.agency_mobile') }}</label>
                            <input type="text" name="agency_mobile" id="agency_mobile" value="{{ old('agency_mobile') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('agency_mobile') border-red-300 @enderror"
                                placeholder="+998 90 123 45 67">
                        </div>

                        <div>
                            <label for="agency_email" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.agency_email') }} <span class="text-red-500">*</span></label>
                            <input type="email" name="agency_email" id="agency_email" required value="{{ old('agency_email') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('agency_email') border-red-300 @enderror"
                                placeholder="agency@example.com">
                        </div>

                        <div>
                            <label for="director" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.director') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="director" id="director" required value="{{ old('director') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('director') border-red-300 @enderror"
                                placeholder="Director's full name">
                        </div>

                        <div>
                            <label for="inn" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.inn') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="inn" id="inn" required value="{{ old('inn') }}"
                                class="input-modern w-full px-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('inn') border-red-300 @enderror"
                                placeholder="123456789">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms and Submit -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-start mb-6">
                    <input type="checkbox" id="terms" name="terms" required class="mt-1 w-5 h-5 rounded border-slate-300 text-green-700 focus:ring-green-500">
                    <label for="terms" class="ml-3 text-sm text-slate-600">
                        {{ __('messages.auth.terms_agree') }} <a href="#" class="text-green-700 hover:underline">{{ __('messages.auth.terms') }}</a> & <a href="#" class="text-green-700 hover:underline">{{ __('messages.auth.privacy') }}</a>.
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800 transition">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            {{ __('messages.auth.back_to_sign_in') }}
                        </span>
                    </a>
                    <button type="submit" class="btn-primary px-8 py-3.5 rounded-xl text-white font-semibold text-sm w-full sm:w-auto">
                        {{ __('messages.auth.create_account_button') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Features -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-800 mb-2">Secure & Verified</h4>
                <p class="text-sm text-slate-500">All agencies are verified for your safety</p>
            </div>
            <div class="text-center p-6">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-800 mb-2">Instant Access</h4>
                <p class="text-sm text-slate-500">Start booking tours immediately</p>
            </div>
            <div class="text-center p-6">
                <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h4 class="font-semibold text-slate-800 mb-2">24/7 Support</h4>
                <p class="text-sm text-slate-500">We're here to help anytime</p>
            </div>
        </div>
    </div>
</div>
@endsection
