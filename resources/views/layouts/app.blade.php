<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Safari Planner | Tanzania Specialist Ltd')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="TS Safari" />

    <script>
        if (localStorage.getItem('theme') === 'green') document.documentElement.classList.add('safari-green-theme');
        if (localStorage.getItem('dark-mode') === 'true' || localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark-theme');
    </script>

    <!-- Fonts -->
    <link rel="manifest" href="/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="@yield('body_class')">
    <div id="app">
        <div class="app-shell">
            @include('partials.sidebar')
            
            <main class="main-content">
                @include('partials.topbar')
                
                <div class="content-wrap">
                    @if(session('success') && !session('import_report'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-error">{{ $errors->first() }}</div>
                    @endif
                    @include('partials.import-report')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    @stack('scripts')
</body>
</html>
