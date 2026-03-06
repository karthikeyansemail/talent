<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4f46e5; color: #fff; padding: 24px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .credentials { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 16px 0; }
        .credentials dt { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-top: 8px; }
        .credentials dd { font-size: 16px; font-weight: 600; margin: 4px 0 0 0; }
        .btn { display: inline-block; background: #4f46e5; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 16px; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0;font-size:20px">Interview Account Created</h1>
    </div>
    <div class="content">
        <p>Hello {{ $userName }},</p>
        <p>You have been assigned as an interviewer. An account has been created for you to access the interview platform.</p>

        <div class="credentials">
            <dl>
                <dt>Email</dt>
                <dd>{{ $userEmail }}</dd>
                <dt>Password</dt>
                <dd>{{ $password }}</dd>
            </dl>
        </div>

        <p>Please log in and change your password after your first login.</p>

        <a href="{{ $loginUrl }}" class="btn">Log In Now</a>

        <p style="margin-top:20px;font-size:13px;color:#6b7280">If your organization uses SSO (Google or Microsoft), you can also sign in using your corporate account.</p>
    </div>
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
