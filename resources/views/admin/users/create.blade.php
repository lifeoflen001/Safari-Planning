@extends('settings.layout')

@section('title', 'Invite teammate | Safari Planner')

@php
    $selectedRole = old('role', 'viewer');
    $roleDefaults = config('access.role_permissions.' . $selectedRole, []);
    $selectedPermissions = old('access_permissions', $roleDefaults === '*' ? array_keys($permissions) : $roleDefaults);
    $selectedModules = old('access_modules', collect($modules)->filter(fn ($module) => in_array($module['permission'], $selectedPermissions, true))->keys()->all());
@endphp

@section('content')
<div class="access-page-header access-create-header"><div><div class="eyebrow">Administration / Team & access</div><h1>Invite teammate</h1><p class="lede">Create a secure invitation with a unique one-time password and least-privilege access.</p></div><a href="{{ route('settings.team') }}" class="button button-secondary">Back to team</a></div>
<form method="POST" action="{{ route('admin.users.store') }}" class="access-editor-grid access-create-form">@csrf
    <section class="panel access-editor-main"><div class="panel-heading"><div><h2>Profile details</h2><p>The invitation email will use these details.</p></div></div><div class="settings-fields"><label>Full name<input type="text" name="name" value="{{ old('name') }}" required></label><label>Work email<input type="email" name="email" value="{{ old('email') }}" required></label><label>Job title<input type="text" name="job_title" value="{{ old('job_title') }}"></label><label>Phone<input type="text" name="phone" value="{{ old('phone') }}"></label><label>Timezone<input type="text" name="timezone" value="{{ old('timezone', 'Africa/Dar_es_Salaam') }}"></label><label>Record visibility<select name="record_scope"><option value="workspace">Workspace records</option><option value="assigned">Assigned records only</option><option value="own">Own records only</option></select></label></div></section>
    <section class="panel access-editor-main"><div class="panel-heading"><div><h2>Role & access</h2><p>Role presets are a safe baseline. Add or remove granular permissions below.</p></div></div><div class="settings-fields access-role-fields"><label>Role<select name="role" required>@foreach($roles as $key => $label)<option value="{{ $key }}" {{ $selectedRole === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></label></div><div class="permission-check-grid">@foreach($permissions as $key => $permission)<label class="permission-check"><input type="checkbox" name="access_permissions[]" value="{{ $key }}" {{ in_array($key, $selectedPermissions, true) ? 'checked' : '' }}><span><strong>{{ $permission['label'] }}</strong><small>{{ $permission['group'] }}</small></span></label>@endforeach</div></section>
    <section class="panel access-editor-main"><div class="panel-heading"><div><h2>Visible modules</h2><p>A permission is required before a module can be shown.</p></div></div><div class="module-check-grid">@foreach($modules as $key => $module)<label class="module-check"><input type="checkbox" name="access_modules[]" value="{{ $key }}" {{ in_array($key, $selectedModules, true) ? 'checked' : '' }}><span>{{ $module['label'] }}</span></label>@endforeach</div><div class="access-security-callout"><span>✦</span><div><strong>Secure invitation workflow</strong><small>A unique temporary password is generated, hashed, expires after {{ config('access.temporary_password_ttl_hours') }} hours and is invalidated after the first password change.</small></div></div>@if(config('mail.default') === 'log')<div class="access-mail-mode-note"><strong>Local email mode</strong><small>Invitation emails are currently written to <code>storage/logs/laravel.log</code>. Configure SMTP in <code>.env</code> for real delivery.</small></div>@endif</section>
    <div class="access-editor-actions"><a href="{{ route('settings.team') }}" class="button button-secondary">Cancel</a><button type="submit" class="button button-primary">Send secure invitation</button></div>
</form>
@endsection
