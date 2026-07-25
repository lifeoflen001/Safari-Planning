@extends('settings.layout')

@section('title', 'Account & Security | Safari Planner')
@section('page_title', 'Account & security')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Change Password</h2>
                <p>Update your password to keep your account secure.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.security.password') }}" class="settings-form security-password-form">
            @csrf
            <div class="settings-fields">
                <label>
                    <span class="settings-field-label">Current Password <span class="required">*</span></span>
                    <input type="password" name="current_password" required>
                </label>
                <label>
                    <span class="settings-field-label">New Password <span class="required">*</span></span>
                    <input type="password" name="password" required id="newPassword">
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <small id="strengthText">Enter a password</small>
                    </div>
                    <ul class="password-checklist">
                        <li id="check-length" class="check-item">At least 8 characters</li>
                        <li id="check-upper" class="check-item">One uppercase letter</li>
                        <li id="check-lower" class="check-item">One lowercase letter</li>
                        <li id="check-number" class="check-item">One number</li>
                        <li id="check-special" class="check-item">One special character</li>
                    </ul>
                </label>
                <label>
                    <span class="settings-field-label">Confirm New Password <span class="required">*</span></span>
                    <input type="password" name="password_confirmation" required>
                </label>
            </div>
            <div class="settings-form-actions">
                <button type="submit" class="button button-primary">Update password</button>
            </div>
        </form>
    </section>

    <section class="panel" style="margin-top:24px;">
        <div class="panel-heading">
            <div>
                <h2>Login History</h2>
                <p>Recent sign-in activity on your account.</p>
            </div>
        </div>
        <div class="booking-table wide">
            <div class="booking-row booking-head">
                <span>Date & time</span>
                <span>Device</span>
                <span>Browser</span>
                <span>IP address</span>
                <span>Location</span>
                <span>Status</span>
            </div>
            @forelse($loginHistory as $entry)
                <div class="booking-row">
                    <span>{{ $entry->logged_in_at->format('d M Y H:i') }}</span>
                    <span>{{ $entry->device ?: 'Unknown' }}</span>
                    <span>{{ $entry->browser ?: 'Unknown' }}</span>
                    <span>{{ $entry->ip_address ?: '—' }}</span>
                    <span>{{ $entry->location ?: '—' }}</span>
                    <span>
                        <span class="status-pill status-{{ $entry->success ? 'green' : 'rose' }}">
                            {{ $entry->success ? 'Success' : 'Failed' }}
                        </span>
                    </span>
                </div>
            @empty
                <div class="booking-row">
                    <span colspan="6">No login history available.</span>
                </div>
            @endforelse
        </div>
    </section>

    <section class="panel" style="margin-top:24px;">
        <div class="panel-heading">
            <div>
                <h2>Appearance</h2>
                <p>Choose how the planner looks for you.</p>
            </div>
        </div>
        <div class="settings-toggles">
            <div class="security-row">
                <div>
                    <strong>Theme</strong>
                    <small>Select your preferred display mode.</small>
                </div>
                <select id="themeSelect" class="settings-select">
                    <option value="beige">Safari Beige</option>
                    <option value="green">Safari Green</option>
                </select>
            </div>
        </div>
    </section>
</section>

@push('scripts')
<script>
(function() {
    const passwordInput = document.getElementById('newPassword');
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    const checks = {
        length: document.getElementById('check-length'),
        upper: document.getElementById('check-upper'),
        lower: document.getElementById('check-lower'),
        number: document.getElementById('check-number'),
        special: document.getElementById('check-special'),
    };

    function updateStrength() {
        const value = passwordInput.value;
        let met = 0;
        if (value.length >= 8) met++;
        if (/[A-Z]/.test(value)) met++;
        if (/[a-z]/.test(value)) met++;
        if (/[0-9]/.test(value)) met++;
        if (/[^A-Za-z0-9]/.test(value)) met++;

        checks.length.classList.toggle('check-met', value.length >= 8);
        checks.upper.classList.toggle('check-met', /[A-Z]/.test(value));
        checks.lower.classList.toggle('check-met', /[a-z]/.test(value));
        checks.number.classList.toggle('check-met', /[0-9]/.test(value));
        checks.special.classList.toggle('check-met', /[^A-Za-z0-9]/.test(value));

        const pct = (met / 5) * 100;
        fill.style.width = pct + '%';
        text.textContent = met === 0 ? 'Enter a password' : met <= 2 ? 'Weak' : met <= 4 ? 'Good' : 'Strong';
        fill.style.background = met <= 2 ? '#ef4444' : met <= 4 ? '#f59e0b' : '#10b981';
    }

    passwordInput.addEventListener('input', updateStrength);
    updateStrength();
})();
</script>
@endpush
@endsection
