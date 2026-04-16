<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Grade Dispute System</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f8;font-family:Arial,sans-serif;color:#202d3f;">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #dbe3ef;border-radius:14px;padding:32px;">
            <h2 style="margin:0 0 12px;font-size:24px;color:#101b2d;">Your portal account is ready</h2>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Hello {{ $recipientName !== '' ? $recipientName : 'there' }},
            </p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                A {{ strtolower($roleLabel) }} account has been created for you in the Grade Dispute System.
            </p>
            <div style="margin:20px 0;padding:18px 20px;background:#edf1f7;border:1px solid #d2dbe8;border-radius:10px;">
                <p style="margin:0 0 8px;font-size:14px;color:#4f5f75;"><strong>University ID:</strong> {{ $identifier }}</p>
                <p style="margin:0;font-size:14px;color:#4f5f75;"><strong>Account status:</strong> {{ $statusLabel }}</p>
            </div>

            @if ($manualApprovalRequired)
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#7a4b00;">
                    Your account is currently waiting for administrative approval before sign-in is enabled.
                </p>
            @endif

            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Use the button below to open the password setup and recovery flow.
                @if ($requirePasswordReset)
                    You will need to choose a password before your first successful sign-in.
                @endif
            </p>

            <p style="margin:24px 0;">
                <a href="{{ $passwordSetupUrl }}" style="display:inline-block;padding:12px 20px;background:#111827;border-radius:10px;color:#ffffff;text-decoration:none;font-weight:700;">
                    Set or Reset Password
                </a>
            </p>

            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#4f5f75;">
                Portal: <a href="{{ $portalUrl }}" style="color:#0f2142;">{{ $portalUrl }}</a>
            </p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#4f5f75;">
                If you were not expecting this account, please contact your department administrator.
            </p>
        </div>
    </div>
</body>
</html>
