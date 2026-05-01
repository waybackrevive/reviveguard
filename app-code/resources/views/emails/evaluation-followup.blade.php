<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Still thinking about ReviveGuard?</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .header { background: #0f172a; padding: 28px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 20px; margin: 0; font-weight: 600; }
        .body { padding: 36px 40px; color: #374151; font-size: 15px; line-height: 1.7; }
        .footer { padding: 20px 40px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
        .footer a { color: #6b7280; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header"><h1>ReviveGuard</h1></div>
    <div class="body">
        <p>Hi {{ $name }},</p>
        <p>
            We reviewed your evaluation request for <strong>{{ $siteUrl }}</strong> but haven't
            heard back from you. We wanted to check in &mdash; are you still interested in
            ongoing WordPress maintenance and monitoring?
        </p>
        <p>
            If now isn't the right time, no worries at all. Just reply to this email whenever you're ready
            and we'll get you set up.
        </p>
        <p style="font-size:13px;color:#6b7280;margin-top:24px;">
            Questions? <a href="mailto:support@reviveguard.com" style="color:#2563eb;">support@reviveguard.com</a>
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ReviveGuard &mdash; <a href="https://reviveguard.com">reviveguard.com</a>
    </div>
</div>
</body>
</html>
