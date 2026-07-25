<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_update_and_delete_a_task(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('planner.tasks'))
            ->assertOk()
            ->assertSee('All tasks');

        $this->actingAs($admin)
            ->post(route('planner.tasks.store'), [
                'title' => 'Confirm airport pickup',
                'project' => 'July arrivals',
                'client_name' => 'Anderson family',
                'due_date' => now()->toDateString(),
                'status' => 'pending',
                'priority' => 'high',
            ])
            ->assertRedirect(route('planner.tasks'));

        $task = Task::firstOrFail();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Confirm airport pickup', 'priority' => 'high']);

        $this->actingAs($admin)
            ->put(route('planner.tasks.update', $task), [
                'title' => 'Confirm airport pickup and guide',
                'project' => 'July arrivals',
                'client_name' => 'Anderson family',
                'status' => 'completed',
                'priority' => 'high',
            ])
            ->assertRedirect(route('planner.tasks'));

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Confirm airport pickup and guide', 'status' => 'completed']);

        $this->actingAs($admin)
            ->delete(route('planner.tasks.destroy', $task))
            ->assertRedirect(route('planner.tasks'));

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_non_managers_only_see_tasks_assigned_to_them_or_created_by_them(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_active' => true]);
        Task::create(['title' => 'Assigned task', 'assigned_to' => $viewer->id, 'created_by' => $admin->id, 'status' => 'pending', 'priority' => 'medium']);
        Task::create(['title' => 'Private unassigned task', 'created_by' => $admin->id, 'status' => 'pending', 'priority' => 'medium']);

        $this->actingAs($viewer)
            ->get(route('planner.tasks'))
            ->assertOk()
            ->assertSee('Assigned task')
            ->assertDontSee('Private unassigned task');

        $this->actingAs($viewer)
            ->post(route('planner.tasks.store'), ['title' => 'Not allowed', 'status' => 'pending', 'priority' => 'low'])
            ->assertForbidden();
    }
}
