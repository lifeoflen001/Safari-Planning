<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_invites_user_with_hashed_one_time_credentials(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Neema Safari',
            'email' => 'neema@example.com',
            'role' => User::ROLE_EDITOR,
            'job_title' => 'Safari coordinator',
            'record_scope' => 'assigned',
            'access_permissions' => ['planner.view', 'planner.edit'],
            'access_modules' => ['planner-bookings'],
        ])->assertSessionHas('success')->assertSessionHas('invitation_link');

        $user = User::where('email', 'neema@example.com')->firstOrFail();
        $response->assertRedirect(route('admin.users.edit', $user));
        $invitation = $user->invitations()->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertNotEmpty($user->temporary_password_expires_at);
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertNotSame('CHANGEME', $user->password);
        $this->assertSame('sent', $invitation->email_delivery_status);
        Mail::assertSent(UserInvitationMail::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.invited', 'auditable_id' => $user->id]);
    }

    public function test_admin_can_generate_and_copy_a_fresh_invitation_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $user = User::factory()->create(['must_change_password' => true]);
        $invitation = UserInvitation::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', 'old-invitation-token'),
            'expires_at' => now()->addHour(),
            'email_delivery_status' => 'sent',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.users.invitations.copy-link', $invitation));

        $response->assertOk()->assertJsonStructure(['link']);
        $freshLink = $response->json('link');
        $freshToken = str($freshLink)->afterLast('/invite/')->toString();
        $this->assertNotSame('old-invitation-token', $freshToken);
        $this->assertSame(hash('sha256', $freshToken), $invitation->fresh()->token_hash);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.invitation_link_regenerated', 'auditable_id' => $user->id]);
    }

    public function test_invitation_is_single_use_and_requires_new_password(): void
    {
        $token = 'secure-invitation-token';
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'email' => 'invitee@example.com',
            'password' => Hash::make('TS-7Kp9-Mx4Q'),
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);
        $invitation = UserInvitation::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour(),
            'email_delivery_status' => 'sent',
        ]);

        $this->post(route('invitation.accept', $token), [
            'email' => $user->email,
            'temporary_password' => 'TS-7Kp9-Mx4Q',
            'password' => 'A-secure-new-password-123!',
            'password_confirmation' => 'A-secure-new-password-123!',
        ])->assertRedirect(route('planner'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('A-secure-new-password-123!', $user->fresh()->password));
        $this->assertNotNull($invitation->fresh()->invitation_accepted_at);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.invitation_accepted', 'auditable_id' => $user->id]);
    }

    public function test_unassigned_module_is_denied_even_when_role_has_the_permission(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
            'access_permissions' => ['planner.view'],
            'access_modules' => ['planner-bookings'],
        ]);

        $this->actingAs($user)->get(route('planner.guides'))->assertForbidden();
        $this->actingAs($user)->get(route('planner.bookings'))->assertOk();
    }

    public function test_suspend_revokes_sessions_and_records_the_change(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $member = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_active' => true]);
        UserSession::create(['user_id' => $member->id, 'session_hash' => hash('sha256', 'session'), 'last_seen_at' => now()]);

        $this->actingAs($admin)->post(route('admin.users.toggle-status', $member))
            ->assertRedirect();

        $this->assertFalse($member->fresh()->is_active);
        $this->assertNotNull(UserSession::first()->fresh()->revoked_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.sessions_revoked', 'auditable_id' => $member->id]);
    }
}
