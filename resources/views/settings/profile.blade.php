@extends('settings.layout')

@section('title', 'My Profile | Safari Planner')
@section('page_title', 'My profile')

@section('content')
<section class="profile-layout">
    <section class="panel">
        <div class="profile-cover">
            <div class="profile-avatar-large" id="profileAvatarPreview">
                @if($user->avatar_path)
                    <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="profile-avatar-img">
                @else
                    {{ collect(preg_split('/\s+/', trim($user->name)))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') }}
                @endif
            </div>
            <div class="profile-cover-copy">
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->job_title ?: 'Planner' }}</p>
                <form class="profile-avatar-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    <label class="profile-avatar-upload" for="avatarInput">Change photo</label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
                    <small class="profile-avatar-help">JPG, PNG or WebP · max 2 MB</small>
                    <span class="profile-avatar-error" id="avatarError" role="alert"></span>
                </form>
            </div>
            <div class="profile-cover-badges">
                <span class="status-pill status-{{ $user->is_active ? 'green' : 'rose' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="status-pill status-blue">{{ ucfirst($user->role) }}</span>
            </div>
        </div>
        <div class="profile-actions">
            <a class="button button-primary" href="{{ route('settings.personal') }}">Edit personal details</a>
            <a class="button button-secondary" href="{{ route('settings.security') }}">Account security</a>
        </div>
        <div class="profile-meta-grid">
            <div class="profile-meta-item">
                <span class="profile-meta-label">Email</span>
                <span class="profile-meta-value">{{ $user->email }}</span>
            </div>
            <div class="profile-meta-item">
                <span class="profile-meta-label">Phone</span>
                <span class="profile-meta-value">{{ $user->phone ?: 'Not set' }}</span>
            </div>
            <div class="profile-meta-item">
                <span class="profile-meta-label">Department</span>
                <span class="profile-meta-value">{{ $user->department ?: 'Not set' }}</span>
            </div>
            <div class="profile-meta-item">
                <span class="profile-meta-label">Timezone</span>
                <span class="profile-meta-value">{{ $user->timezone ?: 'Not set' }}</span>
            </div>
            <div class="profile-meta-item">
                <span class="profile-meta-label">Company</span>
                <span class="profile-meta-value">{{ $company?->name ?? 'Not assigned' }}</span>
            </div>
            <div class="profile-meta-item">
                <span class="profile-meta-label">Last active</span>
                <span class="profile-meta-value">{{ $user->last_active_at?->diffForHumans() ?? 'Just now' }}</span>
            </div>
        </div>
    </section>
    <aside class="profile-side">
        <div class="panel">
            <div class="profile-side-icon">✓</div>
            <h3>Access level</h3>
            <p>Your current role controls what you can edit, comment on and view in the planner.</p>
            <div class="profile-permission-list">
                <span><i class="allowed-dot"></i> View planning board</span>
                <span><i class="allowed-dot"></i> View operational reports</span>
                @if($user->canEditPlanner())
                    <span><i class="allowed-dot"></i> Edit bookings and resources</span>
                @endif
                @if($user->canCommentPlanner())
                    <span><i class="allowed-dot"></i> Comment on plans</span>
                @endif
                @if($user->isAdmin())
                    <span><i class="allowed-dot"></i> Manage team and settings</span>
                @endif
            </div>
        </div>
        <div class="panel">
            <div class="profile-side-icon">◷</div>
            <h3>Recent activity</h3>
            <div class="profile-activity-list">
                @forelse($loginHistory as $entry)
                    <div class="profile-activity-item">
                        <div>
                            <strong>{{ $entry->browser ?? 'Unknown browser' }}</strong>
                            <small>{{ $entry->device ?? 'Unknown device' }}</small>
                        </div>
                        <span class="status-pill status-{{ $entry->success ? 'green' : 'rose' }}">
                            {{ $entry->success ? 'Success' : 'Failed' }}
                        </span>
                    </div>
                @empty
                    <p>No recent login activity.</p>
                @endforelse
            </div>
        </div>
    </aside>
</section>
@endsection
