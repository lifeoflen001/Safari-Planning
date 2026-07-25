@php
    $pageMeta = [
        'calendar' => ['Workspace / Planning calendar', 'Planning calendar', 'See every itinerary, transfer and resource assignment at a glance.'],
        'transfers' => ['Operations / Transfers', 'Transfer desk', 'Coordinate airport pickups and hotel moves from one operational view.'],
        'resources' => ['Operations / Resources', 'Guides & vehicles', 'Monitor availability, capacity and service readiness across your field team.'],
        'reports' => ['Insights / Reports', 'Reports & exports', 'Turn your operational data into decisions and shareable summaries.'],
        'documents' => ['Insights / Documents', 'Documents', 'Keep itineraries, invoices and guest files close to the booking.'],
        'team' => ['Administration / Team & access', 'Team & access', 'Give every teammate the right level of visibility and control.'],
        'settings' => ['Administration / Settings', 'Workspace settings', 'Set the defaults that keep your operations desk consistent.'],
        'profile' => ['Account / Profile', 'Your profile', 'Keep your contact details and working preferences current.'],
    ][$section];
@endphp

@extends('layouts.app')

@section('title', $pageMeta[1] . ' | Safari Planner')
@section('page_title', $pageMeta[1])

@section('content')
<section class="page-header">
    <div>
        <div class="eyebrow">{{ $pageMeta[0] }}</div>
        <h1>{{ $pageMeta[1] }}</h1>
        <p class="lede">{{ $pageMeta[2] }}</p>
    </div>
    <div class="page-action">
        @if($section === 'calendar')
            <a class="button button-secondary" href="{{ route('planner.bookings') }}">Open Safari Board</a>
        @elseif($section === 'transfers')
            <button class="button button-primary" type="button" onclick="openModal('transferModal')">New transfer</button>
        @elseif($section === 'documents')
            <button class="button button-primary" type="button" onclick="openModal('documentModal')">Upload document</button>
        @elseif($section === 'team')
            <button class="button button-primary" type="button" onclick="openModal('inviteTeammateModal')">Invite teammate</button>
        @elseif($section === 'profile')
            <a class="button button-primary" href="{{ route('settings.profile') }}">Profile settings</a>
        @elseif($section === 'reports')
            <button class="button button-secondary" type="button" onclick="showToast('Report export prepared')">Export report</button>
        @endif
    </div>
</section>

