<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canManage = $user->hasPermission('tasks.manage');
        $baseQuery = $this->visibleQuery($user, $canManage);
        $query = clone $baseQuery;
        $search = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));
        $priority = trim((string) $request->input('priority', ''));
        $assignedTo = trim((string) $request->input('assigned_to', ''));
        $from = trim((string) $request->input('from', ''));
        $to = trim((string) $request->input('to', ''));

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('title', 'like', $like)
                    ->orWhere('project', 'like', $like)
                    ->orWhere('client_name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }
        if ($status !== '') $query->where('status', $status);
        if ($priority !== '') $query->where('priority', $priority);
        if ($assignedTo === 'unassigned') $query->whereNull('assigned_to');
        elseif ($assignedTo !== '') $query->where('assigned_to', $assignedTo);
        if ($from !== '') $query->whereDate('due_date', '>=', $from);
        if ($to !== '') $query->whereDate('due_date', '<=', $to);

        $tasks = $query->with(['assignee', 'creator'])
            ->orderByRaw('due_date IS NULL, due_date asc')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        $allVisible = $baseQuery->get(['id', 'status', 'assigned_to']);
        $deletedQuery = Task::onlyTrashed();
        if (! $canManage) {
            $deletedQuery->where(function ($builder) use ($user): void {
                $builder->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
            });
        }
        $users = $canManage ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'job_title']) : collect();

        return view('planner.tasks', [
            'user' => $user,
            'tasks' => $tasks,
            'users' => $users,
            'canManage' => $canManage,
            'filters' => compact('search', 'status', 'priority', 'assignedTo', 'from', 'to'),
            'statusOptions' => ['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
            'priorityOptions' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'],
            'stats' => [
                'total' => $allVisible->count(),
                'pending' => $allVisible->whereIn('status', ['pending', 'in_progress'])->count(),
                'completed' => $allVisible->where('status', 'completed')->count(),
                'unassigned' => $allVisible->whereNull('assigned_to')->count(),
                'deleted' => $deletedQuery->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by'] = $request->user()->id;
        Task::create($validated);

        return redirect()->route('planner.tasks')->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        $task->update($this->validated($request));

        return redirect()->route('planner.tasks')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('planner.tasks')->with('success', 'Task moved to deleted tasks.');
    }

    private function visibleQuery(User $user, bool $canManage)
    {
        $query = Task::query();
        if (! $canManage) {
            $query->where(function ($builder) use ($user): void {
                $builder->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'project' => ['nullable', 'string', 'max:120'],
            'client_name' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);
    }
}
