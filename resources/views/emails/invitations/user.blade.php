<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Tanzania Specialist invitation</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { padding: 18px 10px !important; }
            .email-card { border-radius: 14px !important; }
            .email-content { padding: 26px 22px !important; }
            .email-header { padding: 22px !important; }
            .email-button { display: block !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0;background:#f5f1e8;color:#3b2921;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    <div class="email-shell" style="padding:42px 16px;">
        <div class="email-card" style="max-width:560px;margin:0 auto;overflow:hidden;border:1px solid #e4d9c8;border-radius:18px;background:#ffffff;box-shadow:0 14px 38px rgba(74,53,35,.10);">
            <div class="email-header" style="padding:28px 32px;background:#e8dcc9;border-bottom:1px solid #ddceb8;">
                <img src="{{ asset('assets/images/ourlogo.png') }}" width="178" alt="Tanzania Specialist" style="display:block;width:178px;max-width:100%;height:auto;">
                <div style="margin-top:12px;color:#776453;font-size:10px;font-weight:bold;letter-spacing:.14em;text-transform:uppercase;">Safari Planner workspace</div>
            </div>

            <div class="email-content" style="padding:32px;">
                <div style="margin-bottom:12px;color:#6a7b47;font-size:10px;font-weight:bold;letter-spacing:.15em;text-transform:uppercase;">Secure invitation</div>
                <h1 style="margin:0 0 12px;color:#3b2018;font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:1.15;letter-spacing:-.04em;">Welcome to the operations desk</h1>
                <p style="margin:0 0 18px;color:#8c7868;font-size:14px;">Hello {{ $user->name }},</p>
                <p style="margin:0 0 18px;color:#544235;font-size:14px;">You have been invited to join the Tanzania Specialist safari operations workspace as <strong>{{ ucfirst($user->role) }}</strong>.</p>

                <div style="margin:24px 0;padding:18px;border:1px solid #e4d9c8;border-radius:10px;background:#f8f4ec;">
                    <div style="margin-bottom:6px;color:#806d5b;font-size:10px;font-weight:bold;letter-spacing:.12em;text-transform:uppercase;">Your temporary password</div>
                    <div style="color:#3b2018;font-family:Consolas,Monaco,monospace;font-size:18px;font-weight:bold;letter-spacing:.08em;">{{ $temporaryPassword }}</div>
                    <div style="margin-top:8px;color:#8c7868;font-size:12px;">You will create a new password when you activate your account.</div>
                </div>

                <p style="margin:24px 0;text-align:left;">
                    <a class="email-button" href="{{ route('invitation.show', ['token' => $token]) }}" style="display:inline-block;padding:13px 20px;border-radius:8px;background:#a5ae96;color:#ffffff;font-size:13px;font-weight:bold;text-decoration:none;">Activate your account</a>
                </p>

                <p style="margin:0 0 12px;color:#8c7868;font-size:12px;">This secure invitation expires <strong style="color:#544235;">{{ $expiresAt->format('d M Y, H:i T') }}</strong>.</p>
                <p style="margin:0;color:#8c7868;font-size:12px;">If you did not expect this invitation, contact your workspace administrator. Please do not forward this email.</p>
            </div>

            <div style="padding:20px 32px;border-top:1px solid #eee6dc;background:#fbfaf7;color:#8c7868;font-size:11px;">
                <strong style="color:#3b2018;">Tanzania Specialist</strong><br>
                Safari operations, beautifully in sync.<br>
                This is an automated security message.
            </div>
        </div>
    </div>
</body>
</html>
