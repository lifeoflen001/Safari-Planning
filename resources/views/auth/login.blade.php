@extends('layouts.auth')

@section('title', 'Sign in · Tanzania Specialist')
@section('bodyClass', 'auth-page-login')

@section('content')
    <div class="auth-form-header">
        <h1>Login</h1>
    </div>

    @if(session('status'))
        <div class="auth-alert auth-success" role="status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group">
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter your email" required autofocus autocomplete="email">
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('password', this)" aria-label="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <div class="forgot-password"><a href="{{ route('password.request') }}">Forgot password?</a></div>
        </div>

        <button type="submit" class="auth-submit-btn">Sign In</button>
    </form>
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
