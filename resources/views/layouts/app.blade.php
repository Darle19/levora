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
    @stack('styles')
</head>
<body class="antialiased min-h-screen flex flex-col" style="font-family: Tahoma, Arial, sans-serif; font-size: 13px; background: #f5f5f5;">
    <style>
        /* Header */
        #header { background: #EBF6FC; width: 100%; }
        #header-inner { max-width: 1200px; margin: 0 auto; }
        .high-menu { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; }
        .head-logo img { width: 80px; height: 80px; }
        .head-block { display: flex; align-items: center; border-radius: 8px; padding: 8px 12px; gap: 8px; }
        .head-block-green { background: rgba(25, 135, 84, 0.1); }
        .head-block-blue { background: rgba(13, 110, 253, 0.1); }
        .head-block-red { background: rgba(220, 53, 69, 0.1); }
        .head-block-title { font-size: 12px; font-weight: 700; }
        .head-block-green .head-block-title { color: #198754; }
        .head-block-blue .head-block-title { color: #dc3545; font-size: 14px; font-weight: 400; }
        .head-block-value { font-size: 13px; font-weight: 550; color: #212529; }
        .head-block-blue .head-block-value { font-size: 18px; font-weight: 700; color: #198754; }
        .head-currency { text-align: center; font-size: 12px; }
        .head-currency .cur-title { color: #0d6efd; font-weight: 500; }
        .head-currency table { margin: 2px auto; }
        .head-currency th, .head-currency td { padding: 1px 6px; font-size: 12px; }
        .head-currency th { font-weight: 700; }

        /* Navigation */
        .bottom-menu { background: #f5f5f5; border-top: 1px solid #dee2e6; }
        .bottom-menu-inner { max-width: 1200px; margin: 0 auto; }
        .bottom-menu ul { display: flex; list-style: none; margin: 0; padding: 0; }
        .bottom-menu li { position: relative; }
        .bottom-menu li > a { display: block; padding: 14px 15px; color: #007355; text-decoration: none; font-size: 13px; white-space: nowrap; }
        .bottom-menu li > a:hover { color: #f36f21; }
        .bottom-menu li.active > a { color: #000; font-weight: 700; }
        .bottom-menu li > a .caret { font-size: 10px; margin-left: 2px; }
        /* Dropdown */
        .bottom-menu li ul { display: none; position: absolute; top: 100%; left: 0; background: #eee; padding: 6px 0; min-width: 180px; z-index: 100; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .bottom-menu li:hover > ul { display: block; }
        .bottom-menu li ul li a { padding: 6px 20px; font-size: 12px; color: #007355; }
        .bottom-menu li ul li a:hover { background: #ddd; color: #f36f21; }
        .bottom-menu .nav-right { margin-left: auto; display: flex; align-items: center; }
        .lang-switch { display: flex; gap: 2px; padding: 10px 8px; }
        .lang-switch a { padding: 4px 8px; font-size: 11px; text-decoration: none; color: #007355; border-radius: 3px; }
        .lang-switch a.active { background: #007355; color: #fff; }
    </style>

    <div id="header">
        <div id="header-inner">
            {{-- Top Bar --}}
            <div class="high-menu">
                {{-- Logo --}}
                <div class="head-logo">
                    <a href="{{ route('home') }}"><img src="{{ asset('Levora_logo.svg') }}" alt="Levora"></a>
                </div>

                {{-- Contacts --}}
                <div class="head-block head-block-green">
                    <div>
                        <div class="head-block-title">По вопросам сотрудничества</div>
                        <div class="head-block-value">+998 71 233 44 55</div>
                        <div class="head-block-value" style="font-size:12px;">info@levora.uz</div>
                    </div>
                </div>

                {{-- Hotline --}}
                <div class="head-block head-block-blue">
                    <div>
                        <div class="head-block-title">Горячая линия</div>
                        <div class="head-block-value">+998 71 233 44 55</div>
                    </div>
                </div>

                {{-- Currency --}}
                <div style="display:flex; gap:8px;">
                    <div class="head-block head-block-red head-currency">
                        <div>
                            <div class="cur-title">Узбекский сум</div>
                            <table><tr><th>Date</th><th>$</th></tr><tr><td>{{ date('d.m.Y') }}</td><td>12 850</td></tr></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Bar --}}
    <div class="bottom-menu">
        <div class="bottom-menu-inner">
            <ul>
                <li class="active"><a href="{{ route('search.tours') }}">Search <span class="caret">▾</span></a>
                    <ul>
                        <li><a href="{{ route('search.tours') }}">Tour search</a></li>
                        <li><a href="{{ route('search.hotels') }}">Hotels</a></li>
                        <li><a href="{{ route('search.tickets') }}">Tickets</a></li>
                    </ul>
                </li>
                @auth
                <li><a href="{{ route('claims.index') }}">Claims <span class="caret">▾</span></a>
                    <ul>
                        <li><a href="{{ route('claims.index') }}">View claims</a></li>
                    </ul>
                </li>
                <li><a href="#">Agency <span class="caret">▾</span></a>
                    <ul>
                        <li><a href="{{ route('agency.profile') }}">Agency details</a></li>
                        <li><a href="{{ route('agency.employees') }}">Employees</a></li>
                    </ul>
                </li>
                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                @endauth
                <li><a href="#">Info <span class="caret">▾</span></a>
                    <ul>
                        <li><a href="/admin/hotels">Hotel catalog</a></li>
                        <li><a href="/admin/flights">Flight schedule</a></li>
                    </ul>
                </li>

                {{-- Right side: language + user --}}
                <div class="nav-right">
                    <div class="lang-switch">
                        <a href="{{ route('language.switch', 'ru') }}" class="{{ app()->getLocale() == 'ru' ? 'active' : '' }}">RU</a>
                        <a href="{{ route('language.switch', 'en') }}" class="{{ app()->getLocale() == 'en' ? 'active' : '' }}">EN</a>
                        <a href="{{ route('language.switch', 'uz') }}" class="{{ app()->getLocale() == 'uz' ? 'active' : '' }}">UZ</a>
                    </div>
                    @auth
                    <li><a href="#">{{ Auth::user()->agency->name ?? Auth::user()->name }} <span class="caret">▾</span></a>
                        <ul>
                            <li><a href="{{ route('dashboard') }}">{{ Auth::user()->name }}</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                                    @csrf
                                    <a href="#" onclick="this.closest('form').submit(); return false;" style="display:block;">Sign out</a>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li><a href="{{ route('login') }}">Sign In</a></li>
                    <li><a href="{{ route('register') }}" style="font-weight:700;">Register</a></li>
                    @endauth
                </div>
            </ul>
        </div>
    </div>

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
                        <img src="{{ asset('Levora_logo.svg') }}" alt="Levora" class="h-10 w-auto brightness-0 invert">
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
