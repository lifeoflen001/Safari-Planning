@extends('layouts.app')

@section('title', 'Transfer Board | Safari Planner')
@section('page_title', 'Transfer Board')
@section('body_class', 'transfer-board-page')

@php
    $monthSlug = strtolower($monthName);
    $previousMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $nextMonth = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    $previousDay = $focusDate->copy()->subDay();
    $nextDay = $focusDate->copy()->addDay();
    $today = now();
    $sortFields = [
        'date' => 'Date',
        'pickup_time' => 'Pickup Time',
        'group_name' => 'Group / Guest Name',
        'driver_guide' => 'Driver / Guide',
        'contact_number' => 'Contact Number',
        'supplier' => 'Supplier',
        'vehicle' => 'Vehicle',
        'start_location' => 'Start Location',
        'end_location' => 'End Location',
        'notes' => 'Notes',
        'status' => 'Status',
    ];
    $sortDirection = request('direction') === 'asc' ? 'desc' : 'asc';
    $transferFilterCount = request()->filled('status') ? 1 : 0;
    $queryFor = fn (array $params = []) => request()->fullUrlWithQuery($params);
    $dayUrl = fn (\Carbon\Carbon $date) => route('planner.transfers', [$date->year, strtolower($date->format('F'))]).'?day='.$date->toDateString();
    $exportUrl = route('planner.transfers.export', [$year, $monthSlug]).(request()->query() ? '?'.http_build_query(request()->query()) : '');
@endphp

