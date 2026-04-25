<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

  <tr><td style="background:#1d4ed8;padding:28px 40px;">
    <span style="color:#fff;font-size:20px;font-weight:700;letter-spacing:-.3px;">ReviveGuard</span>
  </td></tr>

  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">Hi {{ $clientName }},</p>

    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin:0 0 24px;">
      <p style="margin:0;font-size:15px;color:#166534;font-weight:600;">✓ Your website is back online</p>
      <p style="margin:8px 0 0;font-size:14px;color:#14532d;">{{ $siteUrl }}</p>
    </div>

    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      <strong>{{ $siteUrl }}</strong> has recovered and is responding normally.
    </p>

    <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;width:100%;">
      <tr>
        <td style="font-size:13px;color:#6b7280;padding:6px 0;border-bottom:1px solid #f3f4f6;">Downtime duration</td>
        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;padding:6px 0;border-bottom:1px solid #f3f4f6;">{{ $downtimeDuration }}</td>
      </tr>
      <tr>
        <td style="font-size:13px;color:#6b7280;padding:6px 0;">Recovered at</td>
        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;padding:6px 0;">{{ $recoveredAt }}</td>
      </tr>
    </table>

    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
      View Dashboard →
    </a>
  </td></tr>

  <tr><td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:20px 40px;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">
      ReviveGuard — WordPress maintenance &amp; monitoring<br>
      You're receiving this because you're a ReviveGuard client.
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
