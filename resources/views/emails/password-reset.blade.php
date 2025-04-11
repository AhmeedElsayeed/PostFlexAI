<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
    <h2>Reset Your Password</h2>
    <p>Hello {{ $user->name }},</p>
    <p>We received a request to reset your password. Click the link below to set a new password:</p>
    <p>
        <a href="{{ config('app.frontend_url') }}/reset-password?token={{ $reset->token }}">
            Reset Password
        </a>
    </p>
    <p>This link will expire in 24 hours.</p>
    <p>If you didn't request this password reset, please ignore this email.</p>
    <p>Best regards,<br>{{ config('app.name') }}</p>
</body>
</html> 