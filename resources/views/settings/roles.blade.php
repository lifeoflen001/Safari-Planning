@extends('settings.layout')

@section('title', 'Roles & Permissions | Safari Planner')

@section('content')
<section class="settings-layout-single">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Roles & Permissions</h2>
                <p>Understand what each role can do in the planner.</p>
            </div>
        </div>
        <div class="booking-table wide">
            <div class="booking-row booking-head">
                <span>Role</span>
                <span>View</span>
                <span>Edit</span>
                <span>Comment</span>
                <span>Manage users</span>
                <span>Export / Import</span>
            </div>
            @foreach($roles as $key => $label)
                @php $u = new \App\Models\User(['role' => $key]); @endphp
                <div class="booking-row">
                    <span><strong>{{ $label }}</strong></span>
                    <span>{{ $u->canViewPlanner() ? '✓' : '—' }}</span>
                    <span>{{ $u->canEditPlanner() ? '✓' : '—' }}</span>
                    <span>{{ $u->canCommentPlanner() ? '✓' : '—' }}</span>
                    <span>{{ $u->canManageUsers() ? '✓' : '—' }}</span>
                    <span>{{ $u->canExport() ? '✓' : '—' }}</span>
                </div>
            @endforeach
        </div>
    </section>
</section>
@endsection