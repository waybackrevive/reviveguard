<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

  <tr><td style="background:#059669;padding:28px 40px;">
    <span style="color:#fff;font-size:20px;font-weight:700;letter-spacing:-.3px;">ReviveGuard</span>
  </td></tr>

  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">Hi {{ $clientName }}, welcome to ReviveGuard!</p>

    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      Your <strong>{{ $planName }}</strong> plan is now active. We're protecting your WordPress site
      around the clock — monitoring uptime, handling updates, and keeping backups safe.
    </p>

    <p style="margin:0 0 20px;font-size:15px;color:#374151;line-height:1.6;">
      Click the button below to set your password and access your client portal.
      This link is valid for <strong>72 hours</strong>.
    </p>

    @if ($activationUrl)
    <div style="margin:0 0 24px;">
      <a href="{{ $activationUrl }}"
         style="display:inline-block;background:#059669;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 28px;border-radius:8px;letter-spacing:-.1px;">
        Activate Your Account →
      </a>
    </div>

    <p style="margin:0 0 16px;font-size:13px;color:#6b7280;line-height:1.6;">
      If the button doesn't work, copy and paste this link into your browser:<br>
      <span style="color:#059669;word-break:break-all;">{{ $activationUrl }}</span>
    </p>

    <p style="margin:0 0 8px;font-size:13px;color:#9ca3af;">
      Link expired? Use the "Forgot password" option on the login page.
    </p>
    @endif

    <hr style="border:none;border-top:1px solid #f3f4f6;margin:24px 0;">

    <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#374151;">Your portal gives you access to:</p>
    <ul style="margin:0 0 0;padding-left:20px;">
      <li style="font-size:13px;color:#374151;margin-bottom:4px;">Real-time site status and uptime metrics</li>
      <li style="font-size:13px;color:#374151;margin-bottom:4px;">Event history and alerts</li>
      <li style="font-size:13px;color:#374151;margin-bottom:4px;">Monthly maintenance reports</li>
      <li style="font-size:13px;color:#374151;margin-bottom:4px;">Backup management</li>
      <li style="font-size:13px;color:#374151;margin-bottom:4px;">Support ticket submission</li>
    </ul>
  </td></tr>

  <tr><td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:20px 40px;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">
      ReviveGuard — WordPress maintenance &amp; monitoring &bull; A WaybackRevive LLC product<br>
      You're receiving this because you signed up for ReviveGuard.
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
