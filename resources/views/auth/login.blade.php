@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-200px)] flex">
    <!-- Left Side - Decorative -->
    <div class="hidden lg:flex lg:w-1/2 gradient-primary relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-white/10 rounded-full"></div>
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-white/10 rounded-full"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center text-white z-10 px-8">
            <div class="mx-auto mb-8">
                <img src="{{ asset('logo-md.png') }}" alt="Levora" class="h-20 w-auto mx-auto brightness-0 invert">
            </div>
            <h1 class="text-4xl font-bold mb-4">Welcome to Levora</h1>
            <p class="text-xl text-white/80 max-w-md">Your gateway to unforgettable travel experiences. Discover the world with us.</p>
            <div class="flex justify-center space-x-8 mt-12">
                <div class="text-center">
                    <div class="text-3xl font-bold">500+</div>
                    <div class="text-sm text-white/70">Destinations</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold">10K+</div>
                    <div class="text-sm text-white/70">Happy Clients</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold">24/7</div>
                    <div class="text-sm text-white/70">Support</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-slate-50">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden text-center mb-8">
                <img src="{{ asset('logo-md.png') }}" alt="Levora" class="h-16 w-auto mx-auto mb-4">
            </div>

            <div class="text-center lg:text-left mb-8">
                <h2 class="text-3xl font-bold text-slate-800 mb-2">{{ __('messages.auth.welcome_back') }}</h2>
                <p class="text-slate-500">{{ __('messages.auth.sign_in_description') }}</p>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Authentication failed</h3>
                        <ul class="mt-1 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.email') }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="input-modern w-full pl-12 pr-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('email') border-red-300 @enderror"
                            placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">{{ __('messages.auth.password') }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="input-modern w-full pl-12 pr-4 py-3 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none @error('password') border-red-300 @enderror"
                            placeholder="Enter your password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-green-700 focus:ring-green-500">
                        <span class="ml-2 text-sm text-slate-600">{{ __('messages.auth.remember_me') }}</span>
                    </label>
                    <a href="#" class="text-sm font-medium text-green-700 hover:text-green-600 transition">{{ __('messages.auth.forgot_password') }}</a>
                </div>

                <button type="submit" class="btn-primary w-full py-3.5 rounded-xl text-white font-semibold text-sm">
                    {{ __('messages.auth.sign_in_button') }}
                </button>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-slate-50 text-slate-500">{{ __('messages.auth.new_to') }}</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('register') }}" class="w-full flex justify-center py-3 px-4 border-2 border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 hover:border-slate-300 transition">
                        {{ __('messages.auth.create_account') }}
                    </a>
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-slate-500">
                {{ __('messages.auth.terms_agree') }}
                <a href="#" class="text-green-700 hover:underline">{{ __('messages.auth.terms') }}</a> &
                <a href="#" class="text-green-700 hover:underline">{{ __('messages.auth.privacy') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
