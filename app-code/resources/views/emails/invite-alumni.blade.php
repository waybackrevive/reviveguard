<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your ReviveGuard Invitation</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .header { background: #0f172a; padding: 32px 40px; text-align: center; }
        .header img { height: 36px; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 12px 0 0; font-weight: 600; letter-spacing: -0.3px; }
        .body { padding: 40px; color: #374151; font-size: 15px; line-height: 1.7; }
        .body h2 { font-size: 18px; color: #111827; margin: 0 0 16px; font-weight: 600; }
        .site-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 18px; margin: 20px 0; font-size: 14px; color: #6b7280; }
        .site-box strong { color: #111827; }
        .cta-wrap { text-align: center; margin: 32px 0; }
        .cta { display: inline-block; background: #2563eb; color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; letter-spacing: 0.2px; }
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
    </div>
    <div class="body">
        <h2>Hi {{ $name }}, you're invited!</h2>
        <p>
            As one of our trusted WaybackRevive alumni, you've earned exclusive early access to
            <strong>ReviveGuard</strong> — ongoing WordPress maintenance and monitoring to keep your
            restored site safe, fast, and always online.
        </p>

        @if ($siteUrl)
        <div class="site-box">
            <strong>Your site:</strong> {{ $siteUrl }}
        </div>
        @endif

        <p>
            Click the button below to create your portal account and get started. No credit card is
            required at this step — your subscription is managed through Stripe.
        </p>

        <div class="cta-wrap">
            <a href="{{ $acceptUrl }}" class="cta">Accept Invitation &amp; Set Password</a>
        </div>
        <p class="expiry">This link expires on {{ $expiresAt }}.</p>

        <hr class="divider">

        <p style="font-size:13px;color:#6b7280;">
            If the button above doesn't work, copy and paste this URL into your browser:
        </p>
        <p class="url-fallback">{{ $acceptUrl }}</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ReviveGuard &mdash; <a href="https://reviveguard.com">reviveguard.com</a><br>
        You received this email because an admin issued you an exclusive invite. Not expecting this?
        You can safely ignore it &mdash; the link will expire automatically.
    </div>
</div>
</body>
</html>