@if($section === 'calendar')
    @include('planner.partials.planning-calendar', $calendar ?? [])
    @if(false)
    @php
        $weekStart = now()->startOfWeek();
    @endphp
    <div class="calendar-toolbar">
        <div class="date-navigator">
            <button class="icon-button" type="button" aria-label="Previous week">‹</button>
            <button class="today-button" type="button">Today</button>
            <button class="icon-button" type="button" aria-label="Next week">›</button>
            <strong>{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->addDays(6)->format('d M Y') }}</strong>
        </div>
        <div class="calendar-view-switch"><button>Month</button><button class="selected">Week</button><button>Day</button></div>
    </div>
    <div class="metric-grid">
        <div class="metric-card"><div class="metric-value">{{ $activities->count() }}</div><div class="metric-label">Scheduled activities</div><div class="metric-detail">Across the planning horizon</div></div>
        <div class="metric-card"><div class="metric-value">{{ $activities->where('status', 'confirmed')->count() }}</div><div class="metric-label">Confirmed</div><div class="metric-detail">Ready for operations</div></div>
        <div class="metric-card"><div class="metric-value">{{ $activities->filter(fn ($activity) => $activity->assignments->isEmpty())->count() }}</div><div class="metric-label">Need assignment</div><div class="metric-detail">Guides or vehicles missing</div></div>
    </div>
    <section class="panel booking-preview wide">
        <div class="panel-heading"><div><h2>Scheduled activities</h2><p>All times shown in {{ $user->timezone ?: 'East Africa Time' }}.</p></div></div>
        <div class="booking-table">
            <div class="booking-row booking-head"><span>Date & time</span><span>Activity</span><span>Guests</span><span>Booking</span><span>Assigned resources</span><span>Status</span><span>Board</span></div>
            @forelse($activities as $activity)
                @php $assignment = $activity->assignments->first(); @endphp
                <div class="booking-row">
                    <span>{{ $activity->starts_at?->format('d M · H:i') }}</span>
                    <span><strong>{{ $activity->title }}</strong><small>{{ ucfirst($activity->type) }}</small></span>
                    <span>{{ $activity->booking?->pax ?? '—' }} pax</span>
                    <span>{{ $activity->booking?->reference ?? 'Unlinked' }}</span>
                    <span>{{ $assignment?->guide?->name ?? 'Unassigned' }}<small>{{ $assignment?->vehicle?->registration_number ?? 'No vehicle' }}</small></span>
                    <span><span class="status-pill status-{{ $activity->status === 'confirmed' ? 'green' : 'amber' }}">{{ ucfirst(str_replace('_', ' ', $activity->status)) }}</span></span>
                    @if($activity->booking?->plan_year && $activity->booking?->plan_month)
                        <a class="report-link" href="{{ route('safari-board.index', [$activity->booking->plan_year, strtolower(date('F', mktime(0, 0, 0, (int) $activity->booking->plan_month, 1)))]) }}">Open row</a>
                    @else
                        <span class="muted">Unlinked</span>
                    @endif
                </div>
            @empty
                <div class="empty-state"><h2>No activities scheduled</h2><p>Your calendar is clear for the current planning window.</p></div>
            @endforelse
        </div>
    </section>
    @endif
@elseif($section === 'transfers')
    <div class="transfer-kpis">
        <div><span>Scheduled</span><b>{{ $transfers->count() }}</b><small>transfers in view</small></div>
        <div><span>Assigned</span><b>{{ $transfers->filter(fn ($transfer) => $transfer->assignments->isNotEmpty())->count() }}</b><small>with a field resource</small></div>
        <div><span>Attention</span><b class="warn-number">{{ $transfers->filter(fn ($transfer) => $transfer->assignments->isEmpty())->count() }}</b><small>need assignment</small></div>
        <div><span>Guests</span><b>{{ $transfers->sum(fn ($transfer) => $transfer->booking?->pax ?? 0) }}</b><small>travelling by transfer</small></div>
    </div>
    <section class="panel transfer-list">
        <div class="panel-heading"><div><h2>Transfer schedule</h2><p>Airport pickups, hotel moves and departures.</p></div></div>
        @forelse($transfers as $transfer)
            @php $assignment = $transfer->assignments->first(); @endphp
            <div class="transfer-row">
                <div class="transfer-time">{{ $transfer->starts_at?->format('H:i') }}<small>{{ $transfer->flight_number ?: 'Ground transfer' }}</small></div>
                <div class="transfer-route"><div class="route-icon route-green">→</div><div><strong>{{ $transfer->startLocation?->name ?? 'Pickup location' }} → {{ $transfer->endLocation?->name ?? 'Destination' }}</strong><small>{{ $transfer->booking?->group_name ?? 'Unlinked booking' }} · {{ $transfer->booking?->pax ?? '—' }} guests</small></div></div>
                <div class="transfer-assignee"><div><span>{{ $assignment?->guide?->name ?? 'Unassigned' }}</span><small>{{ $assignment?->vehicle?->registration_number ?? 'Vehicle unassigned' }}</small></div></div>
                <span class="status-pill status-{{ $assignment ? 'green' : 'amber' }}">{{ $assignment ? 'Assigned' : 'Needs assignment' }}</span>
                <button class="icon-button" type="button" onclick="showToast('Transfer details are ready for review')" aria-label="Open transfer details">···</button>
            </div>
    @empty
            <div class="empty-state"><h2>No transfers scheduled</h2><p>Add a transfer to start coordinating airport and hotel movements.</p></div>
        @endforelse
    </section>
    @include('planner.modals.create-transfer')
