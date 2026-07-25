@extends('layouts.auth')

@section('title', 'Set new password · Tanzania Specialist')
@section('bodyClass', 'auth-page-reset-password')

@section('content')
    <div class="auth-form-header">
        <h1>Set New Password</h1>
        <p>Choose a strong password for your operations desk.</p>
    </div>

    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group"><input type="email" name="email" id="email" value="{{ $email ?? old('email') }}" placeholder="Enter your email address" required autocomplete="email"></div>
        </div>
        <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Enter your new password" required autocomplete="new-password">
                <button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('password', this)" aria-label="Show password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm your new password" required autocomplete="new-password">
                <button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('password_confirmation', this)" aria-label="Show password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <button class="auth-submit-btn" type="submit">Reset Password</button>
    </form>
    <div class="auth-signup-link"><a href="{{ route('login') }}">← Back to sign in</a></div>
@endsection

@push('scripts')
    <script>
        function toggleAuthPassword(id, button) {
            const input = document.getElementById(id);
            if (!input) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button?.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        }
    </script>
@endpush
