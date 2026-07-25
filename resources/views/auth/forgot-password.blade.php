@extends('layouts.auth')

@section('title', 'Reset password · Tanzania Specialist')
@section('bodyClass', 'auth-page-forgot-password')

@section('content')
    <div class="auth-form-header">
        <h1>Reset Password</h1>
        <p>Enter your email address and we’ll send you a link to reset your password.</p>
    </div>

    @if(session('status'))
        <div class="auth-alert auth-success" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group"><input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter your email address" required autofocus autocomplete="email"></div>
        </div>
        <button class="auth-submit-btn" type="submit">Send Reset Link</button>
    </form>
    <div class="auth-signup-link"><a href="{{ route('login') }}">← Back to sign in</a></div>
@endsection
