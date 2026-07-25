<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Safari Planner | Tanzania Specialist Ltd</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    {{-- Load only CSS, not React --}}
    @vite(['resources/css/app.css'])
</head>
<body>
    {{-- The entire dashboard rendered with Blade --}}
    <div id="app">
        <div class="app-shell">
            @include('partials.sidebar')
            
            {{-- Main Content --}}
            <main class="main-content">
                {{-- Top Bar --}}
                <header class="topbar">
                    <div class="breadcrumb">
                        <span>Workspace</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                        <b>Overview</b>
                    </div>
                    
                    <div class="topbar-actions">
                        <div class="global-search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m20 20-4.7-4.7M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.0 0 0 1 0 14.4Z"/>
                            </svg>
                            <input placeholder="Search anything...">
                        </div>
                        
                        <button class="notification-button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
                            </svg>
                            <span>3</span>
                        </button>
                        
                        <div class="user-menu-wrap">
                            <button class="topbar-user">
                                @php $topbarInitials = collect(preg_split('/\s+/', trim($user->name)))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode(''); @endphp
                                <div class="avatar avatar-purple avatar-small">{{ $topbarInitials }}</div>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </header>
                
                {{-- Page Content --}}
                <div class="content-wrap">
                    {{-- Hero Section --}}
                    <section class="hero-row">
                        <div>
                            @php $firstName = collect(explode(' ', trim($user->name)))->first(); @endphp
                            <div class="eyebrow">
                                <span class="live-dot"></span> {{ \Carbon\Carbon::now()->format('l, j F Y') }}
                            </div>
                            <h1>Good morning, {{ $firstName }} <span class="wave">✦</span></h1>
                            <p class="lede">Here's your operations pulse for today.</p>
                        </div>
                        <div class="hero-actions">
                            <button class="button button-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                                </svg>
                                Open calendar
                            </button>
                            <button class="button button-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                New booking
                            </button>
                        </div>
                    </section>
                    
                    {{-- Metrics Grid --}}
                    <div class="metric-grid">
                        @php
                            $metrics = [
                                ['label' => "Today's operations", 'value' => '24', 'detail' => '6 arrivals · 8 departures', 'trend' => '+12%', 'accent' => 'sage', 'icon' => 'calendar'],
                                ['label' => 'Active bookings', 'value' => '186', 'detail' => '32 new this month', 'trend' => '+8.4%', 'accent' => 'blue', 'icon' => 'suitcase'],
                                ['label' => 'Guests travelling', 'value' => '412', 'detail' => 'Across 58 itineraries', 'trend' => '+5.7%', 'accent' => 'gold', 'icon' => 'users'],
                                ['label' => 'Resource utilisation', 'value' => '78%', 'detail' => 'Guides & vehicles', 'trend' => 'Healthy', 'accent' => 'purple', 'icon' => 'car'],
                            ];
                        @endphp
                        
                        @foreach($metrics as $metric)
                            <div class="metric-card">
                                <div class="metric-top">
                                    <div class="metric-icon metric-{{ $metric['accent'] }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>
                                        </svg>
                                    </div>
                                    <span class="metric-trend {{ $metric['trend'] === 'Healthy' ? 'trend-neutral' : '' }}">{{ $metric['trend'] }}</span>
                                </div>
                                <div class="metric-value">{{ $metric['value'] }}</div>
                                <div class="metric-label">{{ $metric['label'] }}</div>
                                <div class="metric-detail">{{ $metric['detail'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Dashboard Grid --}}
                    <div class="dashboard-grid">
                        {{-- Today's Operations --}}
                        <section class="panel agenda-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Today's operations</h2>
                                    <p>Tuesday, 14 June · All times EAT</p>
                                </div>
                                <button class="icon-button">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M5 12h.01M12 12h.01M19 12h.01"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="agenda-list">
                                @php
                                    $agenda = [
                                        ['time' => '06:45', 'type' => 'Arrival', 'title' => 'KLM 567 · JRO Airport', 'group' => 'The Anderson Family', 'detail' => '4 guests · Transfer to Gran Melia', 'color' => 'mint', 'person' => 'JM'],
                                        ['time' => '08:30', 'type' => 'Safari start', 'title' => 'Northern Circuit · Day 1', 'group' => 'Wildlife Wanderers', 'detail' => '6 guests · Arusha → Tarangire', 'color' => 'sand', 'person' => 'AK'],
                                        ['time' => '11:15', 'type' => 'Transfer', 'title' => 'Arusha Coffee Lodge → JRO', 'group' => 'Lena & Markus', 'detail' => '2 guests · Flight ET 815', 'color' => 'blue', 'person' => 'SN'],
                                        ['time' => '14:00', 'type' => 'Departure', 'title' => 'QR 1476 · JRO Airport', 'group' => 'Mendoza Group', 'detail' => '8 guests · from Arusha Serena', 'color' => 'rose', 'person' => 'PM'],
                                    ];
                                @endphp
                                
                                @foreach($agenda as $item)
                                    <div class="agenda-item">
                                        <div class="agenda-time">{{ $item['time'] }}</div>
                                        <div class="agenda-marker marker-{{ $item['color'] }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M5 16h14l-1.3-6.1A2 2 0 0 0 15.75 8H8.25a2 2 0 0 0-1.95 1.9L5 16Zm0 0v3m14-3v3M7 16h.01M17 16h.01M6 10h12"/>
                                            </svg>
                                        </div>
                                        <div class="agenda-copy">
                                            <div class="agenda-meta">
                                                <span>{{ $item['type'] }}</span>
                                                <span class="agenda-line"></span>
                                            </div>
                                            <strong>{{ $item['title'] }}</strong>
                                            <small>{{ $item['group'] }} · {{ $item['detail'] }}</small>
                                        </div>
                                        <div class="avatar avatar-{{ $loop->index % 2 === 0 ? 'sage' : 'blue' }} avatar-small">{{ $item['person'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <button class="panel-link">
                                View full schedule
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </button>
                        </section>
                        
                        {{-- Planning Overview --}}
                        <section class="panel planner-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Planning overview</h2>
                                    <p>Week of 14 – 20 June 2026</p>
                                </div>
                                <div class="segmented">
                                    <button class="selected">Week</button>
                                    <button>Month</button>
                                </div>
                            </div>
                            
                            <div class="mini-calendar">
                                <div class="calendar-corner"></div>
                                <div class="week-head">
                                    @foreach(['MON','TUE','WED','THU','FRI','SAT','SUN'] as $index => $day)
                                        <div class="{{ $index === 1 ? 'today-head' : '' }}">
                                            <span>{{ $day }}</span>
                                            <b>{{ 14 + $index }}</b>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="week-grid">
                                    @for($row = 0; $row < 12; $row++)
                                        <div class="hour-label">{{ sprintf('%02d', 6 + $row) }}:00</div>
                                        @for($day = 0; $day < 7; $day++)
                                            <div class="grid-cell"></div>
                                        @endfor
                                    @endfor
                                    
                                    {{-- Calendar Events --}}
                                    @php
                                        $calendarEvents = [
                                            ['day' => 0, 'start' => 1, 'span' => 2, 'label' => 'KLM 567 · Airport pickup', 'type' => 'transfer', 'guests' => '4 pax'],
                                            ['day' => 0, 'start' => 5, 'span' => 3, 'label' => 'Northern Circuit · Day 1', 'type' => 'safari', 'guests' => '6 pax'],
                                            ['day' => 1, 'start' => 2, 'span' => 4, 'label' => 'Serengeti Migration Camp', 'type' => 'safari', 'guests' => '12 pax'],
                                            ['day' => 2, 'start' => 0, 'span' => 3, 'label' => 'Arusha → JRO', 'type' => 'transfer', 'guests' => '2 pax'],
                                            ['day' => 2, 'start' => 6, 'span' => 4, 'label' => 'Ngorongoro Crater · Day 3', 'type' => 'safari', 'guests' => '8 pax'],
                                            ['day' => 3, 'start' => 3, 'span' => 3, 'label' => 'QR 1476 · Airport drop', 'type' => 'transfer', 'guests' => '8 pax'],
                                            ['day' => 4, 'start' => 1, 'span' => 5, 'label' => 'Lake Manyara · Day 4', 'type' => 'safari', 'guests' => '6 pax'],
                                        ];
                                    @endphp
                                    
                                    @foreach($calendarEvents as $event)
                                        <div class="planner-event event-{{ $event['type'] }}" 
                                             style="grid-column: {{ $event['day'] + 2 }} / span 1; grid-row: {{ $event['start'] + 2 }} / span {{ $event['span'] }};">
                                            <b>{{ $event['label'] }}</b>
                                            <span>{{ $event['guests'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <button class="panel-link">
                                Open planning calendar
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </button>
                        </section>
                    </div>
                    
                    {{-- Lower Grid --}}
                    <div class="lower-grid">
                        {{-- Alerts --}}
                        <section class="panel alerts-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Needs attention</h2>
                                    <p>Items that need your review</p>
                                </div>
                                <span class="alert-count">3 open</span>
                            </div>
                            
                            <div class="alert-list">
                                <div class="alert-row alert-warn">
                                    <div class="alert-icon">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M10.3 3.8 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0ZM12 9v4m0 4h.01"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <strong>Vehicle capacity mismatch</strong>
                                        <p>TS-2026-0182 · 8 guests assigned to TZ 412 JX (capacity 7)</p>
                                    </div>
                                    <button class="resolve-button">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m5 12 4 4L19 6"/>
                                        </svg>
                                        Resolve
                                    </button>
                                </div>
                                
                                <div class="alert-row alert-info">
                                    <div class="alert-icon">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <strong>Payment follow-up due today</strong>
                                        <p>Mendoza Group · €4,860 balance · due 14 Jun</p>
                                    </div>
                                    <button class="text-button">
                                        Review
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="alert-row alert-neutral">
                                    <div class="alert-icon">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7a3 3 0 0 1 0 6m4 10v-1a4 4 0 0 0-3-3.87"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <strong>Unassigned translator</strong>
                                        <p>Lena & Markus · 15 Jun · German language requested</p>
                                    </div>
                                    <button class="text-button">
                                        Assign
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </section>
                        
                        {{-- Resource Utilisation --}}
                        <section class="panel utilisation-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Resource utilisation</h2>
                                    <p>Across active operations</p>
                                </div>
                                <button class="icon-button">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M5 12h.01M12 12h.01M19 12h.01"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="utilisation-chart">
                                <div class="chart-value">78<span>%</span></div>
                                <div class="chart-label">Average utilisation</div>
                                <div class="bars">
                                    @php
                                        $barHeights = [42, 58, 48, 74, 66, 82, 78, 90, 80, 84, 76, 92];
                                    @endphp
                                    @foreach($barHeights as $index => $height)
                                        <div class="bar-wrap">
                                            <div class="bar {{ $index > 8 ? 'bar-active' : '' }}" style="height: {{ $height }}%"></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="chart-legend">
                                    <span><i class="legend-swatch sage"></i> Guides <b>82%</b></span>
                                    <span><i class="legend-swatch blue"></i> Vehicles <b>74%</b></span>
                                </div>
                            </div>
                        </section>
                    </div>
                    
                    {{-- Recent Bookings --}}
                    <section class="panel booking-preview">
                        <div class="panel-heading">
                            <div>
                                <h2>Recent bookings</h2>
                                <p>Latest activity across your portfolio</p>
                            </div>
                            <button class="panel-link">
                                View all bookings
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="booking-table">
                            <div class="booking-row booking-head">
                                <span>Booking</span>
                                <span>Travel dates</span>
                                <span>Guests</span>
                                <span>Lead guide</span>
                                <span>Status</span>
                                <span></span>
                            </div>
                            
                            @php
                                $bookings = [
                                    ['id' => 'TS-2026-0184', 'group' => 'The Anderson Family', 'company' => 'Tanzania Specialist', 'dates' => '14 – 21 Jun 2026', 'pax' => 4, 'stage' => 'Confirmed', 'guide' => 'Joel Mollel', 'color' => 'green'],
                                    ['id' => 'TS-2026-0183', 'group' => 'Wildlife Wanderers', 'company' => 'Safari Collective', 'dates' => '14 – 19 Jun 2026', 'pax' => 6, 'stage' => 'In progress', 'guide' => 'Amani Kweka', 'color' => 'blue'],
                                    ['id' => 'TS-2026-0182', 'group' => 'Mendoza Group', 'company' => 'Atlas Journeys', 'dates' => '14 – 17 Jun 2026', 'pax' => 8, 'stage' => 'Awaiting payment', 'guide' => 'Peter Mushi', 'color' => 'amber'],
                                    ['id' => 'TS-2026-0181', 'group' => 'Lena & Markus', 'company' => 'Nordic Trails', 'dates' => '15 – 22 Jun 2026', 'pax' => 2, 'stage' => 'Confirmed', 'guide' => 'Sofia Nyerere', 'color' => 'green'],
                                ];
                            @endphp
                            
                            @foreach($bookings as $booking)
                                <div class="booking-row">
                                    <div class="booking-name">
                                        <div class="avatar avatar-{{ $booking['color'] }} avatar-small">
                                            {{ substr($booking['group'], 0, 2) }}
                                        </div>
                                        <div>
                                            <strong>{{ $booking['group'] }}</strong>
                                            <small>{{ $booking['id'] }} · {{ $booking['company'] }}</small>
                                        </div>
                                    </div>
                                    <span>{{ $booking['dates'] }}</span>
                                    <span>{{ $booking['pax'] }} pax</span>
                                    <span>{{ $booking['guide'] }}</span>
                                    <span class="status-pill status-{{ $booking['color'] === 'amber' ? 'amber' : ($booking['color'] === 'blue' ? 'blue' : 'green') }}">
                                        <span class="status-dot"></span>
                                        {{ $booking['stage'] }}
                                    </span>
                                    <button class="icon-button">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M5 12h.01M12 12h.01M19 12h.01"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
    
    {{-- Load JavaScript (no React) --}}
    @vite(['resources/js/app.js'])
    
    {{-- Custom JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Planner loaded with Blade!');
            
            // Handle mobile menu toggle
            const sidebar = document.querySelector('.sidebar');
            const mobileMenu = document.querySelector('.mobile-menu-toggle');
            
            // Simple toast notification system
            function showToast(message) {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.innerHTML = `
                    <div class="toast-check">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>
                    ${message}
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3500);
            }
            
            // Handle button clicks
            document.querySelectorAll('.button').forEach(button => {
                button.addEventListener('click', function(e) {
                    const text = this.textContent.trim();
                    showToast(`${text} action ready`);
                });
            });
            
            // Handle notification button
            document.querySelector('.notification-button')?.addEventListener('click', function() {
                showToast('You have 3 notifications to review');
            });
        });
    </script>
</body>
</html>