<!doctype html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #182033; line-height: 1.5;">
    <h2>Verify your email</h2>
    <p>Use this verification code to continue your AiStar Prep free diagnostic:</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 6px;">{{ $verificationCode }}</p>
    <p>This code expires in {{ config('verification.email.expires_minutes', 10) }} minutes.</p>
    <p>If you did not request this code, you can ignore this email.</p>
</body>
</html>
