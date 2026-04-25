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

    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      We've completed a maintenance update on <strong>{{ $siteUrl }}</strong>.
    </p>

    @if ($coreUpdated || count($plugins) > 0)
    <p style="margin:0 0 8px;font-size:14px;font-weight:600;color:#374151;">What was updated:</p>
    <ul style="margin:0 0 24px;padding-left:20px;">
      @if ($coreUpdated)
        <li style="font-size:14px;color:#374151;margin-bottom:6px;">WordPress core</li>
      @endif
      @foreach ($plugins as $plugin)
        <li style="font-size:14px;color:#374151;margin-bottom:6px;">{{ $plugin }}</li>
      @endforeach
    </ul>
    @endif

    @if (count($errors) > 0)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 18px;margin:0 0 24px;">
      <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#991b1b;">Some items had issues — our team has been alerted:</p>
      @foreach ($errors as $error)
        <p style="margin:4px 0 0;font-size:13px;color:#7f1d1d;">• {{ $error }}</p>
      @endforeach
    </div>
    @endif

    <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
      Your site is live and functioning normally. No action is needed on your end.
    </p>

    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
      View Activity Log →
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
