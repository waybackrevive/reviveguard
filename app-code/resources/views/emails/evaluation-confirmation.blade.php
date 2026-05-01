<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We received your evaluation request</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .header { background: #0f172a; padding: 28px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 20px; margin: 0; font-weight: 600; }
        .body { padding: 36px 40px; color: #374151; font-size: 15px; line-height: 1.7; }
        .body h2 { font-size: 17px; color: #111827; font-weight: 600; margin: 0 0 12px; }
        .timeline { list-style: none; padding: 0; margin: 20px 0; }
        .timeline li { padding: 8px 0 8px 28px; position: relative; font-size: 14px; color: #374151; border-left: 2px solid #e5e7eb; margin-left: 6px; }
        .timeline li::before { content: ''; width: 10px; height: 10px; background: #2563eb; border-radius: 50%; position: absolute; left: -6px; top: 12px; }
        .waitlist-box { background: #fefce8; border: 1px solid #fde047; border-radius: 6px; padding: 14px 18px; font-size: 14px; color: #92400e; margin: 20px 0; }
        .footer { padding: 20px 40px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
        .footer a { color: #6b7280; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header"><h1>ReviveGuard</h1></div>
    <div class="body">
        <h2>Hi {{ $name }}, we've received your request!</h2>

        @if ($waitlisted)
        <div class="waitlist-box">
            <strong>You're on the waitlist.</strong> We've reached our evaluation limit for this month.
            We'll reach out as soon as a spot opens up — usually within 2&ndash;4 weeks.
        </div>
        @else
        <p>
            Thanks for submitting your site for evaluation. Here's what happens next:
        </p>
        <ul class="timeline">
            <li><strong>Within 24&ndash;48h</strong> — Our team reviews your site</li>
            <li><strong>If it's a good fit</strong> — We send you a proposal with plan details</li>
            <li><strong>You decide</strong> — Accept and create your portal account, or decline with no pressure</li>
        </ul>
        @endif

        @if ($siteUrl)
        <p style="font-size:13px;color:#6b7280;">
            Site submitted: <strong>{{ $siteUrl }}</strong>
        </p>
        @endif

        <p style="font-size:13px;color:#6b7280;margin-top:20px;">
            Questions? Reply to this email or reach us at
            <a href="mailto:support@reviveguard.com" style="color:#2563eb;">support@reviveguard.com</a>.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ReviveGuard &mdash; <a href="https://reviveguard.com">reviveguard.com</a>
    </div>
</div>
</body>
</html>
