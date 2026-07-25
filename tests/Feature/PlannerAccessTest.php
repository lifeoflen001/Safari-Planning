<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Company;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlannerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_open_the_planner(): void
    {
        $user = User::factory()->create(['name' => 'Asha Kweka', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/planner')
            ->assertOk()
            ->assertSee('Asha Kweka');
    }

    public function test_authenticated_users_can_open_the_safari_board(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/planner/bookings')
            ->assertOk()
            ->assertSee('Safari board')
            ->assertSee('No planning rows match this month');
    }

    public function test_overview_uses_live_board_records_and_refresh_endpoint(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $booking = Booking::create([
            'reference' => 'TS-LIVE-0001',
            'group_name' => 'Live Overview Group',
            'pax' => 4,
            'plan_year' => now()->year,
            'plan_month' => now()->month,
            'arrival_date' => now()->toDateString(),
            'departure_date' => now()->addDay()->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'visibility' => 'workspace',
        ]);
        Activity::create([
            'booking_id' => $booking->id,
            'type' => 'safari',
            'title' => 'Live Overview Activity',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->get(route('planner'))
            ->assertOk()
            ->assertSee('Live Overview Group')
            ->assertSee('Live Overview Activity')
            ->assertDontSee('The Anderson Family');

        $payload = $this->actingAs($admin)
            ->get(route('planner.overview'))
            ->assertOk()
            ->assertJsonPath('refreshed_at', fn ($value) => is_string($value))
            ->json();

        $this->assertStringContainsString('Live Overview Group', $payload['bookings_html']);
        $this->assertStringContainsString('Live Overview Activity', $payload['agenda_html']);
    }

    public function test_each_sidebar_destination_renders_its_own_page(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN]);
        $destinations = [
            ['route' => 'planner.calendar', 'heading' => 'Planning calendar'],
            ['route' => 'planner.transfers', 'heading' => 'Transfer desk'],
            ['route' => 'planner.guides', 'heading' => 'Guides'],
            ['route' => 'planner.vehicles', 'heading' => 'Vehicles'],
            ['route' => 'planner.reports', 'heading' => 'Reports & exports'],
            ['route' => 'planner.documents', 'heading' => 'Documents'],
            ['route' => 'settings.team', 'heading' => 'People with planner access'],
            ['route' => 'settings.profile', 'heading' => 'My Profile'],
            ['route' => 'settings.personal', 'heading' => 'Personal Information'],
        ];

        foreach ($destinations as $destination) {
            $this->actingAs($user)
                ->get(route($destination['route']))
                ->assertOk()
                ->assertSee($destination['heading']);
        }
    }

    public function test_resource_names_open_their_profiles(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $guide = Guide::create([
            'name' => 'Joel Mollel',
            'email' => 'joel@example.com',
            'phone' => '+255 754 000 222',
            'license_number' => 'GUIDE-001',
            'status' => 'available',
            'languages' => ['English', 'Swahili'],
            'license_expiry' => now()->addYear()->toDateString(),
        ]);
        $vehicle = Vehicle::create([
            'registration_number' => 'TZ 381 AB',
            'type' => '4x4 safari vehicle',
            'capacity' => 6,
            'status' => 'available',
            'last_service_at' => now()->subMonth()->toDateString(),
            'next_service_at' => now()->addMonths(5)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('planner.guides'))
            ->assertOk()
            ->assertSee('Joel Mollel')
            ->assertSee(route('planner.guides.show', $guide));

        $this->actingAs($user)
            ->get(route('planner.guides.show', $guide))
            ->assertOk()
            ->assertSee('Guide profile')
            ->assertSee('GUIDE-001');

        $this->actingAs($user)
            ->get(route('planner.vehicles'))
            ->assertOk()
            ->assertSee('TZ 381 AB')
            ->assertSee(route('planner.vehicles.show', $vehicle));

        $this->actingAs($user)
            ->get(route('planner.vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Vehicle profile')
            ->assertSee('6 guests');
    }

    public function test_resource_lists_support_filters_pagination_and_exports(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => User::ROLE_EDITOR]);
        Guide::create(['name' => 'Joel Mollel', 'status' => 'available', 'languages' => ['English']]);
        Guide::create(['name' => 'Amani Kweka', 'status' => 'on_trip', 'languages' => ['French']]);

        $this->actingAs($user)
            ->get(route('planner.guides', ['q' => 'Joel', 'per_page' => 1]))
            ->assertOk()
            ->assertSee('Joel Mollel')
            ->assertDontSee('Amani Kweka')
            ->assertSee('Showing 1–1 of 1');

        $this->actingAs($user)
            ->get(route('planner.guides.export', ['format' => 'excel', 'q' => 'Joel']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)
            ->get(route('planner.guides.export', ['format' => 'pdf', 'q' => 'Joel']))
            ->assertOk()
            ->assertSee('Joel Mollel');

        $this->actingAs($user)
            ->get(route('planner.guides.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)
            ->get(route('planner.vehicles.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_inactive_users_cannot_open_protected_pages(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/planner')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_profile_updates_persist_contact_details(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => 'Asha Kweka',
                'job_title' => 'Senior planner',
                'phone' => '+255 754 000 111',
                'timezone' => 'Africa/Dar_es_Salaam',
            ])
            ->assertOk()
            ->assertJsonPath('user.phone', '+255 754 000 111')
            ->assertJsonPath('user.timezone', 'Africa/Dar_es_Salaam');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+255 754 000 111',
            'timezone' => 'Africa/Dar_es_Salaam',
        ]);
    }

    public function test_personal_settings_persist_extended_profile_fields(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('settings.personal.update'), [
                'first_name' => 'Asha',
                'middle_name' => 'Neema',
                'last_name' => 'Kweka',
                'preferred_name' => 'Ash',
                'job_title' => 'Senior planner',
                'department' => 'Operations',
                'email' => $user->email,
                'phone' => '+255 754 000 111',
                'alternative_phone' => '+255 754 000 112',
                'preferred_language' => 'en',
            ])
            ->assertRedirect(route('settings.personal'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Asha Neema Kweka',
            'middle_name' => 'Neema',
            'preferred_name' => 'Ash',
            'alternative_phone' => '+255 754 000 112',
        ]);
    }

    public function test_admin_can_save_workspace_profile(): void
    {
        $company = Company::create(['name' => 'Tanzania Specialist']);
        $user = User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN, 'company_id' => $company->id]);

        $this->actingAs($user)
            ->post(route('settings.workspace.update'), [
                'name' => 'Tanzania Specialist Operations',
                'country' => 'Tanzania',
                'logo_url' => 'https://example.com/logo.png',
                'website' => 'https://example.com',
                'contact_email' => 'ops@example.com',
                'contact_phone' => '+255 700 000 000',
                'description' => 'A trusted safari planning workspace.',
            ])
            ->assertRedirect(route('settings.workspace'));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Tanzania Specialist Operations',
            'website' => 'https://example.com',
            'description' => 'A trusted safari planning workspace.',
        ]);
    }

    public function test_admin_can_schedule_a_transfer_from_the_modal(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->get(route('planner.transfers.import-template', [2026, 'august']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($user)
            ->post(route('planner.transfers.store'), [
                'group_name' => 'The Morgan family',
                'pax' => 4,
                'arrival_date' => '2026-08-04',
                'departure_date' => '2026-08-12',
                'pickup_location' => 'Kilimanjaro Airport',
                'dropoff_location' => 'Arusha Coffee Lodge',
                'pickup_time' => '08:30',
                'flight_number' => 'PW 412',
                'notes' => 'Four large bags.',
            ])
            ->assertRedirect(route('planner.transfers'))
            ->assertSessionHas('success', 'Transfer scheduled successfully.');

        $this->assertDatabaseHas('bookings', [
            'group_name' => 'The Morgan family',
            'pax' => 4,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('activities', [
            'type' => 'transfer',
            'flight_number' => 'PW 412',
        ]);
        $this->assertDatabaseHas('locations', ['name' => 'Kilimanjaro Airport', 'type' => 'transfer_point']);
    }

    public function test_transfer_action_column_routes_edit_duplicate_cancel_and_archive_transfers(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('planner.transfers.store'), [
            'group_name' => 'Action Test Transfer',
            'pax' => 2,
            'arrival_date' => '2026-08-04',
            'departure_date' => '2026-08-04',
            'pickup_location' => 'Kilimanjaro Airport',
            'dropoff_location' => 'Arusha Coffee Lodge',
            'pickup_time' => '08:30',
        ]);

        $transfer = Activity::where('type', 'transfer')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('planner.transfers.update', $transfer), [
                'group_name' => 'Action Test Transfer Updated',
                'pax' => 3,
                'arrival_date' => '2026-08-04',
                'departure_date' => '2026-08-04',
                'pickup_location' => 'Kilimanjaro Airport',
                'dropoff_location' => 'Arusha Coffee Lodge',
                'pickup_time' => '09:00',
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $transfer->booking_id, 'group_name' => 'Action Test Transfer Updated', 'pax' => 3]);

        $this->actingAs($admin)
            ->post(route('planner.transfers.duplicate', $transfer))
            ->assertRedirect();

        $copy = Activity::where('type', 'transfer')->where('id', '!=', $transfer->id)->firstOrFail();

        $this->actingAs($admin)->post(route('planner.transfers.cancel', $transfer))->assertRedirect();
        $this->actingAs($admin)->post(route('planner.transfers.archive', $copy))->assertRedirect();

        $this->assertDatabaseHas('activities', ['id' => $transfer->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('activities', ['id' => $copy->id, 'status' => 'archived']);
    }

    public function test_admin_can_add_a_teammate_from_the_modal(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->post(route('planner.team.invite'), [
                'name' => 'Asha Mollel',
                'email' => 'asha.mollel@example.com',
                'job_title' => 'Safari coordinator',
                'role' => 'editor',
                'timezone' => 'Africa/Dar_es_Salaam',
            ])
            ->assertRedirect(route('planner.team'))
            ->assertSessionHas('success', 'Teammate added successfully.');

        $this->assertDatabaseHas('users', [
            'name' => 'Asha Mollel',
            'email' => 'asha.mollel@example.com',
            'role' => 'editor',
        ]);
    }
}
