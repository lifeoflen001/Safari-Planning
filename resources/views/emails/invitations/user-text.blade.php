Tanzania Specialist - Safari Planner

Hello {{ $user->name }},

You have been invited to join the Tanzania Specialist safari operations workspace as {{ ucfirst($user->role) }}.

Activate your account:
{{ route('invitation.show', ['token' => $token]) }}

Temporary password: {{ $temporaryPassword }}

This secure invitation expires {{ $expiresAt->format('d M Y, H:i T') }}. You must create a new password before accessing the workspace.

If you did not expect this invitation, contact your workspace administrator. Please do not forward this email.