@section('content')
<style>
    .transfer-board-page .main-content { max-width: none; }
    .transfer-board-page .content-wrap { max-width: none; padding: 16px 14px 34px; }
    .transfer-board-shell { width: 100%; background:#fff; border:1px solid #e6ded1; border-radius:16px; overflow:visible; }
    .transfer-board-header { align-items: flex-start; }
    .transfer-board-actions { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .transfer-board-actions .button { position:relative; }
    .transfer-board-actions .button[data-icon] { width:32px; min-width:32px; height:32px; padding:0; font-size:0; }
    .transfer-board-actions .button[data-icon]::before { content:attr(data-icon); display:grid; width:100%; height:100%; place-items:center; font-size:16px; }
    .transfer-board-actions .button[data-icon]::after { content:attr(title); position:absolute; right:0; top:calc(100% + 7px); z-index:20; padding:6px 8px; border-radius:7px; background:#4e3c2e; color:#fffaf3; font-size:10px; white-space:nowrap; opacity:0; pointer-events:none; transform:translateY(-3px); transition:opacity .16s ease,transform .16s ease; }
    .transfer-board-actions .button[data-icon]:hover::after, .transfer-board-actions .button[data-icon]:focus-visible::after { opacity:1; transform:translateY(0); }
    .transfer-board-actions button[onclick*="transferModal"] { width:32px; min-width:32px; height:32px; padding:0; font-size:0; position:relative; }
    .transfer-board-actions button[onclick*="transferModal"]::before { content:'+'; display:grid; width:100%; height:100%; place-items:center; font-size:16px; }
    .transfer-board-actions button[onclick*="transferModal"]::after { content:'New transfer'; position:absolute; right:0; top:calc(100% + 7px); z-index:20; padding:6px 8px; border-radius:7px; background:#4e3c2e; color:#fffaf3; font-size:10px; white-space:nowrap; opacity:0; pointer-events:none; transform:translateY(-3px); transition:opacity .16s ease,transform .16s ease; }
    .transfer-board-actions button[onclick*="transferModal"]:hover, .transfer-board-actions button[onclick*="transferModal"]:focus-visible { background:#e8dccb; border-color:#b9a27f; color:#3e2e22; outline:2px solid #d9c19e; outline-offset:1px; }
    .transfer-board-actions button[onclick*="transferModal"]:hover::after, .transfer-board-actions button[onclick*="transferModal"]:focus-visible::after { opacity:1; transform:translateY(0); }
    .transfer-board-period { display:flex; justify-content:space-between; gap:16px; align-items:center; padding:14px 16px 10px; background:linear-gradient(135deg,#f8f1e7,#fff); border-radius:16px 16px 0 0; }
    .transfer-board-period .board-period-label { color:#7b746b; font-size:12px; }
    .transfer-board-period .board-period-title { margin-top:6px; color:#3e2e22; font:800 24px/1 'Plus Jakarta Sans',sans-serif; }
    .transfer-daily-tools { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .transfer-daily-tools strong { min-width:145px; text-align:center; color:#4e3c2e; font-size:12px; }
    .transfer-daily-tools input { min-height:38px; height:38px; padding:0 10px; border:1px solid #d9cdbd; border-radius:10px; background:#fff; color:#3e2e22; font-size:11px; }
    .transfer-board-shell .safari-month-tabs { display:grid; grid-template-columns:repeat(12,minmax(55px,1fr)); gap:5px; padding:8px 12px; border-block:1px solid #eee5d8; overflow:auto; }
    .transfer-board-shell .safari-month-tabs a { display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 6px; border-radius:10px; color:#796f64; font-size:12px; text-decoration:none; white-space:nowrap; }
    .transfer-board-shell .safari-month-tabs a:hover, .transfer-board-shell .safari-month-tabs a.is-active { background:#e8dccb; color:#3e2e22; }
    .transfer-board-shell .safari-month-tabs strong { padding:2px 6px; border-radius:999px; background:#fff; font-size:10px; }
    .transfer-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; padding:12px 14px; }
    .transfer-summary-grid .board-stat { display:flex; flex-direction:column; gap:6px; padding:13px 15px; border:1px solid #eee5d8; border-radius:14px; background:#fbf8f2; }
    .transfer-summary-grid .board-stat span { color:#847b70; font-size:11px; }
    .transfer-summary-grid .board-stat strong { color:#3e2e22; font-size:22px; }
    .transfer-board-toolbar { display:flex; align-items:center; gap:7px; flex-wrap:wrap; padding:0 14px 10px; border:0; background:transparent; }
    .transfer-board-toolbar .board-search { display:flex; align-items:center; flex:1 1 280px; gap:8px; min-height:40px; padding:0 12px; border:1px solid #d9cdbd; border-radius:11px; background:#fff; color:#8a7b6c; }
    .transfer-board-toolbar .board-search input { width:100%; height:100%; border:0; outline:0; background:transparent; color:#3e2e22; }
    .transfer-board-toolbar select, .transfer-board-toolbar input { height:36px; }
    .transfer-board-toolbar .button { min-height:36px; }
    .transfer-filter-count { display:inline-flex; align-items:center; justify-content:center; min-width:18px; min-height:18px; margin-left:4px; padding:0 5px; border-radius:999px; background:#e8dccb; color:#573f2d; font-size:10px; }
    .transfer-filter-dropdown { position:relative; }
    .transfer-filter-dropdown summary { list-style:none; cursor:pointer; min-height:36px; padding:10px 12px; border:1px solid #d9cdbd; border-radius:10px; background:#fff; color:#594b3d; font-size:12px; }
    .transfer-filter-dropdown summary::-webkit-details-marker { display:none; }
    .transfer-filter-menu { position:absolute; top:calc(100% + 6px); right:0; z-index:20; width:250px; padding:10px; border:1px solid #e5d8c8; border-radius:10px; background:#fcfaf7; box-shadow:0 12px 28px #46301c24; }
    .transfer-filter-grid { display:block; }
    .transfer-filter-grid label { display:flex; flex-direction:column; gap:5px; color:#675a4d; font-size:11px; font-weight:700; }
    .transfer-filter-grid select { min-height:36px; padding:0 9px; border:1px solid #d9cdbd; border-radius:9px; background:#fff; }
    .transfer-filter-footer { display:flex; align-items:center; justify-content:flex-end; gap:7px; margin-top:9px; }
    .transfer-column-menu { position:absolute; right:0; top:40px; z-index:5; min-width:185px; max-height:245px; overflow:auto; padding:8px 9px; border:1px solid #d9cdbd; border-radius:10px; background:#fff; box-shadow:0 10px 22px #46301c1c; }
    .transfer-column-menu label { display:block; padding:3px 0; color:#5c5044; font-size:10px; line-height:1.2; }
    .transfer-board-toolbar .board-columns { position:relative; }
    .transfer-board-toolbar .board-columns summary { list-style:none; cursor:pointer; padding:10px 12px; border:1px solid #d9cdbd; border-radius:10px; color:#594b3d; font-size:12px; }
    .transfer-column-menu label { display:flex; align-items:center; gap:6px; padding:3px 0; font-size:10px; white-space:nowrap; }
    .transfer-table-wrap { max-height:68vh; overflow:auto; border-block:1px solid #e6ded1; }
    .transfer-planning-table { width:100%; min-width:1180px; border-collapse:separate; border-spacing:0; table-layout:fixed; font-size:10px; color:#4c4137; }
    .transfer-planning-table th, .transfer-planning-table td { min-width:0; height:29px; padding:5px 6px; border-right:1px solid #ebe3d8; border-bottom:1px solid #ebe3d8; vertical-align:middle; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .transfer-planning-table th { position:sticky; top:0; z-index:5; height:32px; padding:7px 6px; background:#eadcc9; color:#493424; border-right:1px solid #d5c3ac; border-bottom:2px solid #d0bda4; font-size:9px; font-weight:800; text-transform:uppercase; }
    .transfer-planning-table th a { color:inherit; text-decoration:none; }
    .transfer-planning-table th a:hover { text-decoration:underline; }
    .transfer-planning-table tr:hover td { background:#fffaf2; }
    .transfer-planning-table .transfer-sticky { position:sticky; z-index:3; background:#fff; }
    .transfer-planning-table thead .transfer-sticky { z-index:8; background:#f1b56b; }
    .transfer-planning-table .transfer-date { left:0; width:78px; min-width:78px; }
    .transfer-planning-table .transfer-time { left:78px; width:88px; min-width:88px; }
    .transfer-planning-table .transfer-group { left:166px; width:178px; min-width:178px; }
    .transfer-planning-table td.transfer-time { font-weight:800; }
    .transfer-planning-table td.transfer-group { font-weight:700; }
    .transfer-planning-table td.transfer-notes { max-width:240px; }
    .transfer-planning-table .transfer-actions { position:sticky; right:0; z-index:4; width:72px; min-width:72px; background:#fff; box-shadow:-4px 0 7px rgba(77,58,39,.05); }
    .transfer-planning-table thead .transfer-actions { z-index:9; background:#f1b56b; }
    .transfer-actions-wrap { display:flex; align-items:center; gap:3px; }
    .transfer-actions-wrap { flex-wrap:wrap; }
    .transfer-actions-wrap .action-icon { display:inline-grid; place-items:center; }
    .transfer-actions-wrap .action-icon svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-linecap:round; stroke-linejoin:round; stroke-width:1.35; }
    .transfer-actions-wrap .icon-button { width:22px; height:22px; padding:2px; font-size:12px; }
    .transfer-status { display:inline-flex; align-items:center; max-width:100%; padding:3px 7px; border-radius:999px; font-size:10px; font-weight:700; }
    .transfer-status-planned { background:#f6ead0; color:#80663f; }
    .transfer-status-confirmed { background:#e2f2e8; color:#2e7652; }
    .transfer-status-in_progress { background:#e2edf8; color:#416b96; }
    .transfer-status-completed { background:#dff2e5; color:#28704b; }
    .transfer-status-delayed, .transfer-status-cancelled { background:#f8e3df; color:#a05243; }
    .transfer-status-archived { background:#e8e4df; color:#776c62; }
    .transfer-empty { padding:40px 20px; text-align:center; color:var(--muted); }
    .transfer-pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 14px 13px; color:#7b746b; font-size:12px; }
    .transfer-pagination .pagination { display:flex; gap:4px; align-items:center; }
    .transfer-pagination a, .transfer-pagination .pagination span { min-width:26px; padding:5px 7px; border:1px solid var(--line); border-radius:6px; text-align:center; text-decoration:none; color:var(--ink); }
    .transfer-pagination > span { padding:0; border:0; }
    .transfer-pagination .active span { background:var(--sand); font-weight:800; }
    .transfer-resizer { position:absolute; top:0; right:-3px; width:7px; height:100%; cursor:col-resize; }
    body.transfer-board-fullscreen .transfer-board-shell { position:fixed; inset:12px; z-index:50; overflow:auto; background:#fff; box-shadow:0 24px 80px #3d281c44; }
    body.transfer-board-fullscreen .transfer-table-wrap { max-height:calc(100vh - 290px); }
    @media (max-width:1000px) { .transfer-board-period { align-items:flex-start; flex-direction:column; } .transfer-summary-grid { grid-template-columns:repeat(2,1fr); } .transfer-board-header { align-items:flex-start; flex-direction:column; } }
    @media (max-width:720px) {
        body.transfer-board-page .content-wrap { padding:12px 8px 24px; }
        .transfer-board-header { gap:12px; margin-bottom:14px; }
        .transfer-board-header h1 { font-size:22px; }
        .transfer-board-header .lede { max-width:34ch; line-height:1.45; }
        .transfer-board-actions { width:100%; gap:5px; flex-wrap:nowrap; overflow-x:auto; padding:2px 2px 5px; scrollbar-width:none; }
        .transfer-board-actions::-webkit-scrollbar { display:none; }
        .transfer-board-actions .button { flex:0 0 34px !important; width:34px; min-width:34px; height:34px; }
        .transfer-board-period { gap:10px; padding:12px 10px 9px; }
        .transfer-board-period .board-period-title { font-size:20px; }
        .transfer-daily-tools { width:100%; }
        .transfer-daily-tools .button { flex:1 1 34px; min-height:34px; }
        .transfer-daily-tools strong { flex:1 1 100%; min-width:0; order:-1; text-align:left; }
        .transfer-daily-tools form { flex:1 1 100%; }
        .transfer-daily-tools input { width:100%; }
        .transfer-board-shell .safari-month-tabs { grid-template-columns:repeat(12,minmax(58px,1fr)); gap:4px; padding:6px 8px; -webkit-overflow-scrolling:touch; }
        .transfer-board-shell .safari-month-tabs a { padding:8px 4px; font-size:11px; }
        .transfer-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; padding:8px; }
        .transfer-summary-grid .board-stat { padding:10px; border-radius:11px; }
        .transfer-summary-grid .board-stat span { font-size:10px; }
        .transfer-summary-grid .board-stat strong { font-size:18px; }
        .transfer-board-toolbar { flex-direction:column; align-items:stretch; gap:6px; padding:0 8px 8px; }
        .transfer-board-toolbar .board-search { flex:0 0 auto; width:100%; min-height:36px; }
        .transfer-board-toolbar > select, .transfer-board-toolbar > .button, .transfer-board-toolbar > .board-columns, .transfer-board-toolbar > .transfer-filter-dropdown { width:100%; }
        .transfer-board-toolbar > select, .transfer-board-toolbar > .button { min-height:36px; }
        .transfer-filter-dropdown summary { text-align:center; }
        .transfer-filter-menu { left:0; right:0; width:auto; }
        .transfer-board-toolbar .board-columns summary { text-align:center; }
        .transfer-filter-footer { gap:6px; }
        .transfer-filter-footer .button { flex:1 1 0; }
        .transfer-column-menu { position:fixed; left:8px; right:8px; top:auto; width:auto; max-height:50vh; padding:8px 10px; }
        .board-table-meta { padding:0 8px 6px; font-size:10px; }
        .transfer-table-wrap { width:100%; min-height:220px; max-height:calc(100vh - 300px); overflow-x:auto; overflow-y:auto; -webkit-overflow-scrolling:touch; overscroll-behavior:contain; scrollbar-gutter:stable; touch-action:pan-x pan-y; }
        .transfer-planning-table { width:max-content; min-width:1040px; font-size:9px; }
        .transfer-planning-table th, .transfer-planning-table td { height:28px; padding:4px 5px; }
        .transfer-planning-table td.transfer-sticky { position:static; left:auto !important; box-shadow:none; }
        .transfer-planning-table th.transfer-sticky { position:sticky; top:0; left:auto !important; z-index:5; box-shadow:none; }
        .transfer-planning-table td.transfer-actions, .transfer-planning-table th.transfer-actions { position:static; box-shadow:none; }
        .transfer-planning-table .transfer-date { left:0; width:64px; min-width:64px; }
        .transfer-planning-table .transfer-time { left:64px; width:72px; min-width:72px; }
        .transfer-planning-table .transfer-group { left:136px; width:140px; min-width:140px; }
        .transfer-planning-table td.transfer-notes { max-width:180px; }
        .transfer-planning-table .transfer-actions { width:68px; min-width:68px; }
        .transfer-pagination { flex-direction:column; align-items:flex-start; gap:7px; padding:9px 8px 11px; }
        .transfer-pagination .pagination { max-width:100%; overflow:auto; }
    }
    @media print { .sidebar,.topbar,.transfer-board-header,.safari-board-period,.safari-month-tabs,.transfer-board-toolbar,.transfer-pagination,.transfer-actions,.transfer-board-page .board-alert,.transfer-board-page .page-header { display:none !important; } .main-content { margin:0 !important; max-width:none !important; } .transfer-board-shell { border:0 !important; } .transfer-table-wrap { max-height:none; overflow:visible; } .transfer-planning-table th { position:static; } }
</style>

<section class="page-header transfer-board-header">
    <div>
        <div class="eyebrow">Operations / Transfer planning</div>
            <h1>Transfer desk</h1>
        <p class="lede">Monthly transfer data in the same compact planning language as the Safari Board.</p>
    </div>
    <div class="transfer-board-actions">
        @if($admin)
            <button class="button button-primary" type="button" onclick="openModal('transferModal')">＋ New transfer</button>
            @if($user->hasPermission('transfers.edit'))
                <button class="button button-secondary" type="button" data-icon="↥" title="Import transfers" onclick="openModal('transferImportModal')">Import transfers</button>
            @endif
        @endif
        @if($canExportTransfers)
            <a class="button button-secondary" data-icon="⇩" title="Export transfers" href="{{ $exportUrl }}">Export CSV</a>
        @endif
        <button class="button button-ghost" data-icon="↗" title="Full screen" type="button" data-transfer-fullscreen>Full screen</button>
        <button class="button button-ghost" data-icon="▣" title="Print" type="button" onclick="window.print()">Print</button>
    </div>
</section>

<section class="safari-board-shell transfer-board-shell" data-transfer-board>
    <div class="safari-board-period">
        <div>
            <div class="board-period-label">Monthly transfer planning</div>
            <div class="board-period-title">{{ $monthName }} {{ $year }}</div>
        </div>
        <div class="transfer-daily-tools">
            <a class="button button-ghost" href="{{ $dayUrl($previousDay) }}" aria-label="Previous day">←</a>
            <a class="button button-secondary" href="{{ $dayUrl($today) }}">Today</a>
            <strong>{{ $selectedDay ? $selectedDay->format('D, d M Y') : 'Entire month' }}</strong>
            <a class="button button-ghost" href="{{ $dayUrl($nextDay) }}" aria-label="Next day">→</a>
            <form method="GET" action="{{ route('planner.transfers', [$year, $monthSlug]) }}">
                <input type="date" name="day" value="{{ $selectedDay?->toDateString() }}" min="{{ \Carbon\Carbon::create($year, $month, 1)->toDateString() }}" max="{{ \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString() }}" aria-label="Choose transfer date" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <nav class="safari-month-tabs" aria-label="Transfer planning months">
        @foreach($months as $number => $label)
            <a class="{{ $number === $month ? 'is-active' : '' }}" href="{{ route('planner.transfers', [$year, strtolower($label)]) }}">
                <span>{{ substr($label, 0, 3) }}</span><strong>{{ $monthCounts[$number] ?? 0 }}</strong>
            </a>
        @endforeach
    </nav>

    <div class="board-summary-grid transfer-summary-grid">
        <div class="board-stat"><span>{{ $selectedDay ? 'Daily transfers' : 'Transfers in month' }}</span><strong>{{ $transferStats['total'] }}</strong></div>
        <div class="board-stat"><span>{{ $selectedDay ? 'Daily PAX' : 'Monthly PAX' }}</span><strong>{{ number_format($transferStats['pax']) }}</strong></div>
        <div class="board-stat"><span>Unassigned transfers</span><strong>{{ $transferStats['unassigned'] }}</strong></div>
        <div class="board-stat"><span>Completed</span><strong>{{ $transferStats['completed'] }}</strong></div>
        <div class="board-stat"><span>Delayed</span><strong>{{ $transferStats['delayed'] }}</strong></div>
    </div>

    @if(session('success') && !session('import_report'))<div class="board-alert board-alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="board-alert board-alert-error">{{ session('error') }}</div>@endif

    <form method="GET" action="{{ route('planner.transfers', [$year, $monthSlug]) }}" class="transfer-board-toolbar">
        <div class="board-search"><span>⌕</span><input id="transferSearch" name="q" value="{{ request('q') }}" placeholder="Search all transfer fields…" aria-label="Search transfers"></div>
        @if($selectedDay)<input type="hidden" name="day" value="{{ $selectedDay->toDateString() }}">@endif
        <details class="transfer-filter-dropdown">
            <summary>Filters <span class="transfer-filter-count">{{ $transferFilterCount }}</span></summary>
            <div class="transfer-filter-menu">
                <div class="transfer-filter-grid">
                    <label>Status<select name="status" aria-label="Filter transfers by status" onchange="this.form.submit()"><option value="">Any status</option>@foreach($transferStatusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
                </div>
                <div class="transfer-filter-footer"><a class="button button-ghost" href="{{ route('planner.transfers', [$year, $monthSlug]) }}">Reset filters</a></div>
            </div>
        </details>
        <button class="button button-primary" type="submit">Apply</button>
        <a class="button button-ghost" href="{{ route('planner.transfers', [$year, $monthSlug]) }}">Reset</a>
        <details class="board-columns">
            <summary>Columns</summary>
            <div class="board-columns-menu transfer-column-menu">
                @foreach(['Contact Number', 'Supplier', 'Vehicle', 'Start Location', 'End Location', 'Notes', 'Status'] as $optionalIndex => $optionalColumn)
                    <label><input type="checkbox" data-transfer-column="{{ $optionalIndex + 4 }}" checked> {{ $optionalColumn }}</label>
                @endforeach
            </div>
        </details>
    </form>

    <div class="board-table-meta"><span>{{ $transfers->total() }} visible transfer records</span><span>Sorted by pickup time</span></div>

    <div class="transfer-table-wrap">
        <table class="transfer-planning-table">
            <thead>
                <tr>
                    @foreach($sortFields as $sortKey => $label)
                        @php $columnIndex = array_search($sortKey, array_keys($sortFields), true); @endphp
                        <th data-column="{{ $columnIndex }}" data-resizable class="{{ $columnIndex < 3 ? 'transfer-sticky transfer-'.($columnIndex === 0 ? 'date' : ($columnIndex === 1 ? 'time' : 'group')) : '' }}">
                            <a href="{{ $queryFor(['sort' => $sortKey, 'direction' => $sortDirection]) }}">{{ $label }} {{ request('sort') === $sortKey ? (request('direction') === 'desc' ? '↓' : '↑') : '' }}</a><span class="transfer-resizer" aria-hidden="true"></span>
                        </th>
                    @endforeach
                    @if($admin)<th class="transfer-actions">Actions</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                    @php
                        $assignment = $transfer->assignments->first();
                        $payload = [
                            'update_url' => route('planner.transfers.update', $transfer),
                            'group_name' => $transfer->booking?->group_name,
                            'pax' => $transfer->booking?->pax,
                            'arrival_date' => $transfer->starts_at?->toDateString(),
                            'pickup_time' => $transfer->starts_at?->format('H:i'),
                            'departure_date' => $transfer->booking?->departure_date?->toDateString() ?: $transfer->starts_at?->toDateString(),
                            'pickup_location' => $transfer->startLocation?->name,
                            'dropoff_location' => $transfer->endLocation?->name,
                            'flight_number' => $transfer->flight_number,
                            'contact_number' => $transfer->contact_number,
                            'supplier_id' => $transfer->supplier_id,
                            'guide_id' => $assignment?->guide_id,
                            'vehicle_id' => $assignment?->vehicle_id,
                            'status' => $transfer->status,
                            'notes' => $transfer->notes,
                        ];
                    @endphp
                    <tr tabindex="0" data-transfer-row>
                        <td data-column="0" class="transfer-sticky transfer-date">{{ $transfer->starts_at?->format('d/M') }}</td>
                        <td data-column="1" class="transfer-sticky transfer-time">{{ $transfer->starts_at?->format('H:i') }}</td>
                        <td data-column="2" class="transfer-sticky transfer-group" title="{{ $transfer->booking?->group_name }}">{{ $transfer->booking?->group_name ?: 'Unlinked booking' }}</td>
                        <td data-column="3" title="{{ $assignment?->guide?->name ?: 'Unassigned' }}">{{ $assignment?->guide?->name ?: 'Unassigned' }}</td>
                        <td data-column="4">{{ $transfer->contact_number ?: $transfer->booking?->company?->contact_phone ?: '—' }}</td>
                        <td data-column="5">{{ $transfer->supplier?->name ?: '—' }}</td>
                        <td data-column="6">{{ $assignment?->vehicle?->registration_number ?: '—' }}</td>
                        <td data-column="7">{{ $transfer->startLocation?->name ?: '—' }}</td>
                        <td data-column="8">{{ $transfer->endLocation?->name ?: '—' }}</td>
                        <td data-column="9" class="transfer-notes" title="{{ trim(implode(' · ', array_filter([$transfer->flight_number, $transfer->notes]))) }}">{{ trim(implode(' · ', array_filter([$transfer->flight_number, $transfer->notes]))) ?: '—' }}</td>
                        <td data-column="10"><span class="transfer-status transfer-status-{{ $transfer->status }}">{{ $transferStatusOptions[$transfer->status] ?? ucfirst(str_replace('_', ' ', $transfer->status)) }}</span></td>
                        @if($admin)
                            <td class="transfer-actions">
                                <div class="transfer-actions-wrap">
                                    <button type="button" class="icon-button" title="Edit transfer" aria-label="Edit transfer" data-transfer-edit data-transfer-payload='{{ json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) }}'>✎</button>
                                    <form method="POST" action="{{ route('planner.transfers.duplicate', $transfer) }}">@csrf<button class="icon-button" type="submit" title="Duplicate transfer" aria-label="Duplicate transfer">⧉</button></form>
                                    @if(! in_array($transfer->status, ['cancelled', 'archived', 'completed'], true))<form method="POST" action="{{ route('planner.transfers.cancel', $transfer) }}" data-confirm-form data-confirm-title="Cancel transfer" data-confirm-message="This transfer will be marked as cancelled.">@csrf<button class="icon-button danger" type="submit" title="Cancel transfer" aria-label="Cancel transfer">×</button></form>@endif
                                    @if($transfer->status !== 'archived')<form method="POST" action="{{ route('planner.transfers.archive', $transfer) }}" data-confirm-form data-confirm-title="Archive transfer" data-confirm-message="This transfer will be moved out of active planning.">@csrf<button class="icon-button action-icon action-icon-archive" type="submit" title="Archive transfer" aria-label="Archive transfer"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 4.5h11l-.8 8h-8.4l-.8-8ZM3.5 3h9l1 1.5h-11L3.5 3ZM6 7.5h4"/></svg></button></form>@endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $admin ? 12 : 11 }}" class="transfer-empty">No transfers match this planning view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="transfer-pagination"><span>Showing {{ $transfers->firstItem() ?: 0 }}–{{ $transfers->lastItem() ?: 0 }} of {{ $transfers->total() }}</span>{{ $transfers->onEachSide(1)->links() }}</div>
</section>

@if($admin)
    @include('planner.modals.create-transfer')
    @include('planner.modals.edit-transfer')
    <div class="modal-backdrop" id="transferImportModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('transferImportModal')">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="transferImportTitle">
            <div class="modal-head"><div><span class="eyebrow">Transfer board</span><h2 id="transferImportTitle">Import transfers</h2></div><button type="button" class="icon-button" onclick="closeModal('transferImportModal')" aria-label="Close import">&times;</button></div>
            <form method="POST" action="{{ route('planner.transfers.import', [$year, $monthSlug]) }}" enctype="multipart/form-data" class="modal-body">
                @csrf
                <p class="modal-intro">Upload an Excel workbook (.xlsx) or CSV (.csv). Required columns: Date, Pickup Time, Group / Guest Name, Start Location and End Location. Imports are atomic and duplicate transfers are skipped safely.</p>
                <label>Excel or CSV file<input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required></label>
                <p class="modal-intro"><a href="{{ route('planner.transfers.import-template', [$year, $monthSlug]) }}">Download sample Excel sheet</a></p>
                <div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('transferImportModal')">Cancel</button><button class="button button-primary" type="submit">Import rows</button></div>
            </form>
        </div>
    </div>
    @include('planner.modals.confirm-action')
@endif

<script>
    (() => {
        const board = document.querySelector('[data-transfer-board]');
        if (!board) return;
        const confirmForm = document.getElementById('confirmActionForm');
        let pendingConfirmForm = null;
        document.querySelectorAll('[data-confirm-form]').forEach(sourceForm => sourceForm.addEventListener('submit', event => {
            event.preventDefault();
            if (!confirmForm) return;
            pendingConfirmForm = sourceForm;
            confirmForm.querySelector('[data-confirm-title]').textContent = sourceForm.dataset.confirmTitle || 'Confirm action';
            confirmForm.querySelector('[data-confirm-message]').textContent = sourceForm.dataset.confirmMessage || 'This action will update the transfer.';
            confirmForm.querySelector('[data-confirm-submit]').textContent = sourceForm.dataset.confirmMethod === 'DELETE' ? 'Delete row' : 'Continue';
            openModal('confirmActionModal');
        }));
        confirmForm?.addEventListener('submit', event => {
            event.preventDefault();
            const sourceForm = pendingConfirmForm;
            pendingConfirmForm = null;
            closeModal('confirmActionModal');
            if (sourceForm) HTMLFormElement.prototype.submit.call(sourceForm);
        });
        document.querySelector('[data-transfer-fullscreen]')?.addEventListener('click', () => document.body.classList.toggle('transfer-board-fullscreen'));
        document.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); document.getElementById('transferSearch')?.focus(); }
            if (event.key === 'Escape') document.body.classList.remove('transfer-board-fullscreen');
        });
        document.querySelectorAll('[data-transfer-column]').forEach(toggle => {
            const key = 'transfer-board-column-' + toggle.dataset.transferColumn;
            toggle.checked = localStorage.getItem(key) !== 'hidden';
            const apply = () => {
                const hidden = !toggle.checked;
                localStorage.setItem(key, hidden ? 'hidden' : 'visible');
                document.querySelectorAll('[data-column="' + toggle.dataset.transferColumn + '"]').forEach(cell => cell.hidden = hidden);
            };
            toggle.addEventListener('change', apply); apply();
        });
        document.querySelectorAll('[data-transfer-edit]').forEach(button => {
            button.addEventListener('click', () => {
                const payload = JSON.parse(button.dataset.transferPayload);
                const form = document.getElementById('editTransferForm');
                form.action = payload.update_url;
                Object.entries(payload).forEach(([key, value]) => {
                    const field = form.querySelector('[data-transfer-field="' + key + '"]');
                    if (field) field.value = value ?? '';
                });
                openModal('editTransferModal');
            });
        });
        document.querySelectorAll('.transfer-planning-table th[data-resizable]').forEach(header => {
            header.querySelector('.transfer-resizer')?.addEventListener('mousedown', event => {
                event.preventDefault();
                const startX = event.clientX, startWidth = header.getBoundingClientRect().width, column = header.dataset.column;
                const move = moveEvent => {
                    const width = Math.max(70, startWidth + moveEvent.clientX - startX);
                    document.querySelectorAll('[data-column="' + column + '"]').forEach(cell => cell.style.width = width + 'px');
                };
                const stop = () => { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', stop); };
                document.addEventListener('mousemove', move); document.addEventListener('mouseup', stop);
            });
        });
        document.querySelectorAll('[data-transfer-row]').forEach(row => row.addEventListener('keydown', event => {
            if (event.key === 'Enter') row.querySelector('[data-transfer-edit]')?.click();
        }));
        if (new URLSearchParams(window.location.search).get('new') === '1') {
            window.setTimeout(() => window.openModal?.('transferModal'), 0);
        }
    })();
</script>
@endsection
