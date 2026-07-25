@php($currentUser = $user ?? auth()->user())
<section class="control-centre-header">
    <div>
        <div class="eyebrow">Operations Dashboard</div>
        <div class="ops-live-clock" data-live-clock data-timezone="{{ $clock_timezone ?? config('app.timezone') }}" aria-label="Current local time">{{ $clock_time ?? now()->format('H:i:s') }}</div>
        <h1>{{ isset($first_name) ? ($greeting ?? 'Good day').', '.$first_name : 'Operations control centre' }}</h1>
        <p class="lede">Start today's planning : {{ $date_label }}.</p>
    </div>
    <div class="control-centre-actions">
        @if($currentUser?->canAccessModule('planner-calendar'))<a class="button button-secondary" href="{{ route('planner.calendar') }}">Open calendar</a>@endif
        @if($currentUser?->canAccessModule('planner-bookings') && $currentUser?->canEditPlanner())<a class="button button-primary" href="{{ route('planner.bookings') }}">New booking</a>@endif
    </div>
</section>

<section class="ops-attention" aria-labelledby="needs-attention-heading">
    <div class="ops-section-heading">
        <div><span class="eyebrow">Action queue</span><h2 id="needs-attention-heading">Needs attention</h2></div>
        <span class="ops-count">{{ count($needs_attention ?? []) }} open</span>
    </div>
    <div class="ops-attention-list">
        @forelse($needs_attention ?? [] as $alert)
            <a class="ops-attention-item ops-attention-{{ $alert['type'] }}" href="{{ $alert['url'] }}">
                <span class="ops-alert-mark" aria-hidden="true">!</span>
                <span><strong>{{ $alert['title'] }}</strong><small>{{ $alert['detail'] }}</small></span>
                <span class="ops-action-link">{{ $alert['action'] }} &rarr;</span>
            </a>
        @empty
            <div class="ops-empty-inline">No unresolved issues in today’s visible operations.</div>
        @endforelse
    </div>
</section>

<section class="ops-kpi-grid" aria-label="Operational key performance indicators">
    @foreach($kpis ?? [] as $kpi)
        <article class="ops-kpi ops-kpi-{{ $kpi['tone'] }}">
            <span>{{ $kpi['label'] }}</span>
            <strong>{{ $kpi['value'] }}</strong>
            <small>{{ $kpi['detail'] }}</small>
        </article>
    @endforeach
</section>

