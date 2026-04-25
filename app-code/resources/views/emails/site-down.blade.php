<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

  {{-- Header --}}
  <tr><td style="background:#1d4ed8;padding:28px 40px;">
    <span style="color:#fff;font-size:20px;font-weight:700;letter-spacing:-.3px;">ReviveGuard</span>
  </td></tr>

  {{-- Body --}}
  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">Hi {{ $clientName }},</p>

    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px 20px;margin:0 0 24px;">
      <p style="margin:0;font-size:15px;color:#991b1b;font-weight:600;">⚠ Your website is not responding</p>
      <p style="margin:8px 0 0;font-size:14px;color:#7f1d1d;">{{ $siteUrl }}</p>
    </div>

    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      We detected that <strong>{{ $siteUrl }}</strong> appears to be offline as of
      <strong>{{ $detectedAt }}</strong>.
    </p>

    <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;">
      We'll notify you as soon as it's back online. No action is needed on your end.
    </p>

    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
      View Dashboard →
    </a>
  </td></tr>

  {{-- Footer --}}
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
