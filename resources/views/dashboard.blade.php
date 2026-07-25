@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
                <!-- Hero Section -->
                <section class="hero-row">
                    <div>
                        <div class="eyebrow">
                            <span class="live-dot"></span> Tuesday, 14 June 2026
                        </div>
                        <h1>Good morning, Lisa <span class="wave">✦</span></h1>
                        <p class="lede">Here's your operations pulse for today.</p>
                    </div>
                    <div class="hero-actions">
                        <button class="button button-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                            </svg>
                            Open calendar
                        </button>
                        <button class="button button-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            New booking
                        </button>
                    </div>
                </section>

                <!-- Metric Cards -->
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
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>
                                    </svg>
                                </div>
                                <span class="metric-trend">{{ $metric['trend'] }}</span>
                            </div>
                            <div class="metric-value">{{ $metric['value'] }}</div>
                            <div class="metric-label">{{ $metric['label'] }}</div>
                            <div class="metric-detail">{{ $metric['detail'] }}</div>
                        </div>
                    @endforeach
                </div>

                <!-- Today's Operations -->
                <section class="panel agenda-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Today's operations</h2>
                            <p>Tuesday, 14 June · All times EAT</p>
                        </div>
                        <button class="icon-button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </button>
                </section>

                <!-- Recent Bookings -->
                <section class="panel booking-preview">
                    <div class="panel-heading">
                        <div>
                            <h2>Recent bookings</h2>
                            <p>Latest activity across your portfolio</p>
                        </div>
                        <button class="panel-link">
                            View all bookings
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor">
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
                                        {{ Str::substr($booking['group'], 0, 2) }}
                                    </div>
                                    <div>
                                        <strong>{{ $booking['group'] }}</strong>
                                        <small>{{ $booking['id'] }} · {{ $booking['company'] }}</small>
                                    </div>
                                </div>
                                <span>{{ $booking['dates'] }}</span>
                                <span>{{ $booking['pax'] }} pax</span>
                                <span>{{ $booking['guide'] }}</span>
                                <span class="status-pill status-{{ $booking['color'] }}">
                                    <span class="status-dot"></span>
                                    {{ $booking['stage'] }}
                                </span>
                                <button class="icon-button">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M5 12h.01M12 12h.01M19 12h.01"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </main>
@stop

@push('scripts')
    <script>
        // Your vanilla JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded with Blade!');
        });
    </script>
@endpush