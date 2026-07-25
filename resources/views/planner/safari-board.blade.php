@extends('layouts.app')

@section('title', 'Safari Board | Safari Planner')
@section('page_title', 'Safari Board')
@section('body_class', 'safari-board-page')

@php
    $statusOptions = ['draft' => 'Draft', 'confirmed' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed', 'archived' => 'Archived', 'cancelled' => 'Cancelled'];
    $paymentOptions = ['pending' => 'Pending', 'deposit_paid' => 'Deposit paid', 'part_paid' => 'Part paid', 'paid' => 'Paid', 'overdue' => 'Overdue'];
    $visibilityOptions = ['workspace' => 'Workspace', 'assigned' => 'Assigned user', 'private' => 'Private'];
    $guideOptions = $guides->map(fn ($item) => ['id' => $item->id, 'label' => $item->name])->values();
    $vehicleOptions = $vehicles->map(fn ($item) => ['id' => $item->id, 'label' => $item->registration_number])->values();
    $translatorOptions = $translators->map(fn ($item) => ['id' => $item->id, 'label' => $item->name])->values();
    $companyOptions = $companies->map(fn ($item) => ['id' => $item->id, 'label' => $item->name])->values();
    $userOptions = $users->map(fn ($item) => ['id' => $item->id, 'label' => $item->name ?: $item->email])->values();
    $languageOptions = collect([
        ['id' => 'Dansk', 'label' => '🇩🇰 Dansk'],
        ['id' => 'Deutsch', 'label' => '🇩🇪 Deutsch'],
        ['id' => 'English', 'label' => '🇬🇧 English'],
        ['id' => 'Español', 'label' => '🇪🇸 Español'],
        ['id' => 'Français', 'label' => '🇫🇷 Français'],
        ['id' => 'Nederlands', 'label' => '🇳🇱 Nederlands'],
        ['id' => 'Norsk', 'label' => '🇳🇴 Norsk'],
        ['id' => 'Svenska', 'label' => '🇸🇪 Svenska'],
    ]);
    $monthSlug = strtolower($monthName);
    $previous = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();
@endphp

@section('content')
<div class="page-header safari-board-header">
    <div>
        <div class="eyebrow">Operations / Annual planning</div>
        <h1>Safari board</h1>
        <p class="lede">A live, spreadsheet-style plan for every group, guide, vehicle and TL assignment.</p>
    </div>
    <div class="page-action safari-board-actions">
        @if($admin)
            <button class="button button-primary" type="button" onclick="openModal('planningRowModal')">＋ Add planning row</button>
            <button class="button button-secondary" type="button" onclick="openModal('copyMonthModal')">Copy month</button>
            @if($user->canImport())
                <button class="button button-secondary" type="button" onclick="openModal('importBoardModal')">Import CSV / Excel</button>
            @endif
        @else
            <span class="board-readonly">Read-only operational view</span>
        @endif
        <button class="button button-ghost" type="button" data-board-fullscreen>Full screen</button>
        <button class="button button-ghost" type="button" onclick="window.print()">Print</button>
    </div>
</div>

<section class="safari-board-shell" data-board>
    <div class="safari-board-period">
        <div>
            <div class="board-period-label">Planning period</div>
            <div class="board-period-title">{{ $monthName }} {{ $year }}</div>
        </div>
        <div class="board-period-controls">
            <a class="button button-ghost" href="{{ route('safari-board.index', [$previous->year, strtolower($previous->format('F'))]) }}" aria-label="Previous month">←</a>
            <a class="button button-secondary" href="{{ route('safari-board.index', [now()->year, strtolower(now()->format('F'))]) }}">Current month</a>
            <a class="button button-ghost" href="{{ route('safari-board.index', [$next->year, strtolower($next->format('F'))]) }}" aria-label="Next month">→</a>
            <label class="board-year-picker">Year
                <select onchange="window.location=this.value">
                    @foreach($years as $availableYear)
                        <option value="{{ route('safari-board.index', [$availableYear, $monthSlug]) }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    <nav class="safari-month-tabs" aria-label="Planning months">
        @foreach($months as $number => $label)
            <a class="{{ $number === $month ? 'is-active' : '' }}" href="{{ route('safari-board.index', [$year, strtolower($label)]) }}">
                <span>{{ substr($label, 0, 3) }}</span>
                <strong>{{ $monthCounts[$number] ?? 0 }}</strong>
            </a>
        @endforeach
    </nav>

    <div class="board-summary-grid">
        <div class="board-stat"><span>Rows in month</span><strong>{{ $records->count() }}</strong></div>
        <div class="board-stat"><span>Active / confirmed</span><strong>{{ $records->whereIn('status', ['confirmed', 'in_progress'])->count() }}</strong></div>
        <div class="board-stat"><span>Need assignment</span><strong>{{ $records->filter(fn ($record) => ! $record->guide_id || ! $record->vehicle_id)->count() }}</strong></div>
        <div class="board-stat"><span>Guests travelling</span><strong>{{ number_format($records->sum('pax')) }}</strong></div>
    </div>

    @if(session('success') && !session('import_report'))
        <div class="board-alert board-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="board-alert board-alert-error">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('safari-board.index', [$year, $monthSlug]) }}" class="board-toolbar">
        <div class="board-search">
            <span>⌕</span>
            <input name="q" value="{{ request('q') }}" placeholder="Search group, guide, vehicle, company, phone or notes" aria-label="Search planning rows">
        </div>
        <details class="board-filter-dropdown">
            <summary>Filters <span class="board-filter-count">{{ request()->filled('status') ? 1 : 0 }}</span></summary>
            <div class="board-filter-menu">
                <label>Status <select name="status" aria-label="Filter Safari Board by status" onchange="this.form.submit()"><option value="">Any status</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
                <div class="board-filter-footer"><button class="button button-ghost" type="button" onclick="this.form.querySelector('[name=status]').value=''; this.form.submit();">Reset filters</button></div>
            </div>
        </details>
        <!--<select name="view_id" class="board-view-select" aria-label="Saved filter views">
            <option value="">Saved views</option>
            @foreach($savedViews as $view)
                <option value="{{ $view->id }}" @selected((string) request('view_id') === (string) $view->id)>{{ $view->name }}</option>
            @endforeach
        </select> -->
        <button class="button button-primary" type="submit">Apply</button>
        <button class="button button-ghost" type="button" onclick="openModal('saveFilterViewModal')">Save view</button>
        <details class="board-columns">
            <summary>Columns</summary>
            <div class="board-columns-menu">
                @foreach(['P/START', 'Notes', 'Group Name', 'PAX', 'Arrival Date', 'Departure Date', 'Guide Name', 'Guide Phone', 'Vehicle Number', 'Language', 'Time / Flight', 'Additional Time / Flight', 'TL / Translator', 'Company', 'Assignment Notes', 'Payment Status'] as $columnIndex => $columnLabel)
                    <label><input type="checkbox" data-column-toggle="{{ $columnIndex }}" checked> {{ $columnLabel }}</label>
                @endforeach
            </div>
        </details>
    </form>

    @if(count($activeFilters))
        <div class="board-filter-chips">
            @foreach($activeFilters as $filter)
                <a href="{{ request()->fullUrlWithQuery([$filter['key'] => null]) }}">{{ $filter['label'] }}: {{ $filter['value'] }} ×</a>
            @endforeach
        </div>
    @endif

    <div class="board-table-meta">
        <span>{{ $records->count() }} visible planning rows</span>
        @if($admin)<span class="board-save-state" data-board-save-state>Ready to edit</span>@endif
    </div>

    <div class="safari-table-wrap">
        <table class="safari-planning-table">
            <thead>
                <tr>
                    @foreach(['P/START', 'Notes', 'Group Name', 'PAX', 'Arrival Date', 'Departure Date', 'Guide Name', 'Guide Phone', 'Vehicle Number', 'Language', 'Time / Flight', 'Additional Time / Flight', 'TL / Translator', 'Company', 'Assignment Notes', 'Payment Status'] as $columnIndex => $columnLabel)
                        <th data-column="{{ $columnIndex }}" class="{{ $columnIndex < 3 ? 'safari-sticky-col' : '' }}">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => match($columnIndex) { 2 => 'group_name', 3 => 'pax', 4 => 'arrival_date', 5 => 'departure_date', 9 => 'language', 15 => 'payment_status', default => 'arrival_date' }, 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}">{{ $columnLabel }}</a>
                        </th>
                    @endforeach
                    @if($admin)<th class="safari-actions-col">Actions</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($records as $booking)
                    @php
                        $capacityWarning = $booking->vehicle && (int) $booking->pax > (int) $booking->vehicle->capacity;
                        $rowWarning = in_array($booking->id, $conflictIds, true) || $capacityWarning;
                    @endphp
                    <tr class="{{ $rowWarning ? 'is-warning' : '' }}" data-update-url="{{ route('safari-board.inline-update', $booking) }}" data-record-id="{{ $booking->id }}">
                        <td data-column="0" class="safari-sticky-col">
                            @if($admin)<span class="inline-cell" data-inline-field="start_instruction" data-inline-type="text" data-value="{{ $booking->start_instruction }}">{{ $booking->start_instruction ?: 'P/START' }}</span>@else{{ $booking->start_instruction ?: '—' }}@endif
                        </td>
                        <td data-column="1" class="safari-sticky-col safari-notes-cell" title="{{ trim(implode(' · ', array_filter([$booking->notes, $booking->operational_notes]))) }}">
                            @if($admin)<span class="inline-cell" data-inline-field="notes" data-inline-type="textarea" data-value="{{ $booking->notes }}">{{ $booking->notes ?: ($booking->operational_notes ?: 'Add note') }}</span>@else{{ $booking->notes ?: ($booking->operational_notes ?: '—') }}@endif
                        </td>
                        <td data-column="2" class="safari-sticky-col safari-group-cell">
                            @if($admin)<span class="inline-cell" data-inline-field="group_name" data-inline-type="text" data-value="{{ $booking->group_name }}">{{ $booking->group_name }}</span>@else{{ $booking->group_name }}@endif
                            <small>{{ $booking->reference }}</small>
                        </td>
                        <td data-column="3">@if($admin)<span class="inline-cell" data-inline-field="pax" data-inline-type="number" data-value="{{ $booking->pax }}">{{ $booking->pax }}</span>@else{{ $booking->pax }}@endif</td>
                        <td data-column="4">@if($admin)<span class="inline-cell" data-inline-field="arrival_date" data-inline-type="date" data-value="{{ optional($booking->arrival_date)->format('Y-m-d') }}">{{ optional($booking->arrival_date)->format('d/m/Y') ?: '—' }}</span>@else{{ optional($booking->arrival_date)->format('d/m/Y') ?: '—' }}@endif</td>
                        <td data-column="5">@if($admin)<span class="inline-cell" data-inline-field="departure_date" data-inline-type="date" data-value="{{ optional($booking->departure_date)->format('Y-m-d') }}">{{ optional($booking->departure_date)->format('d/m/Y') ?: '—' }}</span>@else{{ optional($booking->departure_date)->format('d/m/Y') ?: '—' }}@endif</td>
                        <td data-column="6">@if($admin)<span class="inline-cell" data-inline-field="guide_id" data-inline-type="select" data-value="{{ $booking->guide_id }}" data-options='@json($guideOptions)'>{{ $booking->guide?->name ?: 'Unassigned' }}</span>@else{{ $booking->guide?->name ?: 'Unassigned' }}@endif</td>
                        <td data-column="7">{{ $booking->guide_phone ?: '—' }}</td>
                        <td data-column="8">@if($admin)<span class="inline-cell" data-inline-field="vehicle_id" data-inline-type="select" data-value="{{ $booking->vehicle_id }}" data-options='@json($vehicleOptions)'>{{ $booking->vehicle?->registration_number ?: 'Unassigned' }}</span>@else{{ $booking->vehicle?->registration_number ?: 'Unassigned' }}@endif @if($capacityWarning)<span class="board-warning-icon" title="PAX exceeds vehicle capacity">!</span>@endif</td>
                        <td data-column="9">@if($admin)<span class="inline-cell" data-inline-field="language" data-inline-type="text" data-value="{{ $booking->language }}">{{ $booking->language ?: '—' }}</span>@else{{ $booking->language ?: '—' }}@endif</td>
                        <td data-column="10">@if($admin)<span class="inline-cell" data-inline-field="primary_flight_time" data-inline-type="text" data-value="{{ $booking->primary_flight_time }}">{{ $booking->primary_flight_time ?: '—' }}</span>@else{{ $booking->primary_flight_time ?: '—' }}@endif</td>
                        <td data-column="11">@if($admin)<span class="inline-cell" data-inline-field="secondary_flight_time" data-inline-type="text" data-value="{{ $booking->secondary_flight_time }}">{{ $booking->secondary_flight_time ?: '—' }}</span>@else{{ $booking->secondary_flight_time ?: '—' }}@endif</td>
                        <td data-column="12">@if($admin)<span class="inline-cell" data-inline-field="translator_id" data-inline-type="select" data-value="{{ $booking->translator_id }}" data-options='@json($translatorOptions)'>{{ $booking->translator?->name ?: '—' }}</span>@else{{ $booking->translator?->name ?: '—' }}@endif</td>
                        <td data-column="13">@if($admin)<span class="inline-cell" data-inline-field="company_id" data-inline-type="select" data-value="{{ $booking->company_id }}" data-options='@json($companyOptions)'>{{ $booking->company?->name ?: '—' }}</span>@else{{ $booking->company?->name ?: '—' }}@endif</td>
                        <td data-column="14">@if($admin)<span class="inline-cell" data-inline-field="assignment_notes" data-inline-type="text" data-value="{{ $booking->assignment_notes }}">{{ $booking->assignment_notes ?: '—' }}</span>@else{{ $booking->assignment_notes ?: '—' }}@endif</td>
                        <td data-column="15">@if($admin)<span class="inline-cell" data-inline-field="payment_status" data-inline-type="select" data-value="{{ $booking->payment_status }}" data-options='@json(collect($paymentOptions)->map(fn ($label, $id) => ["id" => $id, "label" => $label])->values())'>{{ $paymentOptions[$booking->payment_status] ?? $booking->payment_status }}</span>@else{{ $paymentOptions[$booking->payment_status] ?? $booking->payment_status }}@endif</td>
                        @if($admin)
                            <td class="safari-actions-col">
                                <button class="icon-button" type="button" title="Duplicate row" data-duplicate-id="{{ $booking->id }}">⧉</button>
                                <form method="POST" action="{{ route('safari-board.destroy', $booking) }}" data-confirm-form data-confirm-title="Delete planning row" data-confirm-message="This planning row will be permanently removed." data-confirm-method="DELETE">@csrf @method('DELETE')<button class="icon-button danger action-icon action-icon-delete" type="submit" title="Delete row" aria-label="Delete row"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 5.5h9M6 5.5V4h4v1.5m-5.5 0 .5 7h6l.5-7M7 7.5v3.5m2-3.5v3.5"/></svg></button></form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $admin ? 17 : 16 }}" class="board-empty">No planning rows match this month and filter set. @if($admin)<button type="button" class="text-button" onclick="openModal('planningRowModal')">Add the first row</button>@endif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="board-footer">
        <span>Beige highlights indicate editable planning data. Success confirmations remain light green.</span>
        @if($admin)
            <form method="POST" action="{{ route('safari-board.archive-year', $year) }}" data-confirm-form data-confirm-title="Archive completed rows" data-confirm-message="Every completed planning row in {{ $year }} will be archived." >@csrf<button class="text-button" type="submit">Archive completed {{ $year }} rows</button></form>
        @endif
    </div>
