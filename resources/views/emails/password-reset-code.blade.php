<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Code</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f8;font-family:Arial,sans-serif;color:#202d3f;">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #dbe3ef;border-radius:14px;padding:32px;">
            <h2 style="margin:0 0 12px;font-size:24px;color:#101b2d;">Password Reset Verification</h2>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Hello {{ $recipientName !== '' ? $recipientName : 'there' }},
            </p>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Use the verification code below to reset your Grade Dispute System password.
            </p>
            <div style="margin:24px 0;padding:18px 20px;background:#edf1f7;border:1px solid #d2dbe8;border-radius:10px;text-align:center;">
                <span style="display:block;font-size:32px;font-weight:700;letter-spacing:10px;color:#0f2142;">{{ $code }}</span>
            </div>
            <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#4f5f75;">
                This code expires in {{ $expiresInMinutes }} minutes.
            </p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#4f5f75;">
                If you did not request a password reset, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
