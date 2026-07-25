@extends('settings.layout')

@section('title', 'Personal Information | Safari Planner')
@section('page_title', 'Personal information')

@section('content')
<section class="settings-layout-single">
    <section class="panel personal-information-panel">
        <div class="panel-heading">
            <div>
                <h2>Personal Information</h2>
                <p>Update your personal details and contact information.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.personal.update') }}" class="settings-form personal-information-form">
            @csrf
            <div class="settings-fields">
                <label>First Name <span class="required">*</span>
                    <input type="text" name="first_name" value="{{ old('first_name', explode(' ', $user->name)[0] ?? '') }}" required>
                </label>
                <label>Middle Name
                    <input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
                </label>
                <label>Last Name <span class="required">*</span>
                    <input type="text" name="last_name" value="{{ old('last_name', collect(explode(' ', $user->name))->slice(-1)->first() ?? '') }}" required>
                </label>
                <label>Preferred Name
                    <input type="text" name="preferred_name" value="{{ old('preferred_name', $user->preferred_name) }}">
                </label>
                <label>Job Title
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}">
                </label>
                <label>Department
                    <input type="text" name="department" value="{{ old('department', $user->department) }}">
                </label>
                <label>Email Address <span class="required">*</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    <small>Changing your email will require confirmation.</small>
                </label>
                <label>Phone Number
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                </label>
                <label>Alternative Phone Number
                    <input type="text" name="alternative_phone" value="{{ old('alternative_phone', $user->alternative_phone) }}">
                </label>
                <label>Preferred Language <span class="required">*</span>
                    <select name="preferred_language" required>
                        <option value="en" {{ old('preferred_language', $user->preferred_language) === 'en' ? 'selected' : '' }}>English</option>
                        <option value="sw" {{ old('preferred_language', $user->preferred_language) === 'sw' ? 'selected' : '' }}>Swahili</option>
                        <option value="fr" {{ old('preferred_language', $user->preferred_language) === 'fr' ? 'selected' : '' }}>French</option>
                        <option value="de" {{ old('preferred_language', $user->preferred_language) === 'de' ? 'selected' : '' }}>German</option>
                    </select>
                </label>
            </div>
            <div class="settings-form-actions">
                <a href="{{ route('settings.profile') }}" class="button button-secondary">Cancel</a>
                <button type="submit" class="button button-primary">Save changes</button>
            </div>
        </form>
    </section>
</section>
@endsection
