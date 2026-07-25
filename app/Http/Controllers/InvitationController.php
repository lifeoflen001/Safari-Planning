<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\UserInvitation;
use App\Services\SessionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->findInvitation($token);
        if (! $invitation || ! $invitation->isValid() || $invitation->user->isTemporaryPasswordExpired()) {
            return view('auth.invitation-invalid');
        }

        $invitation->update(['invitation_opened_at' => $invitation->invitation_opened_at ?: now()]);

        return view('auth.invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token, SessionRegistry $sessions)
    {
        $invitation = $this->findInvitation($token);
        if (! $invitation || ! $invitation->isValid()) {
            return back()->withErrors(['email' => 'This invitation is invalid, expired, or has been revoked.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'temporary_password' => ['required', 'string'],
            'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed', 'min:8'],
        ]);
        $user = $invitation->user;

        if (strcasecmp($validated['email'], $user->email) !== 0) {
            return back()->withErrors(['email' => 'The invitation details do not match.']);
        }

        if ($user->temporary_password_locked_at || $user->temporary_password_attempts >= config('access.temporary_password_max_attempts')) {
            return back()->withErrors(['temporary_password' => 'This temporary password is locked. Ask an administrator to resend the invitation.']);
        }

        if (! Hash::check($validated['temporary_password'], $user->password)) {
            $user->increment('temporary_password_attempts');
            if ($user->fresh()->temporary_password_attempts >= config('access.temporary_password_max_attempts')) {
                $user->update(['temporary_password_locked_at' => now()]);
            }

            return back()->withErrors(['temporary_password' => 'The temporary password is incorrect.']);
        }

        $this->completePasswordChange($user, $invitation, $validated['password']);
        Auth::login($user);
        $request->session()->regenerate();
        $sessions->register($request, $user);
        $this->logLogin($request, $user);

        return redirect()->route('dashboard')->with('success', 'Your account is ready. Welcome to Safari Planner.');
    }

    private function findInvitation(string $token): ?UserInvitation
    {
        return UserInvitation::with('user')->where('token_hash', hash('sha256', $token))->first();
    }

    private function completePasswordChange($user, UserInvitation $invitation, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => false,
            'temporary_password_used_at' => now(),
            'temporary_password_expires_at' => null,
            'temporary_password_attempts' => 0,
            'temporary_password_locked_at' => null,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);
        $invitation->update(['invitation_accepted_at' => now()]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.invitation_accepted',
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'changes' => ['invitation_id' => $invitation->id],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    private function logLogin(Request $request, $user): void
    {
        $user->update(['last_login_at' => now(), 'last_active_at' => now()]);
        $user->loginHistories()->create([
            'logged_in_at' => now(), 'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000), 'success' => true,
        ]);
    }
}
