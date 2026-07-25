@extends('layouts.auth')

@section('title', 'Create account · Tanzania Specialist')
@section('bodyClass', 'auth-page-register')

@section('content')
    <div class="auth-form-header">
        <h1>Create Account</h1>
        <p>Get started with your operations desk.</p>
    </div>

    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">
            <div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <div class="input-group"><input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" placeholder="Enter first name" required autofocus autocomplete="given-name"></div>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <div class="input-group"><input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" placeholder="Enter last name" required autocomplete="family-name"></div>
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group"><input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter email address" required autocomplete="email"></div>
        </div>
        <div class="form-group">
            <label for="job_title">Job Title</label>
            <div class="input-group"><input type="text" name="job_title" id="job_title" value="{{ old('job_title') }}" placeholder="e.g. Operations manager"></div>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group"><input type="password" name="password" id="password" placeholder="Create a password" required autocomplete="new-password"><button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('password', this)" aria-label="Show password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <div class="input-group"><input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm your password" required autocomplete="new-password"><button type="button" class="password-toggle-btn" onclick="toggleAuthPassword('password_confirmation', this)" aria-label="Show password"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
        </div>
        <div class="form-options">
            <label class="remember-me"><input type="checkbox" name="terms" value="1" required><span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span></label>
        </div>
        <button type="submit" class="auth-submit-btn">Create Account</button>
    </form>
    <div class="auth-signup-link">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
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
