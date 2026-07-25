<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['settings' => UserSetting::firstOrCreate(['user_id' => $request->user()->id])]);
    }

    public function update(Request $request)
    {
        $settings = UserSetting::firstOrCreate(['user_id' => $request->user()->id]);
        $settings->update($request->validate([
            'date_format' => ['nullable', 'string'],
            'time_format' => ['nullable', 'string'],
            'week_starts_on' => ['nullable', 'string'],
            'default_calendar_view' => ['nullable', 'string'],
            'email_notifications' => ['nullable', 'boolean'],
            'conflict_alerts' => ['nullable', 'boolean'],
        ]));

        return response()->json(['settings' => $settings->fresh()]);
    }

    public function personalIndex()
    {
        return view('settings.personal', [
            'user' => auth()->user(),
        ]);
    }

    public function personalUpdate(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'alternative_phone' => ['nullable', 'string', 'max:40'],
            'preferred_language' => ['required', 'string', 'max:10'],
        ]);

        $nameChanged = $validated['email'] !== $user->email;
        $validated['middle_name'] = trim((string) ($validated['middle_name'] ?? '')) ?: null;
        $validated['name'] = trim(($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']));
        unset($validated['first_name'], $validated['last_name']);

        $user->update($validated);

        return redirect()->route('settings.personal')->with('success', $nameChanged ? 'Profile updated. Please confirm your new email address.' : 'Profile updated successfully.');
    }

    public function securityIndex()
    {
        return view('settings.security', [
            'user' => auth()->user(),
            'loginHistory' => auth()->user()->loginHistories()->latest('logged_in_at')->limit(20)->get(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function profileIndex()
    {
        return view('settings.profile', [
            'user' => auth()->user(),
            'company' => auth()->user()->company,
            'loginHistory' => auth()->user()->loginHistories()->latest('logged_in_at')->limit(5)->get(),
        ]);
    }

    public function notificationsIndex()
    {
        $settings = UserSetting::firstOrCreate(['user_id' => auth()->id()]);

        return view('settings.notifications', compact('settings'));
    }

    public function notificationsUpdate(Request $request)
    {
        $settings = UserSetting::firstOrCreate(['user_id' => auth()->id()]);
        $validated = $request->validate([
            'email_notifications' => ['sometimes', 'boolean'],
            'conflict_alerts' => ['sometimes', 'boolean'],
            'assignment_alerts' => ['sometimes', 'boolean'],
            'payment_alerts' => ['sometimes', 'boolean'],
        ]);

        $settings->update([
            'email_notifications' => $request->boolean('email_notifications', $settings->email_notifications),
            'conflict_alerts' => $request->boolean('conflict_alerts', $settings->conflict_alerts),
            'assignment_updates' => $request->boolean('assignment_alerts', $settings->assignment_updates),
            'payment_reminders' => $request->boolean('payment_alerts', $settings->payment_reminders),
        ]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function preferencesIndex()
    {
        return view('settings.preferences', [
            'user' => auth()->user(),
            'settings' => UserSetting::firstOrCreate(['user_id' => auth()->id()]),
        ]);
    }

    public function preferencesUpdate(Request $request)
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone'],
            'preferred_language' => ['required', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', Rule::in(['12h', '24h'])],
            'week_starts_on' => ['required', Rule::in(['sunday', 'monday'])],
            'default_calendar_view' => ['required', Rule::in(['day', 'week', 'month'])],
        ]);

        $request->user()->update([
            'timezone' => $validated['timezone'],
            'preferred_language' => $validated['preferred_language'],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => $request->user()->id]);
        $settings->update([
            'date_format' => $validated['date_format'],
            'time_format' => $validated['time_format'],
            'week_starts_on' => $validated['week_starts_on'],
            'default_calendar_view' => $validated['default_calendar_view'],
        ]);

        return back()->with('success', 'Preferences saved.');
    }

    public function sessionsIndex()
    {
        return view('settings.sessions', [
            'loginHistory' => auth()->user()->loginHistories()->latest('logged_in_at')->paginate(20),
        ]);
    }

    public function workspaceIndex()
    {
        return view('settings.workspace', [
            'company' => auth()->user()->company,
        ]);
    }

    public function workspaceUpdate(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('companies', 'name')->ignore($company?->id)],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'website' => ['nullable', 'url', 'max:2048'],
            'country' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $company) {
            $company = Company::create($validated);
            $user->update(['company_id' => $company->id]);
        } else {
            $company->update($validated);
        }

        return redirect()->route('settings.workspace')->with('success', 'Workspace profile saved successfully.');
    }

    public function businessIndex()
    {
        return view('settings.business', [
            'company' => auth()->user()->company,
        ]);
    }

    public function teamIndex()
    {
        $user = auth()->user();
        $query = User::with(['invitations' => fn ($query) => $query->latest()->limit(1)])->orderBy('name');
        if ($user->company_id !== null) {
            $query->where('company_id', $user->company_id);
        } else {
            $query->whereNull('company_id');
        }

        return view('settings.team', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => User::ROLES,
            'permissions' => config('access.permissions'),
            'modules' => config('access.modules'),
            'pendingInvitations' => \App\Models\UserInvitation::whereNull('invitation_accepted_at')->whereNull('revoked_at')->latest()->limit(10)->get(),
        ]);
    }

    public function rolesIndex()
    {
        return view('settings.roles', [
            'roles' => User::ROLES,
            'users' => User::all(),
        ]);
    }

    public function transfersIndex()
    {
        return view('settings.transfers');
    }

    public function notificationTemplatesIndex()
    {
        return view('settings.notification-templates');
    }

    public function dataPrivacyIndex()
    {
        return view('settings.data-privacy');
    }
}


