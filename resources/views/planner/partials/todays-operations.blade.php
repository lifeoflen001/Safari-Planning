<section class="panel agenda-panel">
    <div class="panel-heading">
        <div>
            <h2>Today's operations</h2>
            <p>{{ now()->format('l, j F') }} · All times EAT</p>
        </div>
        <button class="icon-button" type="button" aria-label="Refresh today's operations" onclick="window.refreshOverview?.()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M5 12h.01M12 12h.01M19 12h.01"/>
            </svg>
        </button>
    </div>

    <div class="agenda-list">
        @forelse($agenda ?? [] as $item)
            <a class="agenda-item" href="{{ $item['url'] ?? route('planner.calendar') }}">
                <div class="agenda-time">{{ $item['time'] }}</div>
                <div class="agenda-marker marker-{{ $item['color'] }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 16h14l-1.3-6.1A2 2 0 0 0 15.75 8H8.25a2 2 0 0 0-1.95 1.9L5 16Zm0 0v3m14-3v3M7 16h.01M17 16h.01M6 10h12"/>
                    </svg>
                </div>
                <div class="agenda-copy">
                    <div class="agenda-meta"><span>{{ $item['type'] }}</span><span class="agenda-line"></span></div>
                    <strong>{{ $item['title'] }}</strong>
                    <small>{{ $item['group'] }} · {{ $item['detail'] }}</small>
                </div>
                <div class="avatar avatar-{{ $loop->index % 2 === 0 ? 'sage' : 'blue' }} avatar-small">{{ $item['person'] }}</div>
            </a>
        @empty
            <div class="empty-state"><h2>Nothing scheduled today</h2><p>New activities linked to the Safari Board will appear here automatically.</p></div>
        @endforelse
    </div>

    <a href="{{ route('planner.calendar') }}" class="panel-link">View full schedule
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
</section>
