@php
    $sidebarUser = auth()->user();
    $sidebarInitials = collect(preg_split('/\s+/', trim($sidebarUser->name)))
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $navGroups = config('sidebar', []);
    $userRole = $sidebarUser->role ?: 'viewer';

    $iconPaths = [
        'grid' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        'calendar' => 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
        'checklist' => 'M5 5h2M5 12h2M5 19h2M10 5h9M10 12h9M10 19h9M4 4l1 1 2-2M4 11l1 1 2-2M4 18l1 1 2-2',
        'suitcase' => 'M9 6V4h6v2M4 8h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Zm0 4h16M10 12v2h4v-2',
        'plane' => 'm3 11 18-7-7 18-2-8-9-3Zm9 3 4-4',
        'users' => 'M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7a3 3 0 0 1 0 6m4 10v-1a4 4 0 0 0-3-3.87',
        'car' => 'M5 16h14l-1.3-6.1A2 2 0 0 0 15.75 8h-7.5a2 2 0 0 0-1.95 1.9L5 16Zm0 0v3m14-3v3M7 12h10M7.5 19h.01M16.5 19h.01',
        'chart' => 'M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-7',
        'file' => 'M6 3h8l4 4v14H6zM14 3v5h5M9 13h6M9 17h6M9 9h2',
        'settings' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.4-3.5a7.7 7.7 0 0 0-.1-1l2-1.5-2-3.4-2.3.9a8 8 0 0 0-1.7-1l-.3-2.4h-4l-.3 2.4a8 8 0 0 0-1.7 1l-2.3-.9-2 3.4L4.7 11a7.7 7.7 0 0 0 0 2l-2 1.5 2 3.4 2.3-.9a8 8 0 0 0 1.7 1l.3 2.4h4l.3-2.4a8 8 0 0 0 1.7-1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1Z',
        'shield' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.4-3.5a7.7 7.7 0 0 0-.1-1l2-1.5-2-3.4-2.3.9a8 8 0 0 0-1.7-1l-.3-2.4h-4l-.3 2.4a8 8 0 0 0-1.7 1l-2.3-.9-2 3.4L4.7 11a7.7 7.7 0 0 0 0 2l-2 1.5 2 3.4 2.3-.9a8 8 0 0 0 1.7 1l.3 2.4h4l.3-2.4a8 8 0 0 0 1.7-1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1Z',
        'bell' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
        'sliders' => 'M4 21h16M6 16v4m12-4v4M8 12h8m-8 4h8m-8-8h8',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'building' => 'M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4',
        'key' => 'M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777Zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4',
    ];

    $currentRoute = Route::currentRouteName();
@endphp

<aside class="sidebar">
    <div class="brand">
        <a href="{{ route('planner') }}" class="brand-logo-link">
            <img src="{{ asset('assets/images/ourlogo.png') }}" 
                 alt="Tanzania Specialist" 
                 class="brand-logo"
                 onerror="this.style.display='none'">
            <span class="brand-collapsed-mark" aria-hidden="true">
                <img src="{{ asset('assets/images/favicon/favicon-96x96.png') }}" alt="">
            </span>
        </a>
    </div>

    <nav>
        @foreach($navGroups as $groupLabel => $items)
            @php $visibleItems = array_filter($items, fn ($item) => in_array($userRole, $item['roles'] ?? [], true) && $sidebarUser->canAccessModule($item['id'])); @endphp
            @if($visibleItems)
                <div class="nav-group">
                    <span class="nav-label">{{ $groupLabel }}</span>
                    @foreach($visibleItems as $item)
                        <a href="{{ route($item['route']) }}" 
                           class="nav-item {{ $currentRoute === $item['route'] ? 'active' : '' }}"
                           title="{{ $item['label'] }}"
                           aria-label="{{ $item['label'] }}">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="{{ $iconPaths[$item['icon']] ?? '' }}"/>
                            </svg>
                            <span>{{ $item['label'] }}</span>
                            @if(isset($item['badge']))
                                <em>{{ $item['badge'] }}</em>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-bottom">
        <!--<div class="sidebar-status">
            <span class="status-dot"></span>
            <div>
                <strong>All systems operational</strong>
                <small>Last synced just now</small>
            </div>
        </div> -->

        <a href="{{ route('settings.profile') }}" class="user-card">
            @if($sidebarUser->avatar_path)
                <img src="{{ asset('storage/' . $sidebarUser->avatar_path) }}" alt="{{ $sidebarUser->name }}" class="avatar avatar-image avatar-small">
            @else
                <div class="avatar avatar-purple avatar-small">{{ $sidebarInitials }}</div>
            @endif
            <div>
                <strong>{{ $sidebarUser->name }}</strong>
                <small>{{ $sidebarUser->job_title ?: ucfirst($sidebarUser->role) }}</small>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M5 12h.01M12 12h.01M19 12h.01"/>
            </svg>
        </a>
    </div>
</aside>
