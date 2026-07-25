<section class="planning-calendar-workspace" aria-labelledby="planning-calendar-title">
    <div class="planning-calendar-topline">
        <div>
            <div class="eyebrow">Live operations schedule</div>
            <h2 id="planning-calendar-title">{{ $periodLabel }}</h2>
            <p>Activities visible to you, ordered by start time. Times use your workspace timezone.</p>
        </div>
        <div class="planning-calendar-actions">
            @if($actions['can_edit'])
                <a class="button button-primary" href="{{ $actions['new_activity'] }}" title="Open the activity editor">＋ New activity</a>
                <a class="button button-secondary" href="{{ $actions['assign'] }}">Assign resources</a>
            @endif
            @if($actions['can_transfer_edit'])
                <a class="button button-secondary" href="{{ $actions['new_transfer'] }}">New transfer</a>
            @endif
            @if($actions['can_import'])
                <a class="button button-ghost" href="{{ $actions['import'] }}">Import schedule</a>
            @endif
            @if($actions['can_export'])
                <a class="button button-ghost" href="{{ $actions['export'] }}">Export calendar</a>
            @endif
        </div>
    </div>

    <div class="planning-calendar-navigation">
        <div class="planning-period-controls">
            <a class="icon-button" href="{{ $navigation['previous'] }}" aria-label="Previous period" title="Previous period">‹</a>
            <a class="button button-secondary" href="{{ $navigation['today'] }}">Today</a>
            <a class="icon-button" href="{{ $navigation['next'] }}" aria-label="Next period" title="Next period">›</a>
            <label class="planning-date-picker">
                <span class="sr-only">Choose date</span>
                <input type="date" value="{{ $focusDate->toDateString() }}" onchange="window.location.href='{{ route('planner.calendar') }}?date='+this.value+'&view={{ $view }}'">
            </label>
        </div>
        <nav class="planning-view-switch" aria-label="Calendar view">
            <a class="{{ $view === 'month' ? 'is-active' : '' }}" href="{{ $navigation['month'] }}">Month</a>
            <a class="{{ $view === 'week' ? 'is-active' : '' }}" href="{{ $navigation['week'] }}">Week</a>
            <a class="{{ $view === 'day' ? 'is-active' : '' }}" href="{{ $navigation['day'] }}">Day</a>
        </nav>
    </div>

    <div class="planning-kpi-grid">
        @foreach($kpis as $kpi)
            <div class="planning-kpi planning-kpi-{{ $kpi['tone'] }}">
                <span>{{ $kpi['label'] }}</span>
                <strong>{{ $kpi['value'] }}</strong>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('planner.calendar') }}" class="planning-calendar-filters">
        <input type="hidden" name="date" value="{{ $focusDate->toDateString() }}">
        <input type="hidden" name="view" value="{{ $view }}">
        <label class="planning-search"><span aria-hidden="true">⌕</span><span class="sr-only">Search schedule</span><input name="q" value="{{ $filters['q'] }}" placeholder="Search activities, guests, bookings or locations…"></label>
        <label><span>Activity</span><select name="activity_type"><option value="">All types</option>@foreach($typeOptions as $type)<option value="{{ $type }}" @selected($filters['activity_type'] === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>@endforeach</select></label>
        <label><span>Status</span><select name="status"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Guide</span><select name="guide"><option value="">All guides</option>@foreach($guideOptions as $guide)<option value="{{ $guide->id }}" @selected($filters['guide'] === (string) $guide->id)>{{ $guide->name }}</option>@endforeach</select></label>
        <label><span>Vehicle</span><select name="vehicle"><option value="">All vehicles</option>@foreach($vehicleOptions as $vehicle)<option value="{{ $vehicle->id }}" @selected($filters['vehicle'] === (string) $vehicle->id)>{{ $vehicle->registration_number }}</option>@endforeach</select></label>
        <label><span>Booking</span><select name="booking"><option value="">All bookings</option>@foreach($bookingOptions as $booking)<option value="{{ $booking->id }}" @selected($filters['booking'] === (string) $booking->id)>{{ $booking->reference }} · {{ $booking->group_name }}</option>@endforeach</select></label>
        <label><span>Assignment</span><select name="assignment_state"><option value="">All assignments</option><option value="assigned" @selected($filters['assignment_state'] === 'assigned')>Assigned</option><option value="unassigned" @selected($filters['assignment_state'] === 'unassigned')>Needs assignment</option></select></label>
        <div class="planning-filter-actions"><button class="button button-primary" type="submit">Apply</button><a class="button button-ghost" href="{{ route('planner.calendar', ['date' => $focusDate->toDateString(), 'view' => $view]) }}">Reset</a></div>
    </form>

    @if($view !== 'month' && count($timeDays))
        <section class="planning-time-panel" aria-label="Time schedule">
            <div class="planning-time-grid" style="--planning-days: {{ count($timeDays) }}">
                <div class="planning-time-head"><span class="planning-time-corner">EAT</span>@foreach($timeDays as $day)<span class="planning-day-heading {{ $day['today'] ? 'is-today' : '' }}"><b>{{ $day['label'] }}</b><strong>{{ $day['number'] }}</strong></span>@endforeach</div>
                <div class="planning-time-body">
                    <div class="planning-hour-labels">@foreach($hours as $hour)<span>{{ sprintf('%02d:00', $hour) }}</span>@endforeach</div>
                    <div class="planning-day-tracks">
                        @foreach($timeDays as $day)
                            <div class="planning-day-track">
                                @foreach($hours as $hour)<i style="top: {{ (($hour - 6) / 16) * 100 }}%"></i>@endforeach
                                @foreach($day['blocks'] as $block)
                                    <a href="{{ $block['url'] }}" class="planning-time-block planning-status-{{ $block['status_class'] }} {{ $block['conflict'] ? 'is-conflict' : '' }}" style="top:{{ $block['top'] }}%;height:{{ $block['height'] }}%" title="{{ $block['title'] }} · {{ $block['time'] }}">
                                        <strong>{{ $block['time'] }} · {{ $block['title'] }}</strong><small>{{ $block['group'] }}</small>
                                        @if($block['conflict'] || $block['unassigned'])<em>{{ $block['attention'] }}</em>@endif
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="planning-schedule-panel">
            <div class="planning-schedule-heading"><div><h3>Scheduled activities</h3><p>{{ count($activities) }} records in this planning period.</p></div><span class="planning-sort-note">Sorted by start time</span></div>
            <div class="planning-table-scroll">
                <table class="planning-schedule-table">
                    <thead><tr><th>Date / time</th><th>Activity</th><th>Guest / group</th><th>Booking</th><th>Pickup</th><th>Destination</th><th>Guide</th><th>Vehicle</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($activities as $row)
                            <tr class="{{ $row['conflict'] ? 'has-conflict' : '' }}">
                                <td><strong>{{ $row['date'] }}</strong><small>{{ $row['time'] }}</small></td>
                                <td><strong>{{ $row['title'] }}</strong><small>{{ $row['type'] }}</small></td>
                                <td><strong>{{ $row['group'] }}</strong><small>{{ $row['pax'] }} pax</small></td>
                                <td>{{ $row['booking'] }}</td>
                                <td title="{{ $row['pickup'] }}">{{ $row['pickup'] }}</td>
                                <td title="{{ $row['destination'] }}">{{ $row['destination'] }}</td>
                                <td>{{ $row['guide'] }}</td>
                                <td>{{ $row['vehicle'] }}</td>
                                <td><span class="planning-status-badge planning-status-{{ $row['status_class'] }}">{{ $row['status'] }}</span>@if($row['conflict'] || $row['unassigned'])<small class="planning-attention-note">{{ $row['attention'] }}</small>@endif</td>
                                <td><a class="icon-button" href="{{ $row['url'] }}" title="Open booking" aria-label="Open booking">↗</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10"><div class="planning-empty-state"><div class="planning-empty-icon">▦</div><h3>No activities scheduled</h3><p>There are no visible activities in this planning period. Create one or import your schedule to begin.</p><div><a class="button button-primary" href="{{ $actions['new_activity'] }}">Create activity</a>@if($actions['can_import'])<a class="button button-secondary" href="{{ $actions['import'] }}">Import schedule</a>@endif</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</section>
