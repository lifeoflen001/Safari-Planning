@extends('layouts.app')

@section('title', 'Tasks | Safari Planner')
@section('page_title', 'Tasks')
@section('body_class', 'tasks-page')

@section('content')
<section class="page-header tasks-page-header">
    <div>
        <div class="eyebrow">Operations / Tasks</div>
        <h1>Tasks</h1>
        <p class="lede">Keep the small details of every safari moving forward.</p>
    </div>
    @if($canManage)
        <button class="button button-primary" type="button" data-task-create onclick="openModal('taskModal')">＋ Create task</button>
    @endif
</section>

<div class="tasks-summary-grid">
    <div class="tasks-summary-card tasks-summary-blue"><span>Total tasks</span><strong>{{ $stats['total'] }}</strong><small>Visible in your workspace</small></div>
    <div class="tasks-summary-card tasks-summary-amber"><span>Pending tasks</span><strong>{{ $stats['pending'] }}</strong><small>Need follow-up</small></div>
    <div class="tasks-summary-card tasks-summary-green"><span>Completed tasks</span><strong>{{ $stats['completed'] }}</strong><small>Finished operations</small></div>
    <div class="tasks-summary-card tasks-summary-red"><span>Deleted tasks</span><strong>{{ $stats['deleted'] }}</strong><small>Removed from the active list</small></div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<section class="panel tasks-panel">
    <div class="panel-heading tasks-panel-heading">
        <div><h2>All tasks</h2><p>Assign ownership, due dates and priority from one compact list.</p></div>
        @if($canManage)<button class="button button-secondary" type="button" data-task-create onclick="openModal('taskModal')">＋ New task</button>@endif
    </div>
    <form class="tasks-filter-bar" method="GET" action="{{ route('planner.tasks') }}">
        <label class="tasks-search"><span aria-hidden="true">⌕</span><span class="sr-only">Search tasks</span><input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Search tasks, clients or projects…"></label>
        <label><span class="sr-only">From date</span><input type="date" name="from" value="{{ $filters['from'] }}" aria-label="Due from"></label>
        <label><span class="sr-only">To date</span><input type="date" name="to" value="{{ $filters['to'] }}" aria-label="Due to"></label>
        <select name="status" aria-label="Filter by status"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select>
        <select name="priority" aria-label="Filter by priority"><option value="">All priorities</option>@foreach($priorityOptions as $value => $label)<option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>@endforeach</select>
        @if($canManage)<select name="assigned_to" aria-label="Filter by assignee"><option value="">Everyone</option><option value="unassigned" @selected($filters['assignedTo'] === 'unassigned')>Unassigned</option>@foreach($users as $member)<option value="{{ $member->id }}" @selected($filters['assignedTo'] === (string) $member->id)>{{ $member->name }}</option>@endforeach</select>@endif
        <button class="button button-secondary" type="submit">Apply</button>
        @if(collect($filters)->filter()->isNotEmpty())<a class="tasks-clear-link" href="{{ route('planner.tasks') }}">Clear</a>@endif
    </form>

    <div class="tasks-table-wrap">
        <table class="tasks-table">
            <thead><tr><th class="tasks-check-cell"><span class="sr-only">Select</span></th><th>Task</th><th>Project</th><th>Client / group</th><th>Assigned to</th><th>Due date</th><th>Status</th><th>Priority</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td class="tasks-check-cell"><span class="task-row-marker task-row-marker-{{ $task->status }}"></span></td>
                        <td><strong>{{ $task->title }}</strong><small>{{ $task->description ? IlluminateSupportStr::limit($task->description, 62) : 'No description' }}</small></td>
                        <td>{{ $task->project ?: 'Safari operations' }}</td>
                        <td>{{ $task->client_name ?: 'Internal task' }}</td>
                        <td><span class="task-assignee">{{ $task->assignee?->name ?: 'Unassigned' }}</span></td>
                        <td>{{ $task->due_date?->format('d M Y') ?: 'No due date' }}</td>
                        <td><span class="task-status task-status-{{ $task->status }}">{{ $statusOptions[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                        <td><span class="task-priority task-priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span></td>
                        <td>
                            @if($canManage)
                                <div class="task-actions">
                                    <button class="icon-button" type="button" title="Edit task" aria-label="Edit task" data-task-edit data-task-id="{{ $task->id }}" data-task-title="{{ $task->title }}" data-task-project="{{ $task->project }}" data-task-client-name="{{ $task->client_name }}" data-task-description="{{ $task->description }}" data-task-assigned-to="{{ $task->assigned_to }}" data-task-due-date="{{ $task->due_date?->format('Y-m-d') }}" data-task-status="{{ $task->status }}" data-task-priority="{{ $task->priority }}">✎</button>
                                    <form method="POST" action="{{ route('planner.tasks.destroy', $task) }}" onsubmit="return confirm('Move this task to deleted tasks?')">@csrf @method('DELETE')<button class="icon-button task-delete-button" type="submit" title="Delete task" aria-label="Delete task">⌫</button></form>
                                </div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="tasks-empty-state"><div class="tasks-empty-icon">✓</div><h3>No tasks yet</h3><p>Create a task for an arrival, booking, resource assignment or follow-up.</p>@if($canManage)<button class="button button-primary" type="button" data-task-create onclick="openModal('taskModal')">Create task</button>@endif</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())<div class="tasks-pagination">{{ $tasks->links() }}</div>@endif
</section>

@if($canManage)
<div class="modal-backdrop" id="taskModal" aria-hidden="true">
    <div class="modal task-modal" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
        <div class="modal-head"><div><span class="eyebrow">Operations</span><h2 id="taskModalTitle">Create task</h2></div><button class="modal-close" type="button" onclick="closeModal('taskModal')" aria-label="Close task form">×</button></div>
        <form method="POST" action="{{ route('planner.tasks.store') }}" id="taskForm">
            @csrf
            <input type="hidden" name="_method" value="POST" id="taskMethod">
            <div class="task-form-grid">
                <label>Task title<input type="text" name="title" data-task-field="title" required placeholder="e.g. Confirm airport pickup"></label>
                <label>Project / area<input type="text" name="project" data-task-field="project" placeholder="e.g. July arrivals"></label>
                <label>Client / group<input type="text" name="client_name" data-task-field="client_name" placeholder="e.g. Anderson family"></label>
                <label>Due date<input type="date" name="due_date" data-task-field="due_date"></label>
                <label>Assign to<select name="assigned_to" data-task-field="assigned_to"><option value="">Unassigned</option>@foreach($users as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></label>
                <label>Status<select name="status" data-task-field="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label>Priority<select name="priority" data-task-field="priority">@foreach($priorityOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="task-description-field">Description<textarea name="description" data-task-field="description" rows="3" placeholder="Add useful context for the assignee"></textarea></label>
            </div>
            <div class="modal-foot"><button class="button button-ghost" type="button" onclick="closeModal('taskModal')">Cancel</button><button class="button button-primary" type="submit" id="taskSubmit">Create task</button></div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('taskForm');
    if (!form) return;
    const resetForm = () => {
        form.reset();
        form.action = '{{ route('planner.tasks.store') }}';
        document.getElementById('taskMethod').value = 'POST';
        document.getElementById('taskModalTitle').textContent = 'Create task';
        document.getElementById('taskSubmit').textContent = 'Create task';
    };
    document.querySelectorAll('[data-task-create]').forEach((button) => button.addEventListener('click', resetForm));
    document.querySelectorAll('[data-task-edit]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById('taskModalTitle').textContent = 'Edit task';
        document.getElementById('taskSubmit').textContent = 'Save task';
        form.action = '{{ url('/planner/tasks') }}/' + button.dataset.taskId;
        document.getElementById('taskMethod').value = 'PUT';
        const fields = { title: 'taskTitle', project: 'taskProject', client_name: 'taskClientName', description: 'taskDescription', assigned_to: 'taskAssignedTo', due_date: 'taskDueDate', status: 'taskStatus', priority: 'taskPriority' };
        Object.entries(fields).forEach(([fieldName, dataKey]) => {
            const field = form.querySelector('[data-task-field="' + fieldName + '"]');
            if (field) field.value = button.dataset[dataKey] || '';
        });
        openModal('taskModal');
    }));
})();
</script>
@endpush
