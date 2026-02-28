<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Levora') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #1B6B2E;
            --primary-dark: #145222;
            --secondary: #f97316;
            --accent: #238636;
        }
        body { font-family: 'Inter', sans-serif; }
        .gradient-primary { background: linear-gradient(135deg, #1B6B2E 0%, #238636 100%); }
        .gradient-dark { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: linear-gradient(90deg, #1B6B2E, #238636); transition: width 0.3s; }
        .nav-link:hover::after { width: 100%; }
        .dropdown-menu { opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s ease; }
        .dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .btn-primary { background: linear-gradient(135deg, #1B6B2E 0%, #145222 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(27, 107, 46, 0.3); }
        .input-modern { transition: all 0.2s ease; border: 2px solid #e2e8f0; }
        .input-modern:focus { border-color: #1B6B2E; box-shadow: 0 0 0 3px rgba(27, 107, 46, 0.1); }
    </style>
</head>
<body class="antialiased bg-slate-50 min-h-screen flex flex-col">
    <!-- Top Bar -->
    <div class="gradient-dark text-white py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm">
            <div class="flex items-center space-x-6">
                <a href="tel:+998712334455" class="flex items-center hover:text-green-400 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    +998 71 233 44 55
                </a>
                <a href="mailto:info@levora.uz" class="flex items-center hover:text-green-400 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    info@levora.uz
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Language Selector -->
                <div class="flex items-center space-x-1 bg-slate-800 rounded-full px-1 py-0.5">
                    <a href="{{ route('language.switch', 'ru') }}" class="px-3 py-1 rounded-full text-xs font-medium {{ app()->getLocale() == 'ru' ? 'bg-green-600 text-white' : 'text-slate-300 hover:text-white' }} transition">RU</a>
                    <a href="{{ route('language.switch', 'en') }}" class="px-3 py-1 rounded-full text-xs font-medium {{ app()->getLocale() == 'en' ? 'bg-green-600 text-white' : 'text-slate-300 hover:text-white' }} transition">EN</a>
                    <a href="{{ route('language.switch', 'uz') }}" class="px-3 py-1 rounded-full text-xs font-medium {{ app()->getLocale() == 'uz' ? 'bg-green-600 text-white' : 'text-slate-300 hover:text-white' }} transition">UZ</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="glass sticky top-0 z-50 shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <img src="{{ asset('logo-sm.png') }}" alt="Levora" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-1">
                    @auth
                    <!-- Claims -->
                    <a href="{{ route('claims.index') }}" class="nav-link flex items-center px-4 py-2 text-sm font-medium text-slate-700 hover:text-green-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('messages.nav.claims') }}
                    </a>

                    <!-- Agency Dropdown -->
                    <div class="dropdown relative">
                        <button class="nav-link flex items-center px-4 py-2 text-sm font-medium text-slate-700 hover:text-green-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ __('messages.nav.agency') }}
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute left-0 mt-2 w-56 rounded-2xl shadow-xl bg-white border border-slate-100 py-2 z-50">
                            <a href="{{ route('agency.profile') }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-green-50 hover:text-green-700 transition">
                                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ __('messages.nav.profile') }}
                            </a>
                            <a href="{{ route('agency.employees') }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-green-50 hover:text-green-700 transition">
                                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                {{ __('messages.nav.employees') }}
                            </a>
                        </div>
                    </div>

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" class="nav-link flex items-center px-4 py-2 text-sm font-medium text-slate-700 hover:text-green-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        {{ __('messages.nav.dashboard') }}
                    </a>
                    @endauth
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-4">
                    @auth
                    <!-- User Menu -->
                    <div class="dropdown relative">
                        <button class="flex items-center space-x-3 px-3 py-2 rounded-xl hover:bg-slate-100 transition">
                            <div class="w-9 h-9 rounded-full gradient-primary flex items-center justify-center text-white font-semibold text-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden lg:block text-left">
                                <div class="text-sm font-medium text-slate-700">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-slate-500">{{ Auth::user()->agency->name ?? 'N/A' }}</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute right-0 mt-2 w-56 rounded-2xl shadow-xl bg-white border border-slate-100 py-2 z-50">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="text-sm font-medium text-slate-900">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-slate-500">{{ Auth::user()->email }}</div>
                            </div>
                            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-green-50 hover:text-green-700 transition">
                                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                {{ __('messages.nav.dashboard') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    {{ __('messages.nav.sign_out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 hover:text-green-700 transition">{{ __('messages.nav.sign_in') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                        {{ __('messages.nav.get_started') }}
                    </a>
                    @endauth

                    <!-- Mobile Menu Button -->
                    <button type="button" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 bg-white">
            <div class="px-4 py-4 space-y-2">
                @auth
                <a href="{{ route('claims.index') }}" class="block px-4 py-2 rounded-lg text-slate-700 hover:bg-green-50 hover:text-green-700">{{ __('messages.nav.claims') }}</a>
                <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded-lg text-slate-700 hover:bg-green-50 hover:text-green-700">{{ __('messages.nav.dashboard') }}</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="gradient-dark text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="md:col-span-1">
                    <div class="flex items-center mb-4">
                        <img src="{{ asset('logo-sm.png') }}" alt="Levora" class="h-10 w-auto brightness-0 invert">
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">{{ __('messages.footer.description') }}</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-300 mb-4">{{ __('messages.footer.services') }}</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="text-slate-400 hover:text-white transition">{{ __('messages.footer.package_tours') }}</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition">{{ __('messages.footer.hotel_booking') }}</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition">{{ __('messages.footer.flight_tickets') }}</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition">{{ __('messages.footer.visa_services') }}</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-300 mb-4">{{ __('messages.footer.contact') }}</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            Tashkent, Uzbekistan
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            +998 71 233 44 55
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            info@levora.uz
                        </li>
                    </ul>
                </div>

                <!-- Social -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-300 mb-4">{{ __('messages.footer.follow_us') }}</h4>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center hover:bg-green-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center hover:bg-pink-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center hover:bg-green-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-700 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-slate-400">&copy; {{ date('Y') }} Levora. {{ __('messages.footer.all_rights') }}</p>
                <div class="flex space-x-6 mt-4 md:mt-0 text-sm text-slate-400">
                    <a href="#" class="hover:text-white transition">{{ __('messages.footer.privacy_policy') }}</a>
                    <a href="#" class="hover:text-white transition">{{ __('messages.footer.terms_of_service') }}</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
