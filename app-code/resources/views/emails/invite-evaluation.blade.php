<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your ReviveGuard Proposal</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .header { background: #0f172a; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; font-weight: 600; letter-spacing: -0.3px; }
        .badge { display: inline-block; margin-top: 8px; background: #16a34a; color: #fff; font-size: 12px; font-weight: 600; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px; text-transform: uppercase; }
        .body { padding: 40px; color: #374151; font-size: 15px; line-height: 1.7; }
        .body h2 { font-size: 18px; color: #111827; margin: 0 0 16px; font-weight: 600; }
        .site-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; margin: 20px 0; font-size: 14px; color: #6b7280; }
        .site-box strong { color: #111827; }
        .what-you-get { list-style: none; padding: 0; margin: 0 0 24px; }
        .what-you-get li { padding: 6px 0; font-size: 14px; color: #374151; }
        .what-you-get li::before { content: '✓ '; color: #16a34a; font-weight: 700; }
        .cta-wrap { text-align: center; margin: 32px 0; }
        .cta { display: inline-block; background: #16a34a; color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; letter-spacing: 0.2px; }
        .expiry { font-size: 13px; color: #9ca3af; text-align: center; margin: -16px 0 24px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .footer { padding: 20px 40px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; line-height: 1.6; }
        .footer a { color: #6b7280; text-decoration: none; }
        .url-fallback { word-break: break-all; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>ReviveGuard</h1>
        <span class="badge">Evaluation Complete</span>
    </div>
    <div class="body">
        <h2>Hi {{ $name }}, great news!</h2>
        <p>
            We've reviewed your site evaluation and we're excited to offer you a spot in ReviveGuard.
            Here's what's included in your plan:
        </p>

        @if ($siteUrl)
        <div class="site-box">
            <strong>Site evaluated:</strong> {{ $siteUrl }}
        </div>
        @endif

        <ul class="what-you-get">
            <li>24/7 uptime monitoring with instant alerts</li>
            <li>Daily automated backups to secure cloud storage</li>
            <li>WordPress core, theme &amp; plugin updates</li>
            <li>Monthly performance &amp; security report</li>
            <li>Priority support ticket queue</li>
        </ul>

        <p>
            Accept the proposal below to create your client portal account and start your plan.
            This is your exclusive, one-time link &mdash; don't share it.
        </p>

        <div class="cta-wrap">
            <a href="{{ $acceptUrl }}" class="cta">Accept Proposal &amp; Create Account</a>
        </div>
        <p class="expiry">This proposal expires on {{ $expiresAt }}.</p>

        <hr class="divider">

        <p style="font-size:13px;color:#6b7280;">
            If the button above doesn't work, copy and paste this URL into your browser:
        </p>
        <p class="url-fallback">{{ $acceptUrl }}</p>

        <p style="font-size:13px;color:#6b7280;margin-top:24px;">
            Questions? Reply to this email or reach us at
            <a href="mailto:support@reviveguard.com" style="color:#2563eb;">support@reviveguard.com</a>.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ReviveGuard &mdash; <a href="https://reviveguard.com">reviveguard.com</a><br>
        This invite was issued based on your submitted site evaluation request.
    </div>
</div>
</body>
</html>
