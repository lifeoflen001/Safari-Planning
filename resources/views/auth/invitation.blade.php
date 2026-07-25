@extends('layouts.auth')

@section('title', 'Activate your account · Tanzania Specialist')
@section('bodyClass', 'auth-page-invitation')

@section('content')
    <div class="auth-form-header">
        <h1>Activate account</h1>
        <p>You have been invited to Tanzania Specialist. Create your private password to continue.</p>
    </div>

    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="auth-invitation-note">
        <span>Secure invitation</span>
        <strong>Expires {{ $invitation->expires_at->format('d M Y, H:i T') }}</strong>
    </div>

    <form method="POST" action="{{ route('invitation.accept', ['token' => $token]) }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group"><input type="email" name="email" id="email" value="{{ old('email', $invitation->email) }}" placeholder="Enter your email address" required autofocus autocomplete="email"></div>
        </div>

        <div class="form-group">
            <label for="temporary_password">Temporary Password</label>
            <div class="input-group">
                <input type="password" name="temporary_password" id="temporary_password" placeholder="Enter your temporary password" required autocomplete="one-time-code">
                <button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('temporary_password', this)" aria-label="Show temporary password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-group"><input type="password" name="password" id="password" placeholder="Create a new password" required autocomplete="new-password"></div>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <div class="input-group"><input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repeat your new password" required autocomplete="new-password"></div>
        </div>

        <button type="submit" class="auth-submit-btn">Activate Account</button>
    </form>

    <div class="auth-signup-link">Already have access? <a href="{{ route('login') }}">Sign in</a></div>
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
