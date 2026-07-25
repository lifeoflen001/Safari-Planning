<?php

namespace App\Services;

use App\Mail\UserInvitationMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AccessManagementService
{
    public function invite(User $actor, array $data): array
    {
        return DB::transaction(function () use ($actor, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make(Str::random(64)),
                'role' => $data['role'],
                'job_title' => $data['job_title'] ?? null,
                'phone' => $data['phone'] ?? null,
                'timezone' => $data['timezone'] ?? 'Africa/Dar_es_Salaam',
                'company_id' => $data['company_id'] ?? $actor->company_id,
                'is_active' => true,
                'access_permissions' => $this->permissions($data),
                'access_modules' => $this->modules($data),
                'record_scope' => $data['record_scope'] ?? 'workspace',
                'must_change_password' => true,
            ]);
            UserSetting::firstOrCreate(['user_id' => $user->id]);
            $result = $this->rotateInvitation($actor, $user);
            $this->audit($actor, $user, 'user.invited', ['role' => $user->role, 'permissions' => $user->effectivePermissions()]);

            return $result;
        });
    }

    public function update(User $actor, User $user, array $data): User
    {
        $before = [
            'role' => $user->role,
            'permissions' => $user->effectivePermissions(),
            'modules' => $user->access_modules,
            'record_scope' => $user->record_scope,
            'is_active' => $user->is_active,
        ];
        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'] ?? $user->timezone,
            'is_active' => $data['is_active'] ?? $user->is_active,
            'access_permissions' => $this->permissions($data),
            'access_modules' => $this->modules($data),
            'record_scope' => $data['record_scope'] ?? 'workspace',
        ]);
        $this->audit($actor, $user, 'user.access_updated', ['before' => $before, 'after' => [
            'role' => $user->role, 'permissions' => $user->effectivePermissions(),
            'modules' => $user->access_modules, 'record_scope' => $user->record_scope, 'is_active' => $user->is_active,
        ]]);

        return $user;
    }

    public function rotateInvitation(User $actor, User $user): array
    {
        $user->invitations()->whereNull('invitation_accepted_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $token = Str::random(64);
        $temporaryPassword = 'TS-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        $expiresAt = now()->addHours(config('access.invitation_ttl_hours'));
        $tempExpiresAt = now()->addHours(config('access.temporary_password_ttl_hours'));

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'temporary_password_expires_at' => $tempExpiresAt,
            'temporary_password_attempts' => 0,
            'temporary_password_locked_at' => null,
            'temporary_password_used_at' => null,
            'is_active' => true,
        ]);

        $invitation = $user->invitations()->create([
            'invited_by' => $actor->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'email_delivery_status' => 'pending',
        ]);
        $invitationLink = route('invitation.show', ['token' => $token]);

        $deliveryError = null;
        try {
            Mail::to($user->email)->send(new UserInvitationMail($user, $token, $temporaryPassword, $expiresAt));
            $invitation->update(['email_sent_at' => now(), 'email_delivery_status' => 'sent']);
        } catch (Throwable $exception) {
            report($exception);
            $deliveryError = $exception->getMessage();
            $invitation->update(['email_delivery_status' => 'failed', 'email_delivery_error' => $deliveryError]);
        }

        $this->audit($actor, $user, 'user.invitation_sent', [
            'invitation_id' => $invitation->id,
            'delivery_status' => $invitation->email_delivery_status,
        ]);

        return compact('user', 'invitation', 'temporaryPassword', 'deliveryError', 'invitationLink');
    }

    public function revokeInvitation(User $actor, UserInvitation $invitation): void
    {
        $invitation->update(['revoked_at' => now()]);
        $this->audit($actor, $invitation->user, 'user.invitation_revoked', ['invitation_id' => $invitation->id]);
    }

    public function regenerateLink(User $actor, UserInvitation $invitation): string
    {
        $token = Str::random(64);
        $invitation->update([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(config('access.invitation_ttl_hours')),
            'invitation_opened_at' => null,
            'email_delivery_status' => 'pending',
            'email_delivery_error' => null,
            'revoked_at' => null,
        ]);
        $this->audit($actor, $invitation->user, 'user.invitation_link_regenerated', ['invitation_id' => $invitation->id]);

        return route('invitation.show', ['token' => $token]);
    }

    public function revokeSessions(User $actor, User $user): void
    {
        $user->increment('session_version');
        $user->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $this->audit($actor, $user, 'user.sessions_revoked', ['session_version' => $user->fresh()->session_version]);
    }

    public function audit(User $actor, User $subject, string $action, array $changes): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => User::class,
            'auditable_id' => $subject->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    private function permissions(array $data): ?array
    {
        if (! array_key_exists('access_permissions', $data)) {
            return null;
        }

        return array_values(array_intersect($data['access_permissions'] ?? [], array_keys(config('access.permissions'))));
    }

    private function modules(array $data): ?array
    {
        if (! array_key_exists('access_modules', $data)) {
            return null;
        }

        return array_values(array_intersect($data['access_modules'] ?? [], array_keys(config('access.modules'))));
    }
}
