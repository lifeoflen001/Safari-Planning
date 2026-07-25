@extends('layouts.auth')

@section('title', 'Set your password · Tanzania Specialist')
@section('bodyClass', 'auth-page-temporary-password')

@section('content')
    <div class="auth-form-header">
        <h1>Create Your Password</h1>
        <p>Your temporary password is valid once. Choose a private password before continuing.</p>
    </div>

    @if($errors->any())
        <div class="auth-alert auth-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('temporary-password.update') }}" class="auth-form">
        @csrf
        <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-group"><input type="password" name="password" id="password" placeholder="Create a new password" required autocomplete="new-password"></div>
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <div class="input-group"><input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm your new password" required autocomplete="new-password"></div>
        </div>
        <button class="auth-submit-btn" type="submit">Save Password</button>
    </form>
@endsection
