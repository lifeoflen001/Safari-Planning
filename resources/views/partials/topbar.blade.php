@php
    $topbarUser = auth()->user();
    $topbarInitials = collect(preg_split('/\s+/', trim($topbarUser->name)))
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $topbarNotifications = $topbarUser->notifications()->latest()->limit(8)->get();
    $topbarUnreadCount = $topbarUser->unreadNotifications()->count();
@endphp
<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Toggle sidebar" aria-label="Toggle sidebar" aria-expanded="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path class="hamburger-line" d="M4 6h16"/><path class="hamburger-line" d="M4 12h16"/><path class="hamburger-line" d="M4 18h16"/>
            </svg>
        </button>
        <br>
        <div class="breadcrumb">
            <span>Workspace</span>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 18 6-6-6-6"/>
            </svg>
            <b>@yield('page_title', 'Overview')</b>
        </div>
    </div>

    <div class="topbar-actions">
        <form class="global-search" id="globalSearchForm" method="GET" action="{{ route('planner.bookings') }}" role="search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m20 20-4.7-4.7M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z"/>
            </svg>
            <input name="q" placeholder="Search the safari board..." id="globalSearch" autocomplete="off">
            <button class="global-search-button" type="submit" aria-label="Search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20 20-4.7-4.7M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z"/></svg>
            </button>
        </form>

        <div class="notification-wrap">
            <button class="notification-button" id="notificationButton" type="button" title="Notifications" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
                </svg>
                <span id="notificationCount" style="{{ $topbarUnreadCount ? '' : 'display:none' }}">{{ $topbarUnreadCount > 99 ? '99+' : $topbarUnreadCount }}</span>
            </button>

            <div class="notification-dropdown" id="notificationDropdown" hidden>
                <div class="notification-dropdown-head">
                    <div>
                        <strong>Notifications</strong>
                        <small>Live activity in your workspace</small>
                    </div>
                    <button type="button" id="markNotificationsRead">Mark all read</button>
                </div>
                <div class="notification-list" id="notificationList">
                    @forelse($topbarNotifications as $notification)
                        @php
                            $notificationData = $notification->data;
                            $notificationType = in_array($notificationData['type'] ?? 'blue', ['alert', 'blue', 'gold'], true) ? ($notificationData['type'] ?? 'blue') : 'blue';
                        @endphp
                        <a class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}" href="{{ $notificationData['url'] ?? route('planner.bookings') }}" data-notification-id="{{ $notification->id }}">
                            <span class="notification-dot notification-dot-{{ $notificationType }}"></span>
                            <span><strong>{{ $notificationData['title'] ?? 'Workspace update' }}</strong><small>{{ $notificationData['body'] ?? 'There is new activity in your workspace.' }}</small><em>{{ $notification->created_at?->diffForHumans() ?? 'Just now' }}</em></span>
                        </a>
                    @empty
                        <div class="notification-empty">You are all caught up.</div>
                    @endforelse
                </div>
                <a class="notification-footer" href="{{ route('planner.reports') }}">View activity centre <span>→</span></a>
            </div>
        </div>

        <button class="topbar-tool" id="themeToggle" type="button" title="Use dark theme" aria-label="Use dark theme">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.6 8.5 8.5 0 1 0 20.5 14.5Z"/>
            </svg>
        </button>

        <button class="topbar-tool" id="fullscreenToggle" type="button" title="Enter fullscreen" aria-label="Enter fullscreen">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/>
            </svg>
        </button>

        <div class="user-menu-wrap">
            <button class="topbar-user" id="userMenuToggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Open profile menu">
                @if($topbarUser->avatar_path)
                    <img src="{{ asset('storage/' . $topbarUser->avatar_path) }}" alt="{{ $topbarUser->name }}" class="avatar avatar-image avatar-small">
                @else
                    <div class="avatar avatar-purple avatar-small">{{ $topbarInitials }}</div>
                @endif
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div class="user-dropdown" id="userDropdown" style="display: none;">
                <div class="dropdown-header">
                    @if($topbarUser->avatar_path)
                        <img src="{{ asset('storage/' . $topbarUser->avatar_path) }}" alt="{{ $topbarUser->name }}" class="avatar avatar-image avatar-small">
                    @else
                        <div class="avatar avatar-purple avatar-small">{{ $topbarInitials }}</div>
                    @endif
                    <div>
                        <strong>{{ $topbarUser->name }}</strong>
                        <small>{{ ucfirst($topbarUser->role) }} · {{ $topbarUser->job_title ?: 'Workspace member' }}</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('settings.profile') }}">Profile <span>→</span></a>
                <a href="{{ route('settings.personal') }}">Personal settings <span>→</span></a>
                <a href="{{ route('settings.team') }}">Team & access <span>→</span></a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="dropdown-logout">Log out</button>
                </form>
            </div>
        </div>

    </div>
</header>
