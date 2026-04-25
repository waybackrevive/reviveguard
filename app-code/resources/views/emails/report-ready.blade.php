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

    <p style="margin:0 0 8px;font-size:15px;color:#374151;line-height:1.6;">
      Here's what happened on <strong>{{ $siteUrl }}</strong> in <strong>{{ $period }}</strong>:
    </p>

    <table cellpadding="0" cellspacing="0" style="margin:20px 0 28px;width:100%;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
      @if ($uptime30d !== null)
      <tr style="border-bottom:1px solid #f3f4f6;">
        <td style="font-size:13px;color:#6b7280;padding:12px 16px;">Uptime</td>
        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;padding:12px 16px;">{{ number_format($uptime30d, 2) }}%</td>
      </tr>
      @endif
      <tr>
        <td style="font-size:13px;color:#6b7280;padding:12px 16px;">Full report</td>
        <td style="font-size:13px;color:#111827;font-weight:600;text-align:right;padding:12px 16px;">Attached as PDF</td>
      </tr>
    </table>

    <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
      Your full monthly report is attached to this email as a PDF.
    </p>

    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
      View All Reports →
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
