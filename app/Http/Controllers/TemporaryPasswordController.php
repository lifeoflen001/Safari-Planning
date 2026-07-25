<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TemporaryPasswordController extends Controller
{
    public function edit()
    {
        return view('auth.temporary-password', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed', 'min:8'],
        ]);
        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'temporary_password_used_at' => now(),
            'temporary_password_expires_at' => null,
            'temporary_password_attempts' => 0,
            'temporary_password_locked_at' => null,
        ]);
        $invitation = $user->invitations()->whereNull('invitation_accepted_at')->whereNull('revoked_at')->latest()->first();
        $invitation?->update(['invitation_accepted_at' => now()]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user.temporary_password_changed',
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'changes' => ['invitation_id' => $invitation?->id],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }
}
