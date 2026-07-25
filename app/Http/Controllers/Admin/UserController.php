<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\AccessManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('settings.team');
    }

    public function create()
    {
        return view('admin.users.create', $this->accessData());
    }

    public function store(Request $request, AccessManagementService $access)
    {
        $validated = $this->validated($request, false);
        $result = $access->invite($request->user(), $validated);

        $redirect = redirect()->route('admin.users.edit', $result['user']);

        if ($result['deliveryError']) {
            return $redirect
                ->with('error', 'Invitation created, but email delivery failed. Review the delivery status and resend it.')
                ->with('invitation_link', $result['invitationLink']);
        }

        return $redirect
            ->with('success', 'Invitation sent successfully. Copy the secure link below if you need to share it manually.')
            ->with('invitation_link', $result['invitationLink']);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', array_merge(['user' => $user, 'history' => $this->history($user), 'invitation' => $user->invitations()->latest()->first()], $this->accessData()));
    }

    public function update(Request $request, User $user, AccessManagementService $access)
    {
        $validated = $this->validated($request, true);
        $access->update($request->user(), $user, $validated);

        return redirect()->route('settings.team')->with('success', 'Access settings updated and recorded in the audit history.');
    }

    public function resendInvitation(User $user, Request $request, AccessManagementService $access)
    {
        $result = $access->rotateInvitation($request->user(), $user);

        return back()->with($result['deliveryError'] ? 'error' : 'success', $result['deliveryError']
            ? 'Invitation regenerated, but delivery failed. Try again after checking mail settings.'
            : 'A new invitation and temporary password were sent.');
    }

    public function regeneratePassword(User $user, Request $request, AccessManagementService $access)
    {
        $result = $access->rotateInvitation($request->user(), $user);
        $access->audit($request->user(), $user, 'user.temporary_password_regenerated', [
            'invitation_id' => $result['invitation']->id,
            'delivery_status' => $result['invitation']->email_delivery_status,
        ]);

        return back()->with($result['deliveryError'] ? 'error' : 'success', $result['deliveryError']
            ? 'Temporary password regenerated, but delivery failed. Try again after checking mail settings.'
            : 'Temporary password regenerated and sent securely.' );
    }

    public function revokeInvitation(UserInvitation $invitation, Request $request, AccessManagementService $access)
    {
        $access->revokeInvitation($request->user(), $invitation);

        return back()->with('success', 'Invitation revoked.');
    }

    public function copyInvitationLink(UserInvitation $invitation, Request $request, AccessManagementService $access)
    {
        $link = $access->regenerateLink($request->user(), $invitation);

        return response()->json(['link' => $link]);
    }

    public function toggleStatus(User $user, Request $request, AccessManagementService $access)
    {
        abort_if($user->is($request->user()), 422, 'You cannot suspend your own account.');
        $nextStatus = ! $user->is_active;
        $user->update(['is_active' => $nextStatus]);
        if (! $nextStatus) {
            $access->revokeSessions($request->user(), $user);
        } else {
            $access->audit($request->user(), $user, 'user.reactivated', []);
        }

        return back()->with('success', $nextStatus ? 'User reactivated.' : 'User suspended and sessions revoked.');
    }

    public function revokeSessions(User $user, Request $request, AccessManagementService $access)
    {
        $access->revokeSessions($request->user(), $user);

        return back()->with('success', 'All active sessions for this user were revoked.');
    }

    public function destroy(User $user, Request $request, AccessManagementService $access)
    {
        abort_if($user->is($request->user()) || $user->isAdmin(), 422, 'This account cannot be deleted. Suspend it instead.');
        $access->audit($request->user(), $user, 'user.deleted', []);
        $user->delete();

        return redirect()->route('settings.team')->with('success', 'User removed from the workspace.');
    }

    private function validated(Request $request, bool $update): array
    {
        $routeUser = $request->route('user');
        $ignoreId = $routeUser instanceof User ? $routeUser->id : (int) $routeUser;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $update ? Rule::unique('users')->ignore($ignoreId) : Rule::unique('users')],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'timezone' => ['nullable', 'timezone'],
            'record_scope' => ['required', Rule::in(['workspace', 'assigned', 'own'])],
            'access_permissions' => ['nullable', 'array'],
            'access_permissions.*' => [Rule::in(array_keys(config('access.permissions')))],
            'access_modules' => ['nullable', 'array'],
            'access_modules.*' => [Rule::in(array_keys(config('access.modules')))],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function accessData(): array
    {
        return ['roles' => User::ROLES, 'permissions' => config('access.permissions'), 'modules' => config('access.modules')];
    }

    private function history(User $user)
    {
        return AuditLog::where('auditable_type', User::class)->where('auditable_id', $user->id)->latest()->limit(20)->get();
    }
}
