<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AI Smart Upsell Pro – NituLabs')</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"/>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

    <script src="https://unpkg.com/@shopify/app-bridge"></script>
    <script src="https://unpkg.com/@shopify/app-bridge/actions"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    <style>
        :root {
            --primary:       #10b981;
            --primary-light: #0dc262;
            --bg:            #0f172a;
            --text:          #f1f5f9;
            --glass:         rgba(255, 255, 255, 0.06);
        }

        * {
            margin:     0;
            padding:    0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background:  var(--bg);
            color:       var(--text);
            line-height: 1.6;
        }

        a, nav a, footer a, .glass-card a, .mobile-menu a {
            color:           var(--primary);
            text-decoration: none !important;
            transition:      color 0.3s ease, text-decoration 0.3s ease;
        }

        a:hover,
        a:focus,
        nav a:hover,
        footer a:hover,
        .glass-card a:hover,
        .mobile-menu a:hover {
            color:           var(--primary-light);
            text-decoration: underline;
        }

        main, .hero {
            margin-top: 88px; /* Or your header height */
        }

        .hero {
            display:         flex;
            align-items:     center;
            justify-content: center;
            text-align:      center;
            padding:         6rem 2rem 2rem;
            background:      linear-gradient(110deg, #0f172a 60%, #176972 100%);
        }

        .hero-content {
            animation: fadeInUp 1.2s both;
            max-width: 900px;
        }

        a {
            color:           var(--primary);
            text-decoration: none;
            transition:      color 0.3s ease, text-decoration 0.3s ease;
        }

        a:hover,
        a:focus {
            color:           var(--primary-light);
            text-decoration: underline;
            outline:         none;
        }

        .glass-card a {
            color: #a0f2c8;
        }

        .glass-card a:hover {
            color: #7de5a4;
        }

        header {
            position:         fixed;
            top:              0;
            width:            100%;
            background-color: rgba(15, 23, 42, 0.9);
            backdrop-filter:  blur(12px);
            display:          flex;
            justify-content:  space-between;
            align-items:      center;
            padding:          1rem 2rem;
            z-index:          1000;
        }

        .logo img {
            height: 42px;
        }

        nav {
            display:     flex;
            align-items: center;
            gap:         20px;
        }

        nav a {
            font-weight: 500;
        }

        nav a:not(:last-child) {
            margin-right: 0.8rem;
        }

        /* Auth styles */
        nav form button {
            background:  transparent;
            border:      none;
            padding:     0;
            font-weight: 500;
            cursor:      pointer;
            color:       var(--primary);
            transition:  color 0.3s ease;
        }

        nav form button:hover,
        nav form button:focus {
            color:   var(--primary-light);
            outline: none;
        }

        .hamburger {
            display:        none;
            flex-direction: column;
            gap:            5px;
            cursor:         pointer;
        }

        .hamburger div {
            width:      25px;
            height:     3px;
            background: var(--text);
        }

        .mobile-menu {
            display:        none;
            flex-direction: column;
            background:     var(--bg);
            padding:        1rem 2rem;
        }

        .mobile-menu a {
            margin-bottom: 12px;
            font-weight:   500;
        }

        section {
            max-width: 1000px;
            margin:    4rem auto;
            padding:   0 1.5rem;
        }

        .glass-card {
            background:      var(--glass);
            border-radius:   16px;
            padding:         2rem;
            margin-bottom:   3rem;
            backdrop-filter: blur(12px);
            box-shadow:      0 10px 35px rgba(0, 0, 0, 0.2);
            animation:       fadeInUp 0.9s both;
            color:           var(--text);
        }

        .glass-card h2 {
            color:          var(--primary);
            margin-bottom:  1rem;
            font-size:      1.8rem;
            letter-spacing: 0.5px;
        }

        .glass-card h3 {
            color:         var(--primary);
            margin-bottom: 0.5rem;
            font-size:     1.3rem;
        }

        .glass-card ul {
            list-style:   none;
            padding-left: 0;
        }

        .glass-card ul li {
            position:      relative;
            padding-left:  1.5rem;
            margin-bottom: 0.8rem;
            color:         #d1d5db;
        }

        .glass-card ul li::before {
            content:     "✓";
            position:    absolute;
            left:        0;
            color:       var(--primary);
            font-weight: bold;
        }

        .cta a {
            display:         inline-block;
            margin-top:      1.4rem;
            background:      var(--primary);
            color:           #fff;
            padding:         0.8rem 1.8rem;
            border-radius:   8px;
            text-decoration: none;
            font-weight:     600;
            transition:      background 0.3s;
        }

        .cta a:hover { background: #059669; }

        .cta-hero a {
            display:        inline-block;
            margin:         2rem 0 0;
            background:     var(--primary);
            color:          #fff;
            padding:        1rem 2.2rem;
            border-radius:  10px;
            font-weight:    700;
            letter-spacing: 1px;
            font-size:      1.12rem;
            box-shadow:     0 4px 18px rgba(16, 185, 129, 0.2);
            transition:     background 0.3s, box-shadow 0.3s;
        }

        .cta-hero a:hover { background: #059669; box-shadow: 0 6px 22px rgba(15, 185, 129, 0.33); }

        .hero p {
            font-size:   1.25rem;
            color:       #e3fcec;
            max-width:   600px;
            margin:      1rem auto;
            opacity:     0.9;
            line-height: 1.5;
        }

        .animated-gradient {
            background:              linear-gradient(270deg, #10b981, #0891b2, #4F46E5, #10b981);
            background-size:         800% 800%;
            animation:               gradientBG 12s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter:                  drop-shadow(0 0 8px #12946955);
            font-size:               3rem;
            font-weight:             700;
            margin-bottom:           1rem;
            letter-spacing:          1px;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(36px);}
            to { opacity: 1; transform: translateY(0);}
        }

        footer {
            background: #0c1a2f;
            text-align: center;
            padding:    2rem 1rem;
            font-size:  0.9rem;
            color:      #94a3b8;
            margin:     0;
        }

        footer a {
            color:           var(--primary);
            text-decoration: none;
        }

        .socials a { margin: 0 0.6rem; font-size: 1.2rem; }

        @media (max-width: 768px) {
            nav { display: none; }

            .hamburger { display: flex; }

            .mobile-menu { display: none; }

            .mobile-menu.show { display: flex; }

            section { padding: 0 1rem; }

            .cta-hero a { padding: 0.8rem 1.2rem; font-size: 1rem;}
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-[var(--bg)] text-[var(--text)]">

<!-- Header -->
<header class="fixed top-0 w-full bg-[rgba(15,23,42,0.92)] backdrop-blur-md flex justify-between items-center px-6 py-4 z-50 shadow-md">
    <div class="logo">
        <a href="{{ url('/') }}" class="flex flex-col leading-tight align-middle">
            <span class="text-2xl font-extrabold tracking-tight text-[var(--primary)]">AI Smart Upsell Pro</span>
            <div class="flex items-center gap-1 mt-[-8px] text-sm font-medium text-white">
                <span class="leading-tight tracking-wide">powered by</span>
                <a href="https://nitulabs.com" class="inline-flex items-center">
                    <img src="https://nitulabs.com/images/logo.png" alt="NituLabs Logo" style="height: 22px">
                </a>
            </div>
        </a>
    </div>

    <nav class="hidden md:flex items-center gap-7 font-medium text-[var(--primary)]">
        <a href="{{ secure_url('dashboard') . '?shop=' . $shop->shopify_domain }}">Dashboard</a>
        <a href="{{ secure_url('recommendations') . '?shop=' . $shop->shopify_domain }}">Recommendations</a>
    </nav>

    <div class="hamburger md:hidden flex flex-col gap-1 cursor-pointer" onclick="toggleMenu()">
        <span class="block w-6 h-0.5 bg-white"></span>
        <span class="block w-6 h-0.5 bg-white"></span>
        <span class="block w-6 h-0.5 bg-white"></span>
    </div>
</header>

<!-- Mobile Menu -->
<div id="mobileMenu"
     class="mobile-menu hidden md:hidden flex-col bg-[var(--bg)] p-6 space-y-3 fixed top-20 left-0 w-full h-[calc(100vh-4rem)] z-50 overflow-y-auto shadow-lg">
    <a href="{{ route('dashboard') }}" class="text-[var(--primary)] font-medium">Dashboard</a>
    <a href="{{ route('recommendations.index') }}" class="text-[var(--primary)] font-medium">Recommendations</a>
</div>

<main class="flex-1 mt-20 p-6 md:p-0">
    @yield('content')
</main>

<!-- Footer -->
<footer data-aos="fade-up">
    &copy; 2025 NituLabs. All rights reserved. <br/>
    <a href="/terms.html">Terms</a> | <a href="/privacy.html">Privacy</a>
</footer>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="//unpkg.com/alpinejs" defer></script>
<script>
    AOS.init({ duration: 1000, once: true });

    function toggleMenu() {
        document.getElementById('mobileMenu').classList.toggle('show');
    }
</script>

@stack('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Replace with your actual values, e.g., from config or controller
        const apiKey = "{{ config('shopify.api_key') }}";
        const shopOrigin = "{{ $shop->shopify_domain ?? '' }}";
        if (!apiKey || !shopOrigin) return;

        const AppBridge = window["app-bridge"];
        const createApp = AppBridge.default;
        const { Toast } = AppBridge.actions;

        const app = createApp({
            apiKey: apiKey,
            shopOrigin: shopOrigin,
            forceRedirect: true,
        });

        // Show toast if ?success exists
        const urlParams = new URLSearchParams(window.location.search);
        const successMessage = urlParams.get('success');
        if(successMessage) {
            const toast = Toast.create(app, { message: successMessage, duration: 3500 });
            toast.dispatch(Toast.Action.SHOW);
        }

        // Make toasts callable from anywhere
        window.showToast = function(message) {
            const toast = Toast.create(app, { message: message, duration: 3500 });
            toast.dispatch(Toast.Action.SHOW);
        };
    });
</script>
</body>
</html>
