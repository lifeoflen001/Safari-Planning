<section class="panel alerts-panel">
    <div class="panel-heading">
        <div><h2>Needs attention</h2><p>Live checks from today’s visible operations</p></div>
        <span class="alert-count">{{ count($alerts ?? []) }} open</span>
    </div>
    <div class="alert-list">
        @forelse($alerts ?? [] as $alert)
            <div class="alert-row alert-{{ $alert['type'] }}">
                <div class="alert-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg></div>
                <div><strong>{{ $alert['title'] }}</strong><p>{{ $alert['detail'] }}</p></div>
                <a class="text-button" href="{{ $alert['url'] }}">{{ $alert['action'] }} <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></a>
            </div>
        @empty
            <div class="empty-alert">Everything visible is on track.</div>
        @endforelse
    </div>
</section>
