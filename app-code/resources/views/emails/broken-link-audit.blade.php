<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <tr><td style="background:#d97706;padding:28px 40px;">
    <span style="color:#fff;font-size:20px;font-weight:700;">ReviveGuard</span>
  </td></tr>
  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">Hi {{ $clientName }},</p>
    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      Our monthly link audit on <strong>{{ $siteUrl }}</strong> found <strong>{{ $brokenCount }} broken internal link(s)</strong>.
    </p>
    @if (count($samples) > 0)
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 18px;margin:0 0 24px;">
      @foreach ($samples as $sample)
        <p style="margin:0 0 8px;font-size:13px;color:#78350f;">
          {{ $sample['url'] ?? 'Unknown URL' }}
          @if (isset($sample['status_code'])) (HTTP {{ $sample['status_code'] }}) @endif
        </p>
      @endforeach
    </div>
    @endif
    <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
      Broken links can hurt SEO and user experience. We can fix these for you — just reply or open a ticket.
    </p>
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;margin-right:8px;">View audit details →</a>
    <a href="{{ $ticketsUrl }}" style="display:inline-block;color:#1d4ed8;text-decoration:none;font-size:14px;font-weight:600;padding:12px 0;">Request a fix</a>
  </td></tr>
  <tr><td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:20px 40px;">
    <p style="margin:0;font-size:12px;color:#9ca3af;">ReviveGuard — managed website protection</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
