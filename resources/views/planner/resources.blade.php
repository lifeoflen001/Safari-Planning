@php
    $isGuides = $resourceType === 'guides';
    $pageTitle = $isGuides ? 'Guides' : 'Vehicles';
    $pageDescription = $isGuides
        ? 'Manage guide availability, language coverage and licensing at a glance.'
        : 'Track vehicle capacity, availability and upcoming service requirements.';
    $listRoute = $isGuides ? 'planner.guides' : 'planner.vehicles';
    $exportRoute = $isGuides ? 'planner.guides.export' : 'planner.vehicles.export';
    $importRoute = $isGuides ? 'planner.guides.import' : 'planner.vehicles.import';
    $templateRoute = $isGuides ? 'planner.guides.template' : 'planner.vehicles.template';
    $statusOptions = $statusOptions ?? collect();
    $filters = $filters ?? ['q' => '', 'status' => '', 'per_page' => 10];
@endphp

@extends('layouts.app')

@section('title', $pageTitle . ' | Safari Planner')
@section('page_title', $pageTitle)
@section('body_class', 'resource-page')

@section('content')
<section class="page-header">
    <div>
        <div class="eyebrow">Operations / {{ $pageTitle }}</div>
        <h1>{{ $pageTitle }}</h1>
        <p class="lede">{{ $pageDescription }}</p>
    </div>
    <div class="page-action">
        <button class="button button-primary" type="button" title="Add {{ $isGuides ? 'guide' : 'vehicle' }}" aria-label="Add {{ $isGuides ? 'guide' : 'vehicle' }}" onclick="openModal('{{ $isGuides ? 'guideModal' : 'vehicleModal' }}')">
            Add {{ $isGuides ? 'guide' : 'vehicle' }}
        </button>
    </div>
</section>

<div class="board-summary">
    <div><span class="board-summary-value">{{ $stats['total'] }}</span><span>{{ strtolower($pageTitle) }}</span></div>
    <div><span class="board-summary-value">{{ $stats['available'] }}</span><span>available now</span></div>
    <div><span class="board-summary-value">{{ $stats['secondary'] }}</span><span>{{ $stats['secondaryLabel'] }}</span></div>
</div>

@if(session('success') && !session('import_report'))
    <div class="resource-flash resource-flash-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="resource-flash resource-flash-error">{{ session('error') }}</div>
@endif

