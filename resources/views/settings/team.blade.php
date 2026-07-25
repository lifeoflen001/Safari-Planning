@extends('settings.layout')

@section('title', 'Team & access | Safari Planner')

@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
    $admin = auth()->user();
@endphp

@section('content')
<div class="access-page-header">
    <div>
        <div class="eyebrow">Administration / Team & access</div>
        <h1>Team & access</h1>
        <p class="lede">Give every teammate only the modules, records and actions their job requires.</p>
    </div>
    @if($admin->canManageUsers())
        <a href="{{ route('admin.users.create') }}" class="button button-primary">＋ Invite teammate</a>
    @endif
</div>

<section class="access-callout">
    <div class="access-callout-icon">✓</div>
    <div><strong>Permission enforcement is active</strong><p>Modules are hidden until assigned, and server-side permission checks protect every page and action.</p></div>
    <span class="status-pill status-green">Protected</span>
</section>

<section class="panel access-summary-grid">
    <div><span class="access-summary-label">Workspace members</span><strong>{{ $users->total() }}</strong><small>People with an account</small></div>
    <div><span class="access-summary-label">Pending invitations</span><strong>{{ $pendingInvitations->count() }}</strong><small>Waiting for activation</small></div>
    <div><span class="access-summary-label">Available permissions</span><strong>{{ count($permissions) }}</strong><small>Granular controls</small></div>
    <div><span class="access-summary-label">Session policy</span><strong>Revocable</strong><small>Admins can end access instantly</small></div>
</section>

<section class="panel team-access-panel">
    <div class="panel-heading access-table-heading">
        <div><h2>People with planner access</h2><p>Review roles, modules, scope and account state in one place.</p></div>
        <div class="access-table-tools"><span class="access-legend"><i class="access-legend-dot access-legend-active"></i>Active <i class="access-legend-dot access-legend-pending"></i>Pending <i class="access-legend-dot access-legend-suspended"></i>Suspended</span></div>
    </div>
    <div class="access-table-wrap">
        <table class="access-table">
            <colgroup><col class="access-col-profile"><col class="access-col-job"><col class="access-col-role"><col class="access-col-level"><col class="access-col-modules"><col class="access-col-email"><col class="access-col-status"><col class="access-col-login"><col class="access-col-actions"></colgroup>
            <thead><tr><th>Profile</th><th>Job title</th><th>Role</th><th>Access level</th><th>Assigned modules</th><th>Email</th><th>Account status</th><th>Last login</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($users as $member)
                @php
                    $invitation = $member->invitations->first();
                    $isPending = $member->must_change_password && $invitation && ! $invitation->invitation_accepted_at && ! $invitation->revoked_at;
                    $moduleLabels = $member->assignedModuleLabels();
                @endphp
                <tr>
                    <td><div class="access-profile">@if($member->avatar_path)<img src="{{ asset('storage/' . $member->avatar_path) }}" alt="{{ $member->name }}" class="avatar avatar-image avatar-small">@else<div class="avatar avatar-purple avatar-small">{{ $initials($member->name) }}</div>@endif<div><strong>{{ $member->name }}</strong><small>{{ $member->department ?: 'Safari operations' }}</small></div></div></td>
                    <td>{{ $member->job_title ?: 'Not specified' }}</td>
                    <td><span class="role-badge role-{{ $member->role }}">{{ $roles[$member->role] ?? ucfirst($member->role) }}</span></td>
                    <td><div class="access-level-cell"><strong>{{ $member->access_permissions === null ? 'Role default' : 'Custom access' }}</strong><small>{{ count($member->effectivePermissions()) }} permissions · {{ ucfirst($member->record_scope ?: 'workspace') }} records</small></div></td>
                    <td><div class="module-chips">@forelse(array_slice($moduleLabels, 0, 2) as $label)<span>{{ $label }}</span>@empty<span class="muted-chip">No modules</span>@endforelse @if(count($moduleLabels) > 2)<span class="more-chip">+{{ count($moduleLabels) - 2 }}</span>@endif</div></td>
                    <td><a class="access-email" href="mailto:{{ $member->email }}">{{ $member->email }}</a></td>
                    <td><span class="status-pill status-{{ ! $member->is_active ? 'rose' : ($isPending ? 'gold' : 'green') }}">{{ ! $member->is_active ? 'Suspended' : ($isPending ? 'Invitation pending' : 'Active') }}</span></td>
                    <td><span class="access-last-login">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</span></td>
                    <td><div class="access-actions"><a href="{{ route('admin.users.edit', $member) }}" class="button button-secondary button-small">Manage</a><details class="access-action-menu"><summary aria-label="More actions">•••</summary><div class="access-action-popover">
                        @if($admin->canManageUsers())
                            <form method="POST" action="{{ route('admin.users.toggle-status', $member) }}">@csrf<button type="submit">{{ $member->is_active ? 'Suspend user' : 'Reactivate user' }}</button></form>
                            <form method="POST" action="{{ route('admin.users.revoke-sessions', $member) }}">@csrf<button type="submit">Revoke sessions</button></form>
                            @if($isPending)<form method="POST" action="{{ route('admin.users.resend-invitation', $member) }}">@csrf<button type="submit">Resend invitation</button></form>@endif
                        @endif
                    </div></details></div></td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty-state"><h2>No team members yet</h2><p>Invite your first teammate to start assigning access.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="access-pagination">{{ $users->links() }}</div>
