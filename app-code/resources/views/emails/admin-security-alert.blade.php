<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
  <tr><td style="background:#111827;padding:28px 40px;">
    <span style="color:#fff;font-size:20px;font-weight:700;">ReviveGuard Ops</span>
  </td></tr>
  <tr><td style="padding:36px 40px;">
    <p style="margin:0 0 16px;font-size:16px;color:#111827;">{{ $kind }} alert</p>
    <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
      <strong>{{ $siteName }}</strong> — <a href="{{ $siteUrl }}">{{ $siteUrl }}</a>
    </p>
    @if (count($findings) > 0)
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 18px;margin:0 0 24px;">
      @foreach ($findings as $finding)
        <p style="margin:0 0 8px;font-size:13px;color:#374151;">
          {{ is_array($finding) ? ($finding['message'] ?? ($finding['url'] ?? json_encode($finding))) : $finding }}
        </p>
      @endforeach
    </div>
    @endif
    <a href="{{ $adminUrl }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">Open site in admin →</a>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
