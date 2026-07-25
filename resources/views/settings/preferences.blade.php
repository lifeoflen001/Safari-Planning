@extends('settings.layout')

@section('title', 'Preferences | Safari Planner')
@section('page_title', 'Preferences')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Preferences</h2>
                <p>Customize how dates, times and the calendar appear.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.preferences.update') }}" class="settings-form">
            @csrf
            <div class="settings-fields">
                <label>Timezone <span class="required">*</span>
                    <select name="timezone" required>
                        @foreach(timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}" {{ $user->timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Preferred Language <span class="required">*</span>
                    <select name="preferred_language" required>
                        <option value="en" {{ $user->preferred_language === 'en' ? 'selected' : '' }}>English</option>
                        <option value="sw" {{ $user->preferred_language === 'sw' ? 'selected' : '' }}>Swahili</option>
                        <option value="fr" {{ $user->preferred_language === 'fr' ? 'selected' : '' }}>French</option>
                        <option value="de" {{ $user->preferred_language === 'de' ? 'selected' : '' }}>German</option>
                    </select>
                </label>
                <label>Date Format <span class="required">*</span>
                    <select name="date_format" required>
                        <option value="d/m/Y" {{ in_array($settings->date_format ?? '', ['d/m/Y', 'DD MMM YYYY'], true) ? 'selected' : '' }}>DD/MM/YYYY</option>
                        <option value="m/d/Y" {{ ($settings->date_format ?? '') === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                        <option value="Y-m-d" {{ ($settings->date_format ?? '') === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                    </select>
                </label>
                <label>Time Format <span class="required">*</span>
                    <select name="time_format" required>
                        <option value="12h" {{ ($settings->time_format ?? '') === '12h' ? 'selected' : '' }}>12-hour clock</option>
                        <option value="24h" {{ ($settings->time_format ?? '') === '24h' ? 'selected' : '' }}>24-hour clock</option>
                    </select>
                </label>
                <label>Week Starts On <span class="required">*</span>
                    <select name="week_starts_on" required>
                        <option value="sunday" {{ ($settings->week_starts_on ?? '') === 'sunday' ? 'selected' : '' }}>Sunday</option>
                        <option value="monday" {{ ($settings->week_starts_on ?? '') === 'monday' ? 'selected' : '' }}>Monday</option>
                    </select>
                </label>
                <label>Default Calendar View <span class="required">*</span>
                    <select name="default_calendar_view" required>
                        <option value="day" {{ ($settings->default_calendar_view ?? '') === 'day' ? 'selected' : '' }}>Day</option>
                        <option value="week" {{ ($settings->default_calendar_view ?? '') === 'week' ? 'selected' : '' }}>Week</option>
                        <option value="month" {{ ($settings->default_calendar_view ?? '') === 'month' ? 'selected' : '' }}>Month</option>
                    </select>
                </label>
            </div>
            <div class="settings-form-actions">
                <button type="submit" class="button button-primary">Save preferences</button>
            </div>
        </form>
    </section>
</section>
@endsection
