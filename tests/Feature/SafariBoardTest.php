<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Guide;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SafariBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_inline_edit_a_planning_row(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $company = Company::create(['name' => 'Tanzania Specialist']);
        $guide = Guide::create(['name' => 'Mollel Guide', 'phone' => '+255700000001']);
        $vehicle = Vehicle::create(['registration_number' => 'T 123 ABC', 'capacity' => 7]);

        $response = $this->actingAs($admin)->post(route('safari-board.store', [2026, 'january']), [
            'plan_year' => 2026,
            'plan_month' => 1,
            'start_instruction' => 'P/START',
            'group_name' => 'Kilimanjaro Sunrise',
            'pax' => 4,
            'arrival_date' => '2026-01-04',
            'departure_date' => '2026-01-10',
            'guide_id' => $guide->id,
            'vehicle_id' => $vehicle->id,
            'language' => 'EN',
            'primary_flight_time' => 'KQ 123',
            'company_id' => $company->id,
            'assignment_notes' => 'signed for guest',
            'payment_status' => 'paid',
            'operational_notes' => 'Meet at 08:00',
            'status' => 'confirmed',
            'visibility' => 'workspace',
            'submit_action' => 'assign',
        ]);

        $response->assertRedirect(route('safari-board.index', [2026, 'january']));
        $booking = Booking::where('group_name', 'Kilimanjaro Sunrise')->firstOrFail();
        $this->assertSame('+255700000001', $booking->guide_phone);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $booking->id, 'action' => 'safari_board.created']);

        $this->actingAs($admin)
            ->patchJson(route('safari-board.inline-update', $booking), ['field' => 'group_name', 'value' => 'Kilimanjaro Sunrise Updated'])
            ->assertOk()
            ->assertJsonPath('display', 'Kilimanjaro Sunrise Updated');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'group_name' => 'Kilimanjaro Sunrise Updated']);
    }

    public function test_non_admins_have_read_only_visibility_scoped_board_access(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_active' => true]);
        $otherUser = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_active' => true]);

        $workspace = Booking::create([
            'reference' => 'TS-2026-0001', 'group_name' => 'Workspace Group', 'pax' => 2,
            'plan_year' => 2026, 'plan_month' => 1, 'arrival_date' => '2026-01-05',
            'departure_date' => '2026-01-06', 'status' => 'confirmed', 'payment_status' => 'paid',
            'visibility' => 'workspace',
        ]);
        $private = Booking::create([
            'reference' => 'TS-2026-0002', 'group_name' => 'Private Group', 'pax' => 2,
            'plan_year' => 2026, 'plan_month' => 1, 'status' => 'draft', 'payment_status' => 'pending',
            'visibility' => 'private', 'visible_to_user_id' => $otherUser->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('safari-board.index', [2026, 'january']))
            ->assertOk()
            ->assertSee('Workspace Group')
            ->assertDontSee('Private Group')
            ->assertSee('Read-only operational view');

        $this->actingAs($viewer)
            ->patchJson(route('safari-board.inline-update', $workspace), ['field' => 'group_name', 'value' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $private->id, 'group_name' => 'Private Group']);
    }

    public function test_capacity_filter_and_month_navigation_use_live_records(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $vehicle = Vehicle::create(['registration_number' => 'T 999 XYZ', 'capacity' => 2]);
        Booking::create([
            'reference' => 'TS-2026-0003', 'group_name' => 'Capacity Warning Group', 'pax' => 5,
            'plan_year' => 2026, 'plan_month' => 2, 'arrival_date' => '2026-02-02',
            'departure_date' => '2026-02-03', 'vehicle_id' => $vehicle->id,
            'status' => 'confirmed', 'payment_status' => 'pending', 'visibility' => 'workspace',
        ]);

        $this->actingAs($admin)
            ->get(route('safari-board.index', [2026, 'february']).'?capacity_warning=1')
            ->assertOk()
            ->assertSee('Capacity Warning Group');
    }

    public function test_admin_can_duplicate_and_delete_safari_board_rows(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $booking = Booking::create([
            'reference' => 'TS-2026-ACT1', 'group_name' => 'Action Test Group', 'pax' => 2,
            'plan_year' => 2026, 'plan_month' => 1, 'arrival_date' => '2026-01-05',
            'departure_date' => '2026-01-06', 'status' => 'confirmed', 'payment_status' => 'paid',
            'visibility' => 'workspace',
        ]);

        $this->actingAs($admin)
            ->post(route('safari-board.duplicate', $booking), ['plan_year' => 2026, 'plan_month' => 2])
            ->assertRedirect(route('safari-board.index', [2026, 'february']));

        $this->assertDatabaseHas('bookings', ['group_name' => 'Action Test Group', 'plan_month' => 2, 'status' => 'draft']);

        $this->actingAs($admin)
            ->delete(route('safari-board.destroy', $booking))
            ->assertRedirect(route('safari-board.index', [2026, 'january']));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_admin_can_import_safari_board_rows_and_export_route_is_removed(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $this->actingAs($admin)
            ->get(route('safari-board.import-template', [2026, 'january']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $company = Company::create(['name' => 'Tanzania Specialist']);
        $guide = Guide::create(['name' => 'Imported Guide', 'phone' => '+255700000009']);
        $vehicle = Vehicle::create(['registration_number' => 'T 009 ABC', 'capacity' => 7]);
        $csv = implode("\n", [
            'Status / Start Instruction,Group Name,PAX,Arrival Date,Departure Date,Guide Name,Vehicle Number,Language,Time / Flight,Company,Payment Status',
            'Confirmed,Imported Safari Group,4,04/01/2026,10/01/2026,Imported Guide,T 009 ABC,EN,KQ 123,Tanzania Specialist,Paid',
        ]);

        $this->actingAs($admin)
            ->post(route('safari-board.import', [2026, 'january']), [
                'file' => UploadedFile::fake()->createWithContent('safari-board.csv', $csv),
            ])
            ->assertRedirect(route('safari-board.index', [2026, 'january']))
            ->assertSessionHas('success', '1 planning row imported successfully.');

        $this->assertDatabaseHas('bookings', [
            'group_name' => 'Imported Safari Group',
            'pax' => 4,
            'guide_id' => $guide->id,
            'vehicle_id' => $vehicle->id,
            'company_id' => $company->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'safari_board.imported']);
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($admin)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Safari board update');
        $this->actingAs($admin)->postJson('/api/notifications/read')->assertOk()->assertJsonPath('unread_count', 0);
        $this->actingAs($admin)->get('/safari-board/2026/january/export')->assertNotFound();
    }
}
