@extends('layouts.auth')

@section('title', 'Invitation unavailable · Tanzania Specialist')
@section('bodyClass', 'auth-page-invitation-invalid')

@section('content')
    <div class="auth-form-header">
        <h1>Invitation unavailable</h1>
        <p>This invitation has expired, already been used, or was revoked by your workspace administrator.</p>
    </div>

    <div class="auth-alert auth-error" role="alert">Request a new invitation to continue.</div>
    <a class="auth-submit-btn" href="{{ route('login') }}">Go to Sign In</a>
@endsection