</section>

@if($admin)
<div class="modal-backdrop" id="planningRowModal" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="planningRowTitle">
        <div class="modal-head"><div><span class="eyebrow">Safari board</span><h2 id="planningRowTitle">Add planning row</h2></div><button type="button" class="modal-close" onclick="closeModal('planningRowModal')">×</button></div>
        <form method="POST" action="{{ route('safari-board.store', [$year, $monthSlug]) }}">
            @csrf
            <div class="modal-body">
                <div class="modal-intro">Capture the operational truth from the planning sheet. Drafts can be completed inline later.</div>
                <div class="modal-form-grid">
                    <label>Plan year *<input type="number" name="plan_year" value="{{ old('plan_year', $year) }}" min="2020" max="2100" required></label>
                    <label>Plan month *<select name="plan_month">@foreach($months as $number => $label)<option value="{{ $number }}" @selected((int) old('plan_month', $month) === $number)>{{ $label }}</option>@endforeach</select></label>
                    <label>P/START<select name="start_instruction"><option value="">Choose instruction</option>@foreach(['Pick & Start', 'P/START', 'Pick & start', 'Airport pickup', 'Hotel pickup'] as $instruction)<option @selected(old('start_instruction') === $instruction)>{{ $instruction }}</option>@endforeach</select></label>
                    <label>Group name *<input name="group_name" value="{{ old('group_name') }}" required></label>
                    <label>Reference<input name="reference" value="{{ old('reference') }}" placeholder="Auto-generated if blank"></label>
                    <label>PAX *<input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="999" required></label>
                    <label>Arrival date *<input type="date" name="arrival_date" value="{{ old('arrival_date') }}"></label>
                    <label>Departure date<input type="date" name="departure_date" value="{{ old('departure_date') }}"></label>
                    <label>Guide<select name="guide_id"><option value="">Unassigned</option>@foreach($guides as $guide)<option value="{{ $guide->id }}" @selected((string) old('guide_id') === (string) $guide->id)>{{ $guide->name }}{{ $guide->phone ? ' · '.$guide->phone : '' }}</option>@endforeach</select></label>
                    <label>Guide phone<input name="guide_phone" value="{{ old('guide_phone') }}" placeholder="Filled from guide"></label>
                    <label>Vehicle<select name="vehicle_id"><option value="">Unassigned</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->registration_number }} · {{ $vehicle->capacity }} seats</option>@endforeach</select></label>
                    <label>Language<select name="language"><option value="">Choose language</option>@foreach($languageOptions as $language)<option value="{{ $language['id'] }}" @selected(old('language') === $language['id'])>{{ $language['label'] }}</option>@endforeach</select></label>
                    <label>Time / flight<input name="primary_flight_time" value="{{ old('primary_flight_time') }}"></label>
                    <label>Additional time / flight<input name="secondary_flight_time" value="{{ old('secondary_flight_time') }}"></label>
                    <label>TL / Translator<select name="translator_id"><option value="">None</option>@foreach($translators as $translator)<option value="{{ $translator->id }}" @selected((string) old('translator_id') === (string) $translator->id)>{{ $translator->name }}</option>@endforeach</select></label>
                    <label>Company<select name="company_id"><option value="">None</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>@endforeach</select></label>
                    <label class="modal-field-wide">Assignment notes<input name="assignment_notes" value="{{ old('assignment_notes') }}" placeholder="Signed for…, pickup timing, handover notes"></label>
                    <label>Payment status<select name="payment_status">@foreach($paymentOptions as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', 'pending') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Record status<select name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Visibility<select name="visibility">@foreach($visibilityOptions as $value => $label)<option value="{{ $value }}" @selected(old('visibility', 'workspace') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Visible to user<select name="visible_to_user_id"><option value="">Workspace</option>@foreach($users as $listedUser)<option value="{{ $listedUser->id }}">{{ $listedUser->name ?: $listedUser->email }}</option>@endforeach</select></label>
                    <label class="modal-field-wide">Operational notes<textarea name="operational_notes" rows="3" placeholder="Internal operational notes">{{ old('operational_notes') }}</textarea></label>
                </div>
            </div>
            <div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('planningRowModal')">Cancel</button><button class="button button-secondary" name="submit_action" value="draft">Save draft</button><button class="button button-secondary" name="submit_action" value="add_another">Save & add another</button><button class="button button-primary" name="submit_action" value="assign">Save and assign</button></div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="copyMonthModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="copyMonthTitle">
        <div class="modal-head"><div><span class="eyebrow">Annual planning</span><h2 id="copyMonthTitle">Copy {{ $monthName }}</h2></div><button type="button" class="modal-close" onclick="closeModal('copyMonthModal')">×</button></div>
        <form method="POST" action="{{ route('safari-board.copy-month', [$year, $monthSlug]) }}"><div class="modal-body"><div class="modal-intro">Duplicate the current rows into another month. Dates shift with the destination month and copies start as drafts.</div><div class="modal-form-grid"><label>Target year<input type="number" name="target_year" value="{{ $year }}" min="2020" max="2100" required></label><label>Target month<select name="target_month">@foreach($months as $number => $label)<option value="{{ $number }}" @selected($number === $month)>{{ $label }}</option>@endforeach</select></label></div></div><div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('copyMonthModal')">Cancel</button><button class="button button-primary">Copy planning rows</button></div>@csrf</form>
    </div>
</div>

<div class="modal-backdrop" id="importBoardModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="importBoardTitle">
        <div class="modal-head"><div><span class="eyebrow">Safari board</span><h2 id="importBoardTitle">Import planning rows</h2></div><button type="button" class="modal-close" onclick="closeModal('importBoardModal')">×</button></div>
        <form method="POST" action="{{ route('safari-board.import', [$year, $monthSlug]) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="modal-intro">Upload an Excel workbook (.xlsx) or CSV (.csv). Imports are atomic: valid rows are inserted only when every row passes validation. Existing references are skipped safely as duplicates.</div>
                <label>Excel or CSV file *<input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required></label>
                <p class="board-filter-hint"><a href="{{ route('safari-board.import-template', [$year, $monthSlug]) }}">Download sample Excel sheet</a></p>
                <p class="board-filter-hint">Required columns: Group Name and PAX. Supported columns include arrival/departure dates, guide, vehicle, language, flights, translator, company, payment status, notes, status and visibility.</p>
            </div>
            <div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('importBoardModal')">Cancel</button><button class="button button-primary">Import planning rows</button></div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="duplicateModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="duplicateTitle">
        <div class="modal-head"><div><span class="eyebrow">Safari board</span><h2 id="duplicateTitle">Duplicate planning row</h2></div><button type="button" class="modal-close" onclick="closeModal('duplicateModal')">×</button></div>
        <form method="POST" data-duplicate-form-template="{{ url('/safari-board/records/__BOOKING__/duplicate') }}"><div class="modal-body"><div class="modal-intro">Choose the month for the new draft row.</div><div class="modal-form-grid"><label>Target year<input type="number" name="plan_year" value="{{ $year }}" min="2020" max="2100" required></label><label>Target month<select name="plan_month">@foreach($months as $number => $label)<option value="{{ $number }}" @selected($number === $month)>{{ $label }}</option>@endforeach</select></label></div></div><div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('duplicateModal')">Cancel</button><button class="button button-primary">Duplicate as draft</button></div>@csrf</form>
    </div>
</div>

<div class="modal-backdrop" id="saveFilterViewModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="saveViewTitle">
        <div class="modal-head"><div><span class="eyebrow">Saved filters</span><h2 id="saveViewTitle">Save filter view</h2></div><button type="button" class="modal-close" onclick="closeModal('saveFilterViewModal')">×</button></div>
        <form method="POST" action="{{ route('safari-board.views.store') }}"><div class="modal-body"><label>View name<input name="name" maxlength="80" placeholder="Unassigned groups" required></label><input type="hidden" name="filters" value="{{ e(json_encode($filterPayload)) }}"></div><div class="modal-foot"><button type="button" class="button button-ghost" onclick="closeModal('saveFilterViewModal')">Cancel</button><button class="button button-primary">Save view</button></div>@csrf</form>
    </div>
</div>
@include('planner.modals.confirm-action')
@endif
@endsection

@push('styles')
<style>
    .safari-board-shell { background: var(--surface, #fff); border: 1px solid var(--line, #e6ded1); border-radius: 16px; overflow: visible; }
    .safari-board-shell ~ .modal-backdrop { display:none; }
    .safari-board-period { display:flex; justify-content:space-between; gap:16px; align-items:center; padding:14px 16px 10px; background:linear-gradient(135deg,#f8f1e7,#fff); border-radius:16px 16px 0 0; }
    .board-period-label,.board-filter-hint,.board-table-meta,.board-footer,.board-readonly { color:#7b746b; font-size:12px; }
    .board-period-title { font:800 24px/1 'Plus Jakarta Sans',sans-serif; color:#3e2e22; margin-top:6px; }
    .board-period-controls,.safari-board-actions { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .safari-board-actions .button { position:relative; width:32px; min-width:32px; height:32px; padding:0; font-size:0; }
    .safari-board-actions .button::before { display:grid; width:100%; height:100%; place-items:center; font-size:16px; line-height:1; }
    .safari-board-actions .button::after { content:attr(title); position:absolute; right:0; top:calc(100% + 7px); z-index:8; padding:6px 8px; border-radius:7px; background:#4e3c2e; color:#fffaf3; font-size:10px; line-height:1; white-space:nowrap; opacity:0; pointer-events:none; transform:translateY(-3px); transition:opacity .16s ease, transform .16s ease; }
    .safari-board-actions .button:hover::after, .safari-board-actions .button:focus-visible::after { opacity:1; transform:translateY(0); }
    .safari-board-actions button[onclick*="planningRowModal"]::before { content:'+'; }
    .safari-board-actions button[onclick*="copyMonthModal"]::before { content:'⧉'; }
    .safari-board-actions button[onclick*="importBoardModal"]::before { content:'↥'; }
    .safari-board-actions button[data-board-fullscreen]::before { content:'↗'; }
    .safari-board-actions button[onclick*="print"]::before { content:'▣'; }
    .board-year-picker { display:flex; align-items:center; gap:7px; font-size:12px; color:#7b746b; }
    .board-year-picker select,.board-view-select { min-height:38px; border:1px solid #d9cdbd; border-radius:10px; padding:0 10px; background:#fff; color:#3e2e22; }
    .safari-month-tabs { display:grid; grid-template-columns:repeat(12,minmax(74px,1fr)); gap:5px; padding:8px 12px; border-block:1px solid #eee5d8; overflow:auto; }
    .safari-month-tabs a { text-decoration:none; color:#796f64; display:flex; justify-content:center; align-items:center; gap:6px; padding:9px 6px; border-radius:10px; font-size:12px; white-space:nowrap; }
    .safari-month-tabs a:hover,.safari-month-tabs a.is-active { background:#e8dccb; color:#3e2e22; }
    .safari-month-tabs strong { background:#fff; border-radius:999px; padding:2px 6px; font-size:10px; }
    .board-summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; padding:12px 14px; }
    .board-stat { background:#fbf8f2; border:1px solid #eee5d8; border-radius:14px; padding:13px 15px; display:flex; flex-direction:column; gap:6px; }
    .board-stat span { font-size:11px; color:#847b70; } .board-stat strong { color:#3e2e22; font-size:22px; }
    .board-alert { margin:0 14px 10px; padding:9px 11px; border-radius:9px; font-size:11px; }
    .board-alert-success { background:#e8f5ea; color:#316a3d; border:1px solid #c9e8ce; } .board-alert-error { background:#fff0ec; color:#9b4030; border:1px solid #f2c7bd; }
    .board-toolbar { display:flex; gap:7px; align-items:center; padding:0 14px 10px; flex-wrap:wrap; }
    .board-search { flex:1 1 280px; display:flex; gap:8px; align-items:center; border:1px solid #d9cdbd; border-radius:11px; padding:0 12px; min-height:40px; background:#fff; color:#8a7b6c; }
    .board-search input { border:0; outline:0; width:100%; background:transparent; color:#3e2e22; }
    .board-filter-count { background:#e8dccb; color:#573f2d; border-radius:999px; padding:2px 6px; font-size:10px; }
    .board-filter-dropdown { position:relative; }
    .board-filter-dropdown summary { list-style:none; cursor:pointer; min-height:34px; padding:10px 12px; border:1px solid #d9cdbd; border-radius:10px; background:#fff; color:#594b3d; font-size:12px; }
    .board-filter-dropdown summary::-webkit-details-marker { display:none; }
    .board-filter-menu { position:absolute; top:calc(100% + 6px); right:0; z-index:20; width:230px; padding:10px; border:1px solid #e5d8c8; border-radius:10px; background:#fcfaf7; box-shadow:0 12px 28px #46301c24; }
    .board-filter-menu label { display:flex; flex-direction:column; gap:5px; color:#675a4d; font-size:10px; font-weight:700; }
    .board-filter-menu select { min-height:34px; height:34px; padding:0 9px; border:1px solid #d9cdbd; border-radius:9px; background:#fff; font-size:10px; }
    .board-filter-menu .board-filter-footer { justify-content:flex-end; margin-top:8px; }
    .board-columns { position:relative; } .board-columns summary { list-style:none; cursor:pointer; border:1px solid #d9cdbd; border-radius:10px; padding:10px 12px; font-size:12px; color:#594b3d; }
    .board-columns-menu { position:absolute; z-index:5; right:0; top:40px; width:185px; max-height:245px; overflow:auto; padding:8px 9px; background:#fff; border:1px solid #d9cdbd; border-radius:10px; box-shadow:0 10px 22px #46301c1c; }
    .board-columns-menu label,.board-filter-checks label { display:block; font-size:10px; line-height:1.2; padding:3px 0; color:#5c5044; }
    .board-filter-panel { margin:0 14px 10px; padding:12px; border:1px solid #e5d8c8; border-radius:12px; background:#fcfaf7; }
    .board-filter-grid { display:grid; grid-template-columns:repeat(5,minmax(130px,1fr)); gap:10px; } .board-filter-grid label,.modal-body label { display:flex; flex-direction:column; gap:5px; color:#675a4d; font-size:11px; font-weight:700; }
    .board-filter-grid input,.board-filter-grid select { min-height:36px; border:1px solid #d9cdbd; border-radius:9px; padding:0 9px; background:#fff; }
    .board-filter-simple .board-filter-grid { grid-template-columns:minmax(220px,300px); gap:8px; }
    .board-filter-simple .board-filter-grid label { font-size:10px; }
    .board-filter-simple .board-filter-grid select { min-height:34px; height:34px; font-size:10px; }
    .board-filter-simple .board-filter-footer { margin-top:9px; gap:7px; }
    .board-filter-simple .board-filter-hint { font-size:10px; }
    .board-filter-checks { display:flex; gap:14px; flex-wrap:wrap; margin-top:12px; } .board-filter-footer { display:flex; align-items:center; gap:12px; margin-top:13px; }
    .board-filter-footer .board-filter-hint { margin-right:auto; } .board-filter-chips { display:flex; flex-wrap:wrap; gap:5px; padding:0 14px 10px; }
    .board-filter-chips a { color:#5b4633; background:#efe4d4; border:1px solid #e2d2bd; border-radius:999px; padding:5px 9px; font-size:11px; text-decoration:none; }
    .board-table-meta { display:flex; justify-content:space-between; padding:0 14px 7px; } .board-save-state { color:#75624e; }
    .safari-table-wrap { overflow:auto; border-block:1px solid #e6ded1; max-height:68vh; overscroll-behavior:contain; scroll-behavior:smooth; scrollbar-width:thin; scrollbar-color:#b8a17f #f5eee4; }
    .safari-table-wrap::-webkit-scrollbar { width:10px; height:10px; }
    .safari-table-wrap::-webkit-scrollbar-track { background:#f5eee4; }
    .safari-table-wrap::-webkit-scrollbar-thumb { border:2px solid #f5eee4; border-radius:999px; background:#b8a17f; }
    .safari-planning-table { min-width:1540px; border-collapse:separate; border-spacing:0; width:100%; font-size:10px; color:#4c4137; }
    .safari-planning-table th { position:sticky; top:0; z-index:3; background:#eadcc9; color:#493424; text-align:left; white-space:nowrap; padding:7px 8px; border-right:1px solid #d5c3ac; border-bottom:2px solid #d0bda4; font-size:9px; }
    .safari-planning-table th a { color:inherit; text-decoration:none; } .safari-planning-table td { padding:5px 8px; border-right:1px solid #ebe3d8; border-bottom:1px solid #ebe3d8; vertical-align:top; min-width:72px; background:#fff; }
    .safari-planning-table tbody tr:hover td { background:#fffaf2; } .safari-planning-table tbody tr.is-warning td { background:#fff8e9; } .safari-planning-table tbody tr.is-warning:hover td { background:#fff2d5; }
    .safari-sticky-col { position:sticky; z-index:2; } th.safari-sticky-col { z-index:5; } th.safari-sticky-col:first-child,td.safari-sticky-col:first-child { left:0; min-width:120px; max-width:120px; } th.safari-sticky-col:nth-child(2),td.safari-sticky-col:nth-child(2) { left:120px; min-width:180px; max-width:180px; } th.safari-sticky-col:nth-child(3),td.safari-sticky-col:nth-child(3) { left:300px; min-width:190px; max-width:190px; box-shadow:8px 0 12px #4b32140d; }
    .board-status { display:inline-block; border-radius:999px; padding:4px 7px; background:#eee5d8; color:#5c4431; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; } .board-status-confirmed,.board-status-in_progress { background:#e8f5ea; color:#316a3d; }
    .safari-planning-table small { display:block; color:#8b7b6c; margin-top:6px; max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .safari-group-cell { font-weight:700; color:#3e2e22; } .safari-group-cell small { font-weight:400; } .inline-cell { display:inline-block; cursor:text; border-radius:5px; padding:2px 3px; max-width:210px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; } .inline-cell:hover { background:#efe4d4; outline:1px solid #d6b98f; }
    .inline-editor { width:100%; min-width:74px; border:1px solid #c7a877; border-radius:5px; padding:4px; background:#fffdf8; color:#3e2e22; font:inherit; } .board-warning-icon { display:inline-flex; width:17px; height:17px; align-items:center; justify-content:center; border-radius:50%; background:#e6b25c; color:#fff; font-weight:800; font-size:10px; }
    .safari-actions-col { min-width:60px; width:60px; } .safari-actions-col form { display:inline; } .icon-button { border:0; background:transparent; color:#6c543f; cursor:pointer; padding:3px; font-size:14px; } .icon-button.danger { color:#b05a48; } .action-icon { display:inline-grid; place-items:center; } .action-icon svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-linecap:round; stroke-linejoin:round; stroke-width:1.35; }
    .board-empty { text-align:center; padding:32px !important; color:#8b7b6c; } .text-button { border:0; background:transparent; color:#7b5a36; text-decoration:underline; cursor:pointer; font:inherit; }
    .board-footer { display:flex; justify-content:space-between; gap:15px; padding:10px 14px 13px; } body.safari-board-fullscreen .safari-board-shell { position:fixed; inset:12px; z-index:50; overflow:auto; box-shadow:0 24px 80px #3d281c44; } body.safari-board-fullscreen .safari-table-wrap { max-height:calc(100vh - 250px); }
    @media (max-width:1000px) { .safari-board-period { align-items:flex-start; flex-direction:column; } .board-summary-grid { grid-template-columns:repeat(2,1fr); } .board-filter-grid { grid-template-columns:repeat(2,1fr); } .safari-board-header { align-items:flex-start; flex-direction:column; } }
    @media (max-width:720px) {
        body.safari-board-page .content-wrap { padding:12px 8px 24px; }
        .safari-board-header { gap:12px; margin-bottom:14px; }
        .safari-board-header h1 { font-size:22px; }
        .safari-board-header .lede { max-width:34ch; line-height:1.45; }
        .safari-board-actions { width:100%; gap:5px; }
        .safari-board-actions .button { flex:0 0 34px !important; width:34px; min-width:34px; height:34px; }
        .safari-board-period { gap:10px; padding:12px 10px 9px; }
        .board-period-title { font-size:20px; }
        .board-period-controls { width:100%; }
        .board-period-controls .button { flex:1 1 auto; min-height:34px; }
        .board-year-picker { flex:1 1 100%; }
        .board-year-picker select { flex:1; min-width:0; }
        .safari-month-tabs { grid-template-columns:repeat(4,minmax(0,1fr)); gap:4px; padding:6px 8px; overflow:visible; }
        .safari-month-tabs a { min-width:0; padding:7px 2px; font-size:10px; }
        .board-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; padding:8px; }
        .board-stat { padding:10px; border-radius:11px; }
        .board-stat span { font-size:10px; }
        .board-stat strong { font-size:18px; }
        .board-toolbar { flex-direction:column; align-items:stretch; gap:6px; padding:0 8px 8px; }
        .board-search { flex:0 0 auto; width:100%; min-height:36px; height:36px; }
        .board-search input { height:32px; }
        .board-toolbar > .button, .board-toolbar > .board-view-select, .board-toolbar > .board-columns, .board-toolbar > .board-filter-dropdown { width:100%; }
        .board-toolbar > .button, .board-toolbar > .board-view-select { min-height:34px; height:34px; }
        .board-filter-dropdown summary { text-align:center; }
        .board-filter-menu { left:0; right:0; width:auto; }
        .board-toolbar > .board-view-select { display:none; }
        .board-toolbar > .board-columns summary { text-align:center; }
        .board-filter-panel { margin:0 8px 8px; padding:9px; }
        .board-filter-grid { grid-template-columns:1fr; gap:7px; }
        .board-filter-simple .board-filter-grid { grid-template-columns:1fr; }
        .board-filter-checks { gap:8px; }
        .board-filter-footer { flex-wrap:wrap; gap:7px; }
        .board-filter-footer .board-filter-hint { flex:0 0 100%; margin-right:0; }
        .board-table-meta { padding:0 8px 6px; font-size:10px; }
        .safari-table-wrap { width:100%; min-height:220px; max-height:calc(100vh - 300px); overflow-x:auto; overflow-y:auto; -webkit-overflow-scrolling:touch; overscroll-behavior:contain; scrollbar-gutter:stable; touch-action:pan-x pan-y; }
        .safari-planning-table { width:max-content; min-width:1300px; font-size:9px; }
        .safari-planning-table th, .safari-planning-table td { padding:4px 5px; }
        .safari-planning-table td.safari-sticky-col { position:static; left:auto !important; box-shadow:none; }
        .safari-planning-table th.safari-sticky-col { position:sticky; top:0; left:auto !important; z-index:5; box-shadow:none; }
        th.safari-sticky-col:first-child, td.safari-sticky-col:first-child { min-width:92px; max-width:92px; }
        th.safari-sticky-col:nth-child(2), td.safari-sticky-col:nth-child(2) { min-width:135px; max-width:135px; }
        th.safari-sticky-col:nth-child(3), td.safari-sticky-col:nth-child(3) { min-width:140px; max-width:140px; }
        .safari-planning-table small { margin-top:3px; }
        .board-columns-menu { position:fixed; left:8px; right:8px; top:auto; width:auto; max-height:50vh; padding:8px 10px; }
        .board-footer { flex-direction:column; align-items:flex-start; padding:9px 8px 11px; }
    }
    @media print { .sidebar,.topbar,.safari-board-header,.safari-board-period,.safari-month-tabs,.board-toolbar,.board-filter-panel,.board-filter-chips,.board-footer,.safari-actions-col { display:none !important; } .main-content { margin:0 !important; } .safari-board-shell { border:0; } .safari-table-wrap { max-height:none; overflow:visible; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const board = document.querySelector('[data-board]');
    if (!board) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const state = document.querySelector('[data-board-save-state]');
    const setState = (message, isError = false) => { if (state) { state.textContent = message; state.style.color = isError ? '#a34d3c' : ''; } };
    const confirmForm = document.getElementById('confirmActionForm');
    let pendingConfirmForm = null;
    document.querySelectorAll('[data-confirm-form]').forEach(sourceForm => sourceForm.addEventListener('submit', event => {
        event.preventDefault();
        if (!confirmForm) return;
        pendingConfirmForm = sourceForm;
        confirmForm.querySelector('[data-confirm-title]').textContent = sourceForm.dataset.confirmTitle || 'Confirm action';
        confirmForm.querySelector('[data-confirm-message]').textContent = sourceForm.dataset.confirmMessage || 'This action will update the planning record.';
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
    document.querySelector('[data-board-fullscreen]')?.addEventListener('click', () => document.body.classList.toggle('safari-board-fullscreen'));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') document.body.classList.remove('safari-board-fullscreen'); });

    document.querySelectorAll('[data-column-toggle]').forEach(toggle => {
        const key = 'safari-board-column-' + toggle.dataset.columnToggle;
        const saved = localStorage.getItem(key);
        if (saved === '0') toggle.checked = false;
        const update = () => {
            document.querySelectorAll('[data-column="' + toggle.dataset.columnToggle + '"]').forEach(cell => cell.style.display = toggle.checked ? '' : 'none');
            localStorage.setItem(key, toggle.checked ? '1' : '0');
        };
        toggle.addEventListener('change', update); update();
    });

    const languageOptions = @json($languageOptions);
    document.querySelectorAll('.inline-cell[data-inline-field="language"]').forEach(cell => {
        cell.dataset.inlineType = 'select';
        cell.dataset.options = JSON.stringify(languageOptions);
    });

    let undo = null;
    const undoButton = document.createElement('button');
    undoButton.className = 'text-button';
    undoButton.textContent = 'Undo last change';
    undoButton.hidden = true;
    state?.parentElement?.append(undoButton);
    const displayValue = (cell, value) => {
        if (cell.dataset.inlineType === 'date' && value) return new Date(value + 'T00:00:00').toLocaleDateString('en-GB');
        if (!value) return cell.dataset.inlineType === 'select' ? 'Unassigned' : '—';
        if (cell.dataset.inlineType === 'select') { try { return (JSON.parse(cell.dataset.options || '[]').find(item => String(item.id) === String(value)) || {}).label || value; } catch { return value; } }
        return value;
    };
    const saveCell = async (cell, editor, value, previousValue, previousText) => {
        const row = cell.closest('tr'); setState('Saving…');
        try {
            const response = await fetch(row.dataset.updateUrl, { method:'PATCH', headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','Content-Type':'application/json'}, body:JSON.stringify({field:cell.dataset.inlineField,value}) });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Unable to save');
            cell.dataset.value = payload.value ?? '';
            cell.textContent = payload.display || displayValue(cell, payload.value);
            undo = { cell, value: previousValue, text: previousText };
            undoButton.hidden = false; setState('Saved');
        } catch (error) {
            cell.textContent = previousText; cell.dataset.value = previousValue || ''; setState(error.message || 'Failed to save', true);
        }
    };
    document.querySelectorAll('.inline-cell').forEach(cell => cell.addEventListener('click', () => {
        if (cell.querySelector('.inline-editor')) return;
        const previousValue = cell.dataset.value || '', previousText = cell.textContent.trim();
        let editor;
        if (cell.dataset.inlineType === 'select') {
            editor = document.createElement('select');
            JSON.parse(cell.dataset.options || '[]').forEach(option => { const item = document.createElement('option'); item.value = option.id ?? ''; item.textContent = option.label; item.selected = String(item.value) === String(previousValue); editor.append(item); });
            if (cell.dataset.inlineField !== 'language') { const none = document.createElement('option'); none.value = ''; none.textContent = 'Unassigned'; if (!previousValue) none.selected = true; editor.prepend(none); }
        } else { editor = document.createElement(cell.dataset.inlineType === 'textarea' ? 'textarea' : 'input'); editor.type = cell.dataset.inlineType === 'number' ? 'number' : (cell.dataset.inlineType === 'date' ? 'date' : 'text'); editor.value = previousValue; if (cell.dataset.inlineType === 'textarea') editor.rows = 3; }
        editor.className = 'inline-editor'; cell.textContent = ''; cell.append(editor); editor.focus();
        let committed = false;
        const commit = () => { if (committed) return; committed = true; saveCell(cell, editor, editor.value, previousValue, previousText); };
        editor.addEventListener('change', commit); editor.addEventListener('blur', commit); editor.addEventListener('keydown', event => { if (event.key === 'Enter' && editor.tagName !== 'TEXTAREA') { event.preventDefault(); commit(); } if (event.key === 'Escape') { committed = true; cell.textContent = previousText; } });
    }));
    undoButton.addEventListener('click', async () => { if (!undo) return; const item = undo; undo = null; undoButton.hidden = true; const row = item.cell.closest('tr'); setState('Saving…'); const response = await fetch(row.dataset.updateUrl, {method:'PATCH',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({field:item.cell.dataset.inlineField,value:item.value})}); if (response.ok) { const payload = await response.json(); item.cell.dataset.value = payload.value ?? ''; item.cell.textContent = payload.display || item.text; setState('Saved'); } else { setState('Undo failed', true); } });
    document.querySelectorAll('[data-duplicate-id]').forEach(button => button.addEventListener('click', () => { const form = document.querySelector('[data-duplicate-form-template]'); form.action = form.dataset.duplicateFormTemplate.replace('__BOOKING__', button.dataset.duplicateId); openModal('duplicateModal'); }));
    @if(session('open_modal')) window.addEventListener('load', () => openModal(@json(session('open_modal')))); @endif
    if (new URLSearchParams(window.location.search).get('open') === 'import') window.setTimeout(() => openModal('importBoardModal'), 0);
})();
</script>
@endpush
