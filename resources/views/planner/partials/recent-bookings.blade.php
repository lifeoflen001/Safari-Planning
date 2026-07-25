<section class="panel booking-preview">
    <div class="panel-heading">
        <div><h2>Recent bookings</h2><p>Latest records from the Safari Board</p></div>
        <a href="{{ route('planner.bookings') }}" class="panel-link">View all bookings
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>
    </div>

    <div class="booking-table">
        <div class="booking-row booking-head"><span>Booking</span><span>Travel dates</span><span>Guests</span><span>Lead guide</span><span>Status</span><span></span></div>
        @forelse($bookings ?? [] as $booking)
            <a class="booking-row booking-row-link" href="{{ $booking['url'] ?? route('planner.bookings') }}">
                <div class="booking-name"><div class="avatar avatar-{{ $booking['color'] }} avatar-small">{{ substr($booking['group'], 0, 2) }}</div><div><strong>{{ $booking['group'] }}</strong><small>{{ $booking['id'] }} · {{ $booking['company'] }}</small></div></div>
                <span>{{ $booking['dates'] }}</span>
                <span>{{ $booking['pax'] }} pax</span>
                <span>{{ $booking['guide'] }}</span>
                <span class="status-pill status-{{ $booking['color'] === 'amber' ? 'amber' : ($booking['color'] === 'blue' ? 'blue' : 'green') }}"><span class="status-dot"></span>{{ $booking['stage'] }}</span>
                <span class="icon-button" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h.01M12 12h.01M19 12h.01"/></svg></span>
            </a>
        @empty
            <div class="empty-state"><h2>No bookings yet</h2><p>Import or create a Safari Board record to see it here.</p></div>
        @endforelse
    </div>
</section>
