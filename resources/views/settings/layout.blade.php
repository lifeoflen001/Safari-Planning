<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Settings | Safari Planner')</title>
    
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TS Safari" />

    <script>
        if (localStorage.getItem('theme') === 'green') document.documentElement.classList.add('safari-green-theme');
        if (localStorage.getItem('dark-mode') === 'true' || localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark-theme');
    </script>
    
    <link rel="manifest" href="/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="settings-page">
    <div id="app">
        <div class="app-shell">
            @include('partials.sidebar')
            
            <main class="main-content">
                @include('partials.topbar')
                
                <div class="content-wrap">
                    <div class="settings-layout">
                        <nav class="settings-nav">
                            <div class="settings-nav-header">
                                <span>Settings</span>
                                <button class="settings-nav-toggle" id="settingsNavToggle" type="button" title="Collapse settings navigation" aria-label="Collapse settings navigation" aria-expanded="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="settings-nav-section">
                                <div class="settings-nav-label">Personal Settings</div>
                                <a href="{{ route('settings.profile') }}" class="settings-nav-item {{ request()->routeIs('settings.profile') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20v-6m0 0-3 3m3-3 3 3M5 10a7 7 0 0 1 14 0c0 7-3 9-3 9h9s1-2 1-9a5 5 0 0 0-10 0c0 2.5 1 5 1 5H5z"/></svg>
                                    <span>My Profile</span>
                                </a>
                                <a href="{{ route('settings.personal') }}" class="settings-nav-item {{ request()->routeIs('settings.personal') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7a3 3 0 0 1 0 6m4 10v-1a4 4 0 0 0-3-3.87"/></svg>
                                    <span>Personal Information</span>
                                </a>
                                <a href="{{ route('settings.security') }}" class="settings-nav-item {{ request()->routeIs('settings.security') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.4-3.5a7.7 7.7 0 0 0-.1-1l2-1.5-2-3.4-2.3.9a8 8 0 0 0-1.7-1l-.3-2.4h-4l-.3 2.4a8 8 0 0 0-1.7 1l-2.3-.9-2 3.4L4.7 11a7.7 7.7 0 0 0 0 2l-2 1.5 2 3.4 2.3-.9a8 8 0 0 0 1.7 1l.3 2.4h4l.3-2.4a8 8 0 0 0-1.7 1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1Z"/></svg>
                                    <span>Account & Security</span>
                                </a>
                                <a href="{{ route('settings.notifications') }}" class="settings-nav-item {{ request()->routeIs('settings.notifications') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                                    <span>Notifications</span>
                                </a>
                                <a href="{{ route('settings.preferences') }}" class="settings-nav-item {{ request()->routeIs('settings.preferences') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.4-3.5a7.7 7.7 0 0 0-.1-1l2-1.5-2-3.4-2.3.9a8 8 0 0 0-1.7-1l-.3-2.4h-4l-.3 2.4a8 8 0 0 0-1.7 1l-2.3-.9-2 3.4L4.7 11a7.7 7.7 0 0 0 0 2l-2 1.5 2 3.4 2.3-.9a8 8 0 0 0 1.7 1l.3 2.4h4l.3-2.4a8 8 0 0 0-1.7 1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1Z"/></svg>
                                    <span>Preferences</span>
                                </a>
                                <a href="{{ route('settings.sessions') }}" class="settings-nav-item {{ request()->routeIs('settings.sessions') ? 'active' : '' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span>Activity & Sessions</span>
                                </a>
                            </div>

                            @if(auth()->user()->hasPermission('workspace.view') || auth()->user()->hasPermission('users.view'))
                                <div class="settings-nav-section settings-nav-section-workspace">
                                    <div class="settings-nav-label">Workspace</div>
                                    @if(auth()->user()->hasPermission('workspace.view'))<a href="{{ route('settings.workspace') }}" class="settings-nav-item {{ request()->routeIs('settings.workspace') ? 'active' : '' }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h1m4 0h1M9 11h1m4 0h1M9 15h1m4 0h1M10 21v-4h4v4"/></svg>
                                        <span>Workspace profile</span>
                                    </a>@endif
                                    @if(auth()->user()->hasPermission('users.view'))<a href="{{ route('settings.team') }}" class="settings-nav-item {{ request()->routeIs('settings.team') ? 'active' : '' }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0-0-8 4 4 0 0 0 0 8Zm6-7a3 3 0 0 1 0 6m4 10v-1a4 4 0 0 0-3-3.87"/></svg>
                                        <span>Team & access</span>
                                    </a>@endif
                                    @if(auth()->user()->hasPermission('roles.manage'))<a href="{{ route('settings.roles') }}" class="settings-nav-item {{ request()->routeIs('settings.roles') ? 'active' : '' }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v5c0 4.5 3.4 7.8 8 9 4.6-1.2 8-4.5 8-9V7l-8-4Zm0 5v4m0 4h.01"/></svg>
                                        <span>Roles & permissions</span>
                                    </a>@endif
                                </div>
                            @endif
                        </nav>
                        
                        <div class="settings-content">
                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if(session('error'))
                                <div class="alert alert-error">{{ session('error') }}</div>
                            @endif
                            @if($errors->any())
                                <div class="alert alert-error">{{ $errors->first() }}</div>
                            @endif
                            
                            @yield('content')
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    @stack('scripts')
</body>
</html>
