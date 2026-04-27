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
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">Hi {{ $clientName }},</p>

    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      Your ReviveGuard subscription has been updated. Here's a summary of your current plan:
    </p>

    <table width="100%" cellpadding="0" cellspacing="0"
           style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin:0 0 24px;">
      <tr>
        <td style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
          <span style="font-size:13px;color:#6b7280;">Plan</span><br>
          <span style="font-size:15px;font-weight:600;color:#111827;">{{ $planName }}</span>
        </td>
      </tr>
      <tr>
        <td style="padding:16px 20px;">
          <span style="font-size:13px;color:#6b7280;">Status</span><br>
          <span style="font-size:15px;font-weight:600;color:#059669;">Active</span>
          @if ($validUntil)
          <span style="font-size:13px;color:#6b7280;"> · renews {{ $validUntil }}</span>
          @endif
        </td>
      </tr>
    </table>

    <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.6;">
      Everything is up and running. Log in to your portal to check your site status,
      view reports, and manage your account.
    </p>

    <a href="{{ $dashboardUrl }}"
       style="display:inline-block;background:#059669;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
      Go to Dashboard →
    </a>

    <p style="margin:24px 0 0;font-size:13px;color:#6b7280;line-height:1.6;">
      If you did not make any changes to your plan, please reply to this email immediately.
    </p>
  </td></tr>

  <tr><td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:20px 40px;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">
      ReviveGuard — WordPress maintenance &amp; monitoring &bull; A WaybackRevive LLC product<br>
      You're receiving this because you have an active ReviveGuard subscription.
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
