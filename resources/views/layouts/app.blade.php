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
<body class="antialiased min-h-screen flex flex-col" style="font-family: Tahoma, Arial, sans-serif; font-size: 13px; background: #f5f5f5; zoom: 1.2;">
    <style>
        /* Header */
        #header { background: #EBF6FC; width: 100%; }
        #header-inner { max-width: 1200px; margin: 0 auto; }
        .high-menu { display: flex; align-items: center; justify-content: center; gap: 20px; padding: 8px 10px; }
        .head-logo img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
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
                        <div class="head-block-value">+998 91 977 77 35</div>
                        <div class="head-block-value" style="font-size:12px;">info@levora.uz</div>
                    </div>
                </div>

                {{-- Hotline --}}
                <div class="head-block head-block-blue">
                    <div>
                        <div class="head-block-title">Горячая линия</div>
                        <div class="head-block-value">+998 91 977 77 35</div>
                    </div>
                </div>

                {{-- Currency --}}
                @php
                    $usdId = \App\Models\Currency::where('code', 'USD')->value('id');
                    $uzsId = \App\Models\Currency::where('code', 'UZS')->value('id');
                    $usdUzsRate = null;
                    if ($usdId && $uzsId) {
                        $usdUzsRate = \App\Models\CurrencyRate::where(function($q) use ($usdId, $uzsId) {
                                $q->where(fn($q2) => $q2->where('from_currency_id', $usdId)->where('to_currency_id', $uzsId))
                                  ->orWhere(fn($q2) => $q2->where('from_currency_id', $uzsId)->where('to_currency_id', $usdId));
                            })
                            ->orderByDesc('date')
                            ->first();
                    }
                @endphp
                <div style="display:flex; gap:8px;">
                    <div class="head-block head-block-red head-currency">
                        <div>
                            <div class="cur-title">Узбекский сум</div>
                            <table><tr><th>Date</th><th>$</th></tr><tr><td>{{ $usdUzsRate ? $usdUzsRate->date->format('d.m.Y') : date('d.m.Y') }}</td><td>{{ $usdUzsRate ? number_format($usdUzsRate->rate, 0, '.', ' ') : '—' }}</td></tr></table>
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

    {{-- No footer --}}

    @stack('scripts')
</body>
</html>
