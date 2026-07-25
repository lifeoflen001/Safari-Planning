<div class="metric-grid">
    @foreach($metrics ?? [] as $metric)
        <div class="metric-card">
            <div class="metric-top">
                <div class="metric-icon metric-{{ $metric['accent'] }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="{{ $metric['icon'] === 'calendar' ? 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z' : ($metric['icon'] === 'suitcase' ? 'M9 6V4h6v2M4 8h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Zm0 4h16M10 12v2h4v-2' : ($metric['icon'] === 'users' ? 'M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-7a3 3 0 0 1 0 6m4 10v-1a4 4 0 0 0-3-3.87' : 'M5 16h14l-1.3-6.1A2 2 0 0 0 15.75 8H8.25a2 2 0 0 0-1.95 1.9L5 16Zm0 0v3m14-3v3M7 16h.01M17 16h.01M6 10h12')) }}"/>
                    </svg>
                </div>
                <span class="metric-trend {{ $metric['trend'] === 'Healthy' ? 'trend-neutral' : '' }}">
                    {{ $metric['trend'] }}
                </span>
            </div>
            <div class="metric-value">{{ $metric['value'] }}</div>
            <div class="metric-label">{{ $metric['label'] }}</div>
            <div class="metric-detail">{{ $metric['detail'] }}</div>
        </div>
    @endforeach
</div>