@elseif($section === 'resources')
    <div class="board-summary">
        <div><span class="board-summary-value">{{ $guides->count() }}</span><span>guides</span></div>
        <div><span class="board-summary-value">{{ $guides->where('status', 'available')->count() }}</span><span>available guides</span></div>
        <div><span class="board-summary-value">{{ $vehicles->count() }}</span><span>vehicles</span></div>
        <div><span class="board-summary-value">{{ $vehicles->where('status', 'available')->count() }}</span><span>available vehicles</span></div>
    </div>
    <section class="panel"><div class="panel-heading"><div><h2>Guides</h2><p>Availability and language coverage.</p></div></div><div class="resource-grid">
        @forelse($guides as $guide)
            <article class="resource-card"><div class="resource-head"><span class="avatar avatar-sage avatar-small">{{ collect(explode(' ', $guide->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span><span class="status-pill status-{{ $guide->status === 'available' ? 'green' : 'blue' }}">{{ ucfirst(str_replace('_', ' ', $guide->status)) }}</span></div><h3>{{ $guide->name }}</h3><p>{{ $guide->phone ?: 'Contact details not added' }}</p><div class="resource-language"><span>Languages</span><b>{{ implode(', ', $guide->languages ?: ['Not specified']) }}</b></div><div class="resource-status"><span>License</span><span>{{ $guide->license_expiry?->format('d M Y') ?: 'No expiry date' }}</span></div></article>
        @empty
            <div class="empty-state"><h2>No guides added</h2><p>Add guides to see availability and language fit here.</p></div>
        @endforelse
    </div></section>
    <section class="panel" style="margin-top:17px"><div class="panel-heading"><div><h2>Vehicles</h2><p>Capacity and service status.</p></div></div><div class="resource-grid">
        @forelse($vehicles as $vehicle)
            <article class="resource-card"><div class="resource-head"><span class="metric-icon metric-blue">4x4</span><span class="status-pill status-{{ $vehicle->status === 'available' ? 'green' : 'blue' }}">{{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}</span></div><h3>{{ $vehicle->registration_number }}</h3><p>{{ $vehicle->type }}</p><div class="resource-language"><span>Passenger capacity</span><b>{{ $vehicle->capacity }} guests</b></div><div class="resource-status"><span>Next service</span><span>{{ $vehicle->next_service_at?->format('d M Y') ?: 'Not scheduled' }}</span></div></article>
        @empty
            <div class="empty-state"><h2>No vehicles added</h2><p>Add vehicles to track capacity and service readiness.</p></div>
        @endforelse
    </div></section>
@elseif($section === 'reports')
    <div class="report-grid">
        @foreach([
            ['Monthly operations summary', 'Bookings, transfers and resource utilisation', 'Updated today'],
            ['Payment collection report', 'Outstanding balances and due dates', 'Updated today'],
            ['Guide utilisation', 'Assignments, language fit and availability', 'Updated today'],
            ['Booking source analysis', 'Companies, countries and conversion', 'Updated today'],
        ] as $index => $report)
            <article class="report-card"><div class="report-icon report-{{ $index }}">{{ $index + 1 }}</div><h3>{{ $report[0] }}</h3><p>{{ $report[1] }}</p><span>{{ $report[2] }}</span><button class="report-link" type="button" onclick="showToast('Report preview opened')">Open report →</button></article>
        @endforeach
    </div>
    <section class="panel"><div class="panel-heading"><div><h2>Portfolio snapshot</h2><p>Live totals from your operational database.</p></div></div><div class="booking-table wide">
        <div class="booking-row booking-head"><span>Measure</span><span>Value</span><span>Context</span><span>Measure</span><span>Value</span><span>Context</span></div>
        <div class="booking-row"><span>All bookings</span><span><strong>{{ $bookingCount }}</strong></span><span>Across the portfolio</span><span>Active bookings</span><span><strong>{{ $activeBookingCount }}</strong></span><span>Confirmed or in progress</span></div>
        <div class="booking-row"><span>Guests travelling</span><span><strong>{{ $guestCount }}</strong></span><span>Total passenger count</span><span>Outstanding payments</span><span><strong>€{{ number_format($outstandingPayments, 2) }}</strong></span><span>Not marked as paid</span></div>
    </div></section>
@elseif($section === 'documents')
    <section class="panel">
        <div class="panel-heading"><div><h2>Document library</h2><p>Secure files connected to bookings and operations.</p></div></div>
        @forelse($documents as $document)
            <div class="booking-row wide"><span><strong>{{ $document->name }}</strong><small>{{ $document->mime_type ?: 'File' }}</small></span><span>{{ $document->booking?->reference ?? 'Unlinked' }}</span><span>{{ $document->size ? number_format($document->size / 1024, 1) . ' KB' : '—' }}</span><span>{{ $document->uploader?->name ?? 'System' }}</span><span>{{ $document->created_at?->format('d M Y') }}</span><span><button class="icon-button" type="button" onclick="showToast('Document preview opened')" aria-label="Open document">→</button></span></div>
        @empty
            <div class="empty-state"><div class="empty-state-icon">＋</div><h2>Your document library is ready</h2><p>Upload an itinerary, invoice or guest file to see it here.</p><button class="button button-primary" type="button" onclick="openModal('documentModal')">Upload document</button></div>
        @endforelse
    </section>
    @include('planner.modals.upload-document')
@elseif($section === 'team')
    <div class="access-callout"><div class="access-callout-icon">✓</div><div><strong>Role-based access is active</strong><p>Admins manage users. Editors coordinate assignments. Commenters can flag changes without editing. Viewers can locate details safely.</p></div><span class="status-pill status-green">Protected</span></div>
    <section class="panel team-panel"><div class="panel-heading"><div><h2>People with planner access</h2><p>{{ $team->count() }} teammates · profiles and permissions</p></div></div><div class="booking-table wide">
        <div class="booking-row booking-head"><span>Profile</span><span>Role</span><span>Job title</span><span>Email</span><span>Status</span><span /></div>
        @foreach($team as $member)
            <div class="booking-row"><span><strong>{{ $member->name }}</strong><small>{{ $member->timezone ?: 'Timezone not set' }}</small></span><span>{{ ucfirst($member->role) }}</span><span>{{ $member->job_title ?: 'Not specified' }}</span><span>{{ $member->email }}</span><span><span class="status-pill status-{{ $member->is_active ? 'green' : 'rose' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></span><span>···</span></div>
        @endforeach
    </div></section>
    @include('planner.modals.invite-teammate')
@elseif($section === 'settings')
    <div class="settings-layout">
        <section class="panel"><div class="panel-heading"><div class="settings-section-icon">◷</div><div><h2>Planning defaults</h2><p>How dates, times and calendars appear for you.</p></div></div><div class="settings-fields"><label>Date format<select disabled><option>{{ $settings->date_format }}</option></select></label><label>Time format<select disabled><option>{{ $settings->time_format === '24h' ? '24-hour clock' : '12-hour clock' }}</option></select></label><label>Week starts on<select disabled><option>{{ ucfirst($settings->week_starts_on) }}</option></select></label><label>Default calendar view<select disabled><option>{{ ucfirst($settings->default_calendar_view) }}</option></select></label></div></section>
        <section class="panel"><div class="panel-heading"><div class="settings-section-icon settings-bell">●</div><div><h2>Notifications</h2><p>Stay ahead of changes that affect the day.</p></div></div><div class="settings-toggles"><div class="security-row"><div><strong>Email notifications</strong><small>Receive important operational updates by email.</small></div><span class="status-pill status-{{ $settings->email_notifications ? 'green' : 'neutral' }}">{{ $settings->email_notifications ? 'On' : 'Off' }}</span></div><div class="security-row"><div><strong>Conflict alerts</strong><small>Show resource overlap and capacity warnings.</small></div><span class="status-pill status-{{ $settings->conflict_alerts ? 'green' : 'neutral' }}">{{ $settings->conflict_alerts ? 'On' : 'Off' }}</span></div></div></section>
        <section class="panel"><div class="panel-heading"><div class="settings-section-icon settings-shield">✓</div><div><h2>Security & access</h2><p>Controls for keeping the workspace protected.</p></div></div><div class="security-row"><div><strong>Role-based access</strong><small>Admin, editor, commenter and view-only roles are enabled.</small></div><span class="status-pill status-green">Active</span></div><div class="security-row"><div><strong>Session security</strong><small>Sessions expire after 120 minutes of inactivity.</small></div><span>120 minutes</span></div></section>
    </div>
@elseif($section === 'profile')
    @php $profileInitials = collect(preg_split('/\s+/', trim($user->name)))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode(''); @endphp
    <div class="profile-layout">
        <section class="panel">
            <div class="profile-cover">
                <div class="profile-avatar-large" id="profileAvatarPreview">
                    @if($user->avatar_path)
                        <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="profile-avatar-img">
                    @else
                        {{ $profileInitials }}
                    @endif
                </div>
                <div>
                    <h2>{{ $user->name }}</h2>
                    <p>{{ $user->job_title ?: 'Planner' }}</p>
                </div>
                <span class="status-pill status-green">{{ ucfirst($user->role) }}</span>
            </div>
            <form class="profile-form" method="POST" action="{{ route('profile.update') }}" id="profileForm">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <label>Full name
                        <input type="text" name="name" value="{{ $user->name }}" required>
                    </label>
                    <label>Work email
                        <input type="email" value="{{ $user->email }}" readonly>
                    </label>
                </div>
                <div class="form-row">
                    <label>Job title
                        <input type="text" name="job_title" value="{{ $user->job_title }}">
                    </label>
                    <label>Phone number
                        <input type="text" name="phone" value="{{ $user->phone }}">
                    </label>
                </div>
                <label>Timezone
                    <select name="timezone">
                        @foreach(timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}" {{ $user->timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="page-action" style="margin-top:12px;">
                    <button type="submit" class="button button-primary">Save changes</button>
                </div>
            </form>
            <form class="profile-avatar-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" id="avatarForm">
                @csrf
                <label class="profile-avatar-upload" for="avatarInput">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20v-6m0 0-3 3m3-3 3 3M5 10a7 7 0 0 1 14 0c0 7-3 9-3 9h9s1-2 1-9a5 5 0 0 0-10 0c0 2.5 1 5 1 5H5z"/></svg>
                    <span>Change photo</span>
                </label>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" hidden onchange="handleAvatarUpload(this)">
                <div id="avatarError" style="color:#c26a66;font-size:11px;margin-top:8px;display:none;"></div>
            </form>
        </section>
        <aside class="profile-side">
            <div class="panel profile-side-card">
                <div class="profile-side-icon">✓</div>
                <h3>Access level</h3>
                <p>Your current role controls what you can edit, comment on and view in the planner.</p>
                <div class="profile-permission-list">
                    <span><i class="allowed-dot" /> View planning board</span>
                    <span><i class="allowed-dot" /> View operational reports</span>
                    @if($user->canEditPlanner())
                        <span><i class="allowed-dot" /> Edit bookings and resources</span>
                    @endif
                    @if($user->canCommentPlanner())
                        <span><i class="allowed-dot" /> Comment on plans</span>
                    @endif
                    @if($user->isAdmin())
                        <span><i class="allowed-dot" /> Manage team and settings</span>
                    @endif
                </div>
                <a class="profile-link" href="{{ route('settings.personal') }}">Open settings →</a>
            </div>
        </aside>
    </div>
@endif
@endsection
