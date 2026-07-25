<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tanzania Specialist')</title>
    <script>if (localStorage.getItem('theme') === 'green') document.documentElement.classList.add('safari-green-theme');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/auth-reference.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="auth-body @yield('bodyClass', 'auth-page')">
    @include('auth.partials.brandbar')

    <main class="auth-container">
        <section class="auth-image-side" aria-label="Tanzania Specialist safari imagery">
            @include('auth.partials.visual')
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrapper">
                <div class="auth-form-topline">
                    <button class="auth-language" type="button" aria-label="Language: English">
                        <span aria-hidden="true">🇬🇧</span>
                        <span>EN</span>
                        <span class="auth-language-chevron" aria-hidden="true">⌄</span>
                    </button>
                </div>
                @yield('content')
            </div>
        </section>
    </main>

    @include('auth.partials.footer')
    @stack('scripts')
</body>
</html>