</section>

<section class="access-bottom-grid">
    <div class="panel access-policy-card"><div class="panel-heading"><div><h2>Access principles</h2><p>Simple controls for safer operations.</p></div></div><div class="access-policy-list"><div><span>01</span><div><strong>Least privilege</strong><small>Every permission is explicit and auditable.</small></div></div><div><span>02</span><div><strong>Scoped records</strong><small>Limit users to workspace, assigned or own records.</small></div></div><div><span>03</span><div><strong>Instant offboarding</strong><small>Suspension also revokes all active sessions.</small></div></div></div></div>
    <div class="panel access-audit-card"><div class="panel-heading"><div><h2>Access history</h2><p>Permission changes are recorded with actor and timestamp.</p></div></div><a href="{{ route('settings.roles') }}" class="profile-link">Review roles & permissions →</a><div class="access-audit-note"><span>⌁</span><div><strong>Audit-ready</strong><small>Open a teammate's access settings to review their recent history.</small></div></div></div>
</section>
@push('scripts')
<script>
(function () {
    const menus = Array.from(document.querySelectorAll('.access-action-menu'));

    function positionMenu(menu) {
        if (!menu.open) return;
        const popover = menu.querySelector('.access-action-popover');
        const summary = menu.querySelector('summary');
        if (!popover || !summary) return;

        popover.classList.add('is-floating');
        const summaryRect = summary.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        const gap = 6;
        const left = Math.max(8, Math.min(summaryRect.right - popoverRect.width, window.innerWidth - popoverRect.width - 8));
        const belowTop = summaryRect.bottom + gap;
        const top = belowTop + popoverRect.height <= window.innerHeight - 8
            ? belowTop
            : Math.max(8, summaryRect.top - popoverRect.height - gap);

        popover.style.left = `${left}px`;
        popover.style.top = `${top}px`;
    }

    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            const popover = menu.querySelector('.access-action-popover');
            if (!menu.open) {
                popover?.classList.remove('is-floating');
                if (popover) {
                    popover.style.left = '';
                    popover.style.top = '';
                }
                return;
            }

            menus.forEach((other) => {
                if (other !== menu) other.removeAttribute('open');
            });
            positionMenu(menu);
        });
    });

    window.addEventListener('resize', () => menus.forEach(positionMenu));
    document.querySelector('.access-table-wrap')?.addEventListener('scroll', () => menus.forEach(positionMenu), { passive: true });
})();
</script>
@endpush
@endsection
