@extends('settings.layout')

@section('title', 'Notifications | Safari Planner')
@section('page_title', 'Notifications')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Notification Preferences</h2>
                <p>Choose what updates you receive and how.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.notifications.update') }}" class="settings-form">
            @csrf
            <div class="settings-toggles">
                <div class="settings-toggle">
                    <div>
                        <strong>Email notifications</strong>
                        <small>Receive important operational updates by email.</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="email_notifications" value="1" {{ $settings->email_notifications ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-toggle">
                    <div>
                        <strong>Conflict alerts</strong>
                        <small>Show resource overlap and capacity warnings.</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="conflict_alerts" value="1" {{ $settings->conflict_alerts ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-toggle">
                    <div>
                        <strong>Assignment alerts</strong>
                        <small>Notify when guides or vehicles are assigned.</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="assignment_alerts" value="1" {{ $settings->assignment_updates ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-toggle">
                    <div>
                        <strong>Payment alerts</strong>
                        <small>Notify on payment status changes.</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="payment_alerts" value="1" {{ $settings->payment_reminders ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            <div class="settings-form-actions">
                <button type="submit" class="button button-primary">Save preferences</button>
            </div>
        </form>
    </section>
</section>
@endsection
