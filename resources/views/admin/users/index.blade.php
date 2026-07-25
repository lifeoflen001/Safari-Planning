@extends('layouts.app')

@section('title', 'Team & access | Safari Planner')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">Administration / Team & access</div>
        <h1>Team & access</h1>
        <p class="lede">Manage teammates and their access levels.</p>
    </div>
    <div class="page-action">
        <a href="{{ route('admin.users.create') }}" class="button button-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Invite teammate
        </a>
    </div>
</div>

<section class="panel team-panel">
    <div class="panel-heading">
        <div>
            <h2>People with planner access</h2>
            <p>{{ $users->total() }} teammates · profiles and permissions</p>
        </div>
    </div>
    <div class="booking-table wide">
        <div class="booking-row booking-head">
            <span>Profile</span>
            <span>Role</span>
            <span>Job title</span>
            <span>Email</span>
            <span>Status</span>
            <span></span>
        </div>
        @foreach($users as $member)
            <div class="booking-row">
                <span>
                    <strong>{{ $member->name }}</strong>
                    <small>{{ $member->timezone ?: 'Timezone not set' }}</small>
                </span>
                <span>{{ ucfirst($member->role) }}</span>
                <span>{{ $member->job_title ?: 'Not specified' }}</span>
                <span>{{ $member->email }}</span>
                <span>
                    <span class="status-pill status-{{ $member->is_active ? 'green' : 'rose' }}">
                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </span>
                <span>
                    <a href="{{ route('admin.users.edit', $member) }}" class="icon-button" aria-label="Edit user">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 21h16M5 10l7-7 7 7M5 10v11h16V10"/>
                        </svg>
                    </a>
                </span>
            </div>
        @endforeach
    </div>
    <div style="padding: 16px 20px;">
        {{ $users->links() }}
    </div>
</section>
@endsection