<div class="ops-table-grid">
    @foreach([['title' => 'Arrivals today', 'rows' => $arrivals ?? [], 'empty' => 'No arrivals scheduled today.'], ['title' => 'Departures today', 'rows' => $departures ?? [], 'empty' => 'No departures scheduled today.']] as $table)
        <section class="ops-panel">
            <div class="ops-section-heading"><div><h2>{{ $table['title'] }}</h2><p>Transfers linked to the Safari Board</p></div><span class="ops-count">{{ count($table['rows']) }}</span></div>
            <div class="ops-table-scroll">
                <table class="ops-table">
                    <thead><tr><th>Time</th><th>Guest</th><th>Flight</th><th>Location</th><th>Guide</th><th>Vehicle</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($table['rows'] as $row)
                            <tr>
                                <td><a href="{{ $row['url'] }}"><strong>{{ $row['time'] }}</strong></a></td>
                                <td><a href="{{ $row['url'] }}"><strong>{{ $row['guest'] }}</strong><small>{{ $row['pax'] }} guests</small></a></td>
                                <td>{{ $row['flight'] }}</td><td>{{ $row['location'] }}</td><td>{{ $row['guide'] }}</td><td>{{ $row['vehicle'] }}</td>
                                <td><span class="ops-status ops-status-{{ $row['status_class'] }}">{{ $row['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="ops-empty-cell" colspan="7">{{ $table['empty'] }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>

<div class="ops-main-grid">
    <section class="ops-panel">
        <div class="ops-section-heading"><div><h2>Today’s operations</h2><p>Chronological activity across visible bookings</p></div><a class="ops-text-link" href="{{ route('planner.calendar') }}">Full schedule &rarr;</a></div>
        <div class="ops-timeline">
            @forelse($timeline ?? [] as $item)
                <a class="ops-timeline-item" href="{{ $item['url'] }}">
                    <time>{{ $item['time'] }}</time><span class="ops-timeline-dot ops-dot-{{ $item['status_class'] }}"></span>
                    <span class="ops-timeline-copy"><strong>{{ $item['title'] }}</strong><small>{{ $item['type'] }} · {{ $item['guest'] }} · {{ $item['resource'] }}</small></span>
                    <span class="ops-status ops-status-{{ $item['status_class'] }}">{{ $item['status'] }}</span>
                </a>
            @empty
                <div class="ops-empty-state"><strong>No operations scheduled today</strong><span>Activities linked to the Safari Board will appear here.</span></div>
            @endforelse
        </div>
    </section>

    <div class="ops-resource-stack">
        @foreach([['label' => 'Guide availability', 'data' => $guide_availability ?? ['total' => 0, 'segments' => []]], ['label' => 'Fleet availability', 'data' => $vehicle_availability ?? ['total' => 0, 'segments' => []]]] as $resource)
            <section class="ops-panel ops-resource-widget">
                <div class="ops-section-heading"><div><h2>{{ $resource['label'] }}</h2><p>Current roster position</p></div></div>
                <div class="ops-resource-content">
                    @php($dashOffset = 25)
                    <svg class="ops-donut" viewBox="0 0 42 42" role="img" aria-label="{{ $resource['label'] }} chart">
                        <circle class="ops-donut-track" cx="21" cy="21" r="15.9155" pathLength="100"></circle>
                        @foreach($resource['data']['segments'] as $segment)
                            @if($segment['value'] > 0)
                                <circle class="ops-donut-segment ops-tone-{{ $segment['tone'] }}" cx="21" cy="21" r="15.9155" pathLength="100" stroke-dasharray="{{ $segment['percent'] }} {{ 100 - $segment['percent'] }}" stroke-dashoffset="{{ $dashOffset }}"></circle>
                                @php($dashOffset -= $segment['percent'])
                            @endif
                        @endforeach
                        <text x="21" y="22" text-anchor="middle">{{ $resource['data']['total'] }}</text>
                    </svg>
                    <div class="ops-legend">
                        @foreach($resource['data']['segments'] as $segment)
                            <span><i class="ops-legend-dot ops-tone-{{ $segment['tone'] }}"></i>{{ $segment['label'] }} <b>{{ $segment['value'] }}</b></span>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>

<div class="ops-analytics-grid">
    <section class="ops-panel ops-chart-panel">
        <div class="ops-section-heading"><div><h2>7-day workload</h2><p>Operations scheduled by day</p></div></div>
        <div class="ops-workload-chart">
            @foreach($workload ?? [] as $day)
                <div class="ops-workload-bar"><span style="height:{{ $day['height'] }}%" title="{{ $day['count'] }} operations"></span><small>{{ $day['count'] }}</small><label>{{ $day['label'] }}</label></div>
            @endforeach
        </div>
    </section>
    <section class="ops-panel ops-chart-panel">
        <div class="ops-section-heading"><div><h2>Booking status</h2><p>Arrivals in the current month</p></div></div>
        <div class="ops-status-chart">
            @forelse($booking_statuses ?? [] as $status => $count)
                @php($statusTotal = max(1, array_sum($booking_statuses)))
                <div><span><b>{{ ucfirst(str_replace('_', ' ', $status)) }}</b><em>{{ $count }}</em></span><i><u class="ops-status-fill-{{ $status }}" style="width:{{ round($count / $statusTotal * 100) }}%"></u></i></div>
            @empty
                <div class="ops-empty-inline">No bookings in the current month.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="ops-bottom-grid">
    <section class="ops-panel">
        <div class="ops-section-heading"><div><h2>Recent activity</h2><p>Latest changes recorded in the workspace</p></div></div>
        <div class="ops-feed">
            @forelse($recent_activity ?? [] as $activity)
                <div class="ops-feed-item"><span class="ops-feed-dot"></span><span><strong>{{ $activity['action'] }}</strong><small>{{ $activity['user'] }} · {{ $activity['time'] }}</small></span></div>
            @empty
                <div class="ops-empty-state"><strong>No recent activity</strong><span>Workspace changes will appear here.</span></div>
            @endforelse
        </div>
    </section>
    <section class="ops-panel">
        <div class="ops-section-heading"><div><h2>Quick actions</h2><p>Common planning tasks</p></div></div>
        <div class="ops-quick-actions">
            @if($currentUser?->canAccessModule('planner-bookings') && $currentUser?->canEditPlanner())<a href="{{ route('planner.bookings') }}">New booking <span>+</span></a>@endif
            @if($currentUser?->canAccessModule('planner-transfers') && $currentUser?->hasPermission('transfers.edit'))<a href="{{ route('planner.transfers') }}">New transfer <span>+</span></a>@endif
            @if($currentUser?->canAccessModule('planner-bookings') && $currentUser?->hasPermission('planner.edit'))<a href="{{ route('safari-board.index', [now()->year, strtolower(now()->format('F'))]) }}">Assign resources <span>&rarr;</span></a>@endif
            @if($currentUser?->canAccessModule('planner-bookings') && $currentUser?->canImport())<a href="{{ route('safari-board.index', [now()->year, strtolower(now()->format('F'))]) }}">Import planning data <span>&uarr;</span></a>@endif
            @if($currentUser?->canExport())<a href="{{ route('planner.transfers.export', [now()->year, strtolower(now()->format('F'))]) }}">Export daily plan <span>&darr;</span></a>@endif
        </div>
    </section>
</div>