<section class="panel">
    <div class="panel-heading">
        <div>
            <h2>{{ $pageTitle }}</h2>
            <p>{{ $isGuides ? 'All guide details, availability and license dates.' : 'All vehicle details, capacity and service readiness.' }}</p>
        </div>
    </div>

    <div class="resource-toolbar">
        <form class="resource-filter-form" method="GET" action="{{ route($listRoute) }}">
            <label class="resource-search" aria-label="Search {{ strtolower($pageTitle) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search {{ strtolower($pageTitle) }}...">
            </label>
            <select name="status" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="per_page" aria-label="Rows per page">
                @foreach([10, 25, 50, 100] as $quantity)
                    <option value="{{ $quantity }}" @selected((int) $filters['per_page'] === $quantity)>{{ $quantity }} per page</option>
                @endforeach
            </select>
            <button class="button button-secondary resource-filter-button" type="submit" title="Apply filters" aria-label="Apply filters">Apply filters</button>
            @if($filters['q'] || $filters['status'] || (int) $filters['per_page'] !== 10)
                <a class="resource-clear-link" href="{{ route($listRoute) }}">Clear</a>
            @endif
        </form>

        <div class="resource-export-actions">
            @if($user->canExport())
            <a class="resource-tool-button" href="{{ route($exportRoute, array_merge(['format' => 'excel'], request()->query())) }}" title="Export {{ $pageTitle }} to Excel" aria-label="Export {{ $pageTitle }} to Excel">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h10l6 6v10H4z"/><path d="M14 4v6h6M8 14l4 4m0-4-4 4"/></svg>
            </a>
            <a class="resource-tool-button" href="{{ route($exportRoute, array_merge(['format' => 'pdf'], request()->query())) }}" target="_blank" title="Export {{ $pageTitle }} to PDF" aria-label="Export {{ $pageTitle }} to PDF">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h8l4 4v14H6zM14 3v5h5"/><path d="M8.5 16h2.2a1.7 1.7 0 0 0 0-3.4H8.5V18M13.5 18v-5.4h1.7a2.7 2.7 0 0 1 0 5.4h-1.7"/></svg>
            </a>
            @endif
            @if($user->hasPermission($isGuides ? 'guides.edit' : 'vehicles.edit'))
            <form method="POST" action="{{ route($importRoute) }}" enctype="multipart/form-data" id="resourceImportForm">
                @csrf
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                <input type="file" name="file" id="resourceExcelFile" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" hidden onchange="this.form.submit()">
                <button class="resource-tool-button" type="button" title="Import {{ $pageTitle }} from CSV or Excel" aria-label="Import {{ $pageTitle }} from CSV or Excel" onclick="document.getElementById('resourceExcelFile').click()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L8 8m4-4 4 4M5 13v6a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-6"/></svg>
                </button>
            </form>
            <a class="resource-template-link" href="{{ route($templateRoute) }}" title="Download sample Excel template">Sample .xlsx / CSV headings</a>
            @endif
        </div>
    </div>

    <div class="spreadsheet-wrap resource-table-wrap">
        <table class="spreadsheet resource-table">
            @if($isGuides)
                <thead>
                    <tr>
                        <th>Guide</th>
                        <th>Phone</th>
                        <th>Languages</th>
                        <th>License number</th>
                        <th>License expiry</th>
                        <th>Status</th>
                        <th class="resource-actions-heading">Actions</th>
                    </tr>
                </thead>
                <tbody>
            @forelse($guides as $guide)
                <tr>
                    <td>
                        <div class="sheet-group">
                            <span class="avatar avatar-sage avatar-small">{{ collect(explode(' ', $guide->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span>
                            <div>
                                <a class="resource-name-link" href="{{ route('planner.guides.show', $guide) }}">{{ $guide->name }}</a>
                                <small>{{ $guide->email ?: 'Email not added' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $guide->phone ?: 'Not added' }}</td>
                    <td class="language-cell">{{ implode(', ', $guide->languages ?: ['Not specified']) }}</td>
                    <td>{{ $guide->license_number ?: 'Not added' }}</td>
                    <td>{{ $guide->license_expiry?->format('d M Y') ?: 'No expiry date' }}</td>
                    <td><span class="status-pill status-{{ $guide->status === 'available' ? 'green' : 'blue' }}">{{ ucfirst(str_replace('_', ' ', $guide->status)) }}</span></td>
                    <td>
                        <div class="resource-actions">
                            <a class="icon-button" href="{{ route('planner.guides.show', $guide) }}" title="View guide profile" aria-label="View guide profile">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </a>
                            <details class="resource-action-menu">
                                <summary class="icon-button" title="More guide actions" aria-label="More guide actions">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="19" cy="12" r="1" fill="currentColor"/></svg>
                                </summary>
                                <div class="resource-action-popover">
                                    <a href="{{ route('planner.guides.show', $guide) }}">View profile</a>
                                    @if($user->hasPermission('guides.edit'))
                                        <button type="button" data-resource-edit="guide" data-resource-update-url="{{ route('planner.guides.update', $guide) }}" data-resource-name="{{ $guide->name }}" data-name="{{ $guide->name }}" data-email="{{ $guide->email }}" data-phone="{{ $guide->phone }}" data-license-number="{{ $guide->license_number }}" data-status="{{ $guide->status }}" data-languages="{{ implode(', ', $guide->languages ?: []) }}" data-license-expiry="{{ $guide->license_expiry?->format('Y-m-d') }}">Edit guide</button>
                                        <button type="button" class="resource-danger-action" data-resource-delete="{{ route('planner.guides.destroy', $guide) }}" data-resource-name="{{ $guide->name }}">Remove guide</button>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><h2>No guides added</h2><p>Add guides to see availability and language fit here.</p></div></td></tr>
            @endforelse
                </tbody>
            @else
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Last service</th>
                        <th>Next service</th>
                        <th class="resource-actions-heading">Actions</th>
                    </tr>
                </thead>
                <tbody>
            @forelse($vehicles as $vehicle)
                <tr>
                    <td>
                        <div class="sheet-group">
                            <span class="metric-icon metric-blue">4x4</span>
                            <div>
                                <a class="resource-name-link" href="{{ route('planner.vehicles.show', $vehicle) }}">{{ $vehicle->registration_number }}</a>
                                <small>{{ $vehicle->type }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $vehicle->type }}</td>
                    <td><span class="pax-number">{{ $vehicle->capacity }}</span> guests</td>
                    <td><span class="status-pill status-{{ $vehicle->status === 'available' ? 'green' : 'blue' }}">{{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}</span></td>
                    <td>{{ $vehicle->last_service_at?->format('d M Y') ?: 'Not recorded' }}</td>
                    <td>{{ $vehicle->next_service_at?->format('d M Y') ?: 'Not scheduled' }}</td>
                    <td>
                        <div class="resource-actions">
                            <a class="icon-button" href="{{ route('planner.vehicles.show', $vehicle) }}" title="View vehicle profile" aria-label="View vehicle profile">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </a>
                            <details class="resource-action-menu">
                                <summary class="icon-button" title="More vehicle actions" aria-label="More vehicle actions">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="19" cy="12" r="1" fill="currentColor"/></svg>
                                </summary>
                                <div class="resource-action-popover">
                                    <a href="{{ route('planner.vehicles.show', $vehicle) }}">View profile</a>
                                    @if($user->hasPermission('vehicles.edit'))
                                        <button type="button" data-resource-edit="vehicle" data-resource-update-url="{{ route('planner.vehicles.update', $vehicle) }}" data-resource-name="{{ $vehicle->registration_number }}" data-registration-number="{{ $vehicle->registration_number }}" data-type="{{ $vehicle->type }}" data-capacity="{{ $vehicle->capacity }}" data-status="{{ $vehicle->status }}" data-last-service-at="{{ $vehicle->last_service_at?->format('Y-m-d') }}" data-next-service-at="{{ $vehicle->next_service_at?->format('Y-m-d') }}">Edit vehicle</button>
                                        <button type="button" class="resource-danger-action" data-resource-delete="{{ route('planner.vehicles.destroy', $vehicle) }}" data-resource-name="{{ $vehicle->registration_number }}">Remove vehicle</button>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><h2>No vehicles added</h2><p>Add vehicles to track capacity and service readiness.</p></div></td></tr>
            @endforelse
                </tbody>
            @endif
        </table>
    </div>

    <div class="resource-pagination">
        <span>Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }}</span>
        <div class="resource-pagination-links">
            @if($records->onFirstPage())
                <span class="resource-page-disabled">Previous</span>
            @else
                <a href="{{ $records->previousPageUrl() }}">Previous</a>
            @endif
            @foreach($records->getUrlRange(max(1, $records->currentPage() - 1), min($records->lastPage(), $records->currentPage() + 1)) as $page => $url)
                <a class="{{ $page === $records->currentPage() ? 'is-current' : '' }}" href="{{ $url }}">{{ $page }}</a>
            @endforeach
            @if($records->hasMorePages())
                <a href="{{ $records->nextPageUrl() }}">Next</a>
            @else
                <span class="resource-page-disabled">Next</span>
            @endif
        </div>
    </div>
</section>

@if($resourceType === 'guides')
    @include('planner.modals.create-guide')
@else
    @include('planner.modals.create-vehicle')
@endif

<div class="modal-backdrop" id="resourceEditModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('resourceEditModal')">
    <div class="modal">
        <div class="modal-head">
            <h2 id="resourceEditTitle">Edit {{ $isGuides ? 'guide' : 'vehicle' }}</h2>
            <button type="button" class="icon-button" onclick="closeModal('resourceEditModal')" aria-label="Close edit form">&times;</button>
        </div>
        <form method="POST" id="resourceEditForm" class="modal-body">
            @csrf
            @method('PUT')
            @if($isGuides)
                <div class="modal-form-grid">
                    <label>Full name <span class="required">*</span><input type="text" name="name" data-edit-field="name" required></label>
                    <label>Email<input type="email" name="email" data-edit-field="email"></label>
                    <label>Phone<input type="text" name="phone" data-edit-field="phone"></label>
                    <label>License number<input type="text" name="license_number" data-edit-field="licenseNumber"></label>
                    <label>Status <span class="required">*</span><select name="status" data-edit-field="status" required><option value="available">Available</option><option value="on_trip">On trip</option><option value="on_leave">On leave</option><option value="unavailable">Unavailable</option></select></label>
                    <label>Languages<input type="text" name="languages" data-edit-field="languages" placeholder="English, Swahili"></label>
                    <label>License expiry<input type="date" name="license_expiry" data-edit-field="licenseExpiry"></label>
                </div>
            @else
                <div class="modal-form-grid">
                    <label>Registration number <span class="required">*</span><input type="text" name="registration_number" data-edit-field="registrationNumber" required></label>
                    <label>Type <span class="required">*</span><input type="text" name="type" data-edit-field="type" required></label>
                    <label>Capacity <span class="required">*</span><input type="number" name="capacity" min="1" max="20" data-edit-field="capacity" required></label>
                    <label>Status <span class="required">*</span><select name="status" data-edit-field="status" required><option value="available">Available</option><option value="in_service">In service</option><option value="maintenance">Maintenance</option><option value="unavailable">Unavailable</option></select></label>
                    <label>Last service<input type="date" name="last_service_at" data-edit-field="lastServiceAt"></label>
                    <label>Next service<input type="date" name="next_service_at" data-edit-field="nextServiceAt"></label>
                </div>
            @endif
            <div class="modal-foot">
                <button type="button" class="button button-secondary" onclick="closeModal('resourceEditModal')">Cancel</button>
                <button type="submit" class="button button-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="resourceDeleteModal" style="display:none" aria-hidden="true" onclick="if(event.target===this)closeModal('resourceDeleteModal')">
    <div class="modal modal-confirm">
        <div class="modal-head">
            <h2>Remove {{ $isGuides ? 'guide' : 'vehicle' }}?</h2>
            <button type="button" class="icon-button" onclick="closeModal('resourceDeleteModal')" aria-label="Close remove dialog">&times;</button>
        </div>
        <div class="modal-body">
            <p id="resourceDeleteMessage">This action cannot be undone.</p>
            <form method="POST" id="resourceDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-foot">
                    <button type="button" class="button button-secondary" onclick="closeModal('resourceDeleteModal')">Cancel</button>
                    <button type="submit" class="button button-danger">Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('resourceEditModal');
    const editForm = document.getElementById('resourceEditForm');
    const editTitle = document.getElementById('resourceEditTitle');
    const deleteModal = document.getElementById('resourceDeleteModal');
    const deleteForm = document.getElementById('resourceDeleteForm');
    const deleteMessage = document.getElementById('resourceDeleteMessage');

    const positionActionMenu = (menu) => {
        const popover = menu.querySelector('.resource-action-popover');
        if (!popover) return;

        const trigger = menu.querySelector('summary').getBoundingClientRect();
        popover.classList.add('is-floating');
        const width = popover.offsetWidth;
        const height = popover.offsetHeight;
        const left = Math.min(window.innerWidth - width - 8, Math.max(8, trigger.right - width));
        const top = trigger.bottom + height + 8 > window.innerHeight
            ? Math.max(8, trigger.top - height - 6)
            : trigger.bottom + 6;
        popover.style.left = `${left}px`;
        popover.style.top = `${top}px`;
    };

    document.querySelectorAll('.resource-action-menu').forEach((menu) => {
        menu.addEventListener('toggle', () => {
            const popover = menu.querySelector('.resource-action-popover');
            if (!menu.open) {
                popover?.classList.remove('is-floating');
                if (popover) {
                    popover.style.left = '';
                    popover.style.top = '';
                }
                return;
            }

            document.querySelectorAll('.resource-action-menu[open]').forEach((other) => {
                if (other !== menu) other.open = false;
            });
            positionActionMenu(menu);
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.resource-action-menu')) {
            document.querySelectorAll('.resource-action-menu[open]').forEach((menu) => { menu.open = false; });
        }
    });

    window.addEventListener('resize', () => {
        const openMenu = document.querySelector('.resource-action-menu[open]');
        if (openMenu) positionActionMenu(openMenu);
    });

    document.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-resource-edit]');
        if (editButton && editModal && editForm) {
            event.preventDefault();
            editForm.action = editButton.dataset.resourceUpdateUrl;
            editTitle.textContent = `Edit ${editButton.dataset.resourceEdit}`;
            editForm.querySelectorAll('[data-edit-field]').forEach((field) => {
                const key = field.dataset.editField;
                field.value = editButton.dataset[key] || '';
            });
            editButton.closest('details')?.removeAttribute('open');
            openModal('resourceEditModal');
            return;
        }

        const deleteButton = event.target.closest('[data-resource-delete]');
        if (deleteButton && deleteModal && deleteForm) {
            event.preventDefault();
            deleteForm.action = deleteButton.dataset.resourceDelete;
            deleteMessage.textContent = `Remove ${deleteButton.dataset.resourceName}? This will remove it from future assignments.`;
            deleteButton.closest('details')?.removeAttribute('open');
            openModal('resourceDeleteModal');
        }
    });
});
</script>
@endpush

@endsection
