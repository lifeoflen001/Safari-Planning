@php
    $calendar = $calendar ?? [];
    $weekStart = $calendar['weekStart'] ?? now()->startOfWeek();
    $days = $calendar['days'] ?? collect(range(0, 6))->map(fn ($day) => ['label' => $weekStart->copy()->addDays($day)->format('D'), 'date' => $weekStart->copy()->addDays($day)->format('j'), 'today' => $weekStart->copy()->addDays($day)->isToday()])->all();
    $events = $calendar['events'] ?? [];
@endphp

<section class="panel planner-panel">
    <div class="panel-heading">
        <div>
            <h2>Planning overview</h2>
            <p>Week of {{ $weekStart->format('j') }} – {{ $weekStart->copy()->addDays(6)->format('j F Y') }} · linked to Safari Board</p>
        </div>
        <div class="segmented"><button class="selected" type="button">Week</button><a href="{{ route('planner.calendar') }}">Calendar</a></div>
    </div>

    <div class="mini-calendar">
        <div class="calendar-corner"></div>
        <div class="week-head">
            @foreach($days as $day)
                <div class="{{ $day['today'] ? 'today-head' : '' }}"><span>{{ strtoupper($day['label']) }}</span><b>{{ $day['date'] }}</b></div>
            @endforeach
        </div>
        <div class="week-grid">
            @for($row = 0; $row < 12; $row++)
                <div class="hour-label">{{ sprintf('%02d', 6 + $row) }}:00</div>
                @for($day = 0; $day < 7; $day++)<div class="grid-cell"></div>@endfor
            @endfor

            @forelse($events as $event)
                <a class="planner-event event-{{ $event['type'] }}" href="{{ $event['url'] }}" style="grid-column: {{ $event['day'] + 2 }} / span 1; grid-row: {{ $event['start'] + 2 }} / span {{ $event['span'] }};" title="Open {{ $event['label'] }} on the Safari Board">
                    <b>{{ $event['label'] }}</b><span>{{ $event['guests'] }}</span>
                </a>
            @empty
                <div class="calendar-empty">No linked activities this week.</div>
            @endforelse
        </div>
    </div>

    <a href="{{ route('planner.calendar') }}" class="panel-link">Open planning calendar
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
</section>
