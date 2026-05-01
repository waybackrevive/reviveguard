<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Received — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem;color:var(--t)}
        a{color:var(--g);text-decoration:none}a:hover{text-decoration:underline}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.35rem;font-weight:800;letter-spacing:-.025em;text-decoration:none}
        .brand .dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--bd);border-radius:1rem;padding:2.5rem;box-shadow:0 4px 24px rgba(0,0,0,.07);text-align:center}
        .icon{width:3rem;height:3rem;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem}
        .icon-ok{background:var(--gl);border:2px solid var(--gb)}
        .icon-wait{background:#fef9c3;border:2px solid #fde047}
        .icon svg{width:1.5rem;height:1.5rem}
        h2{font-size:1.25rem;font-weight:800;color:var(--t);margin-bottom:.5rem}
        p{font-size:.88rem;color:var(--tm);line-height:1.65}
        .what-next{background:var(--gl);border:1px solid var(--gb);border-radius:.75rem;padding:1rem 1.25rem;text-align:left;margin:1.5rem 0}
        .what-next .wn-title{font-size:.78rem;font-weight:700;color:var(--gh);margin-bottom:.5rem}
        .what-next p{font-size:.8rem;color:var(--tm)}
        .back-link{display:inline-block;margin-top:.25rem;font-size:.85rem;font-weight:600;color:var(--g)}
    </style>
</head>
<body>
<div class="card">
    <a href="https://reviveguard.com" class="brand" style="justify-content:center;margin-bottom:1.5rem">
        <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
    </a>

    @if ($waitlisted)
        <div class="icon icon-wait">
            <svg fill="none" stroke="#ca8a04" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2>You're on the waitlist</h2>
        <p>We've received your request but have reached our evaluation limit for this month (26 new clients). You're on the waitlist — we'll reach out as soon as a spot opens up.</p>

        <div class="what-next">
            <div class="wn-title">📬 &nbsp;What happens next</div>
            <p>We'll email you when a spot opens. No action needed on your end. If it's urgent, <a href="mailto:support@reviveguard.com">contact us directly</a>.</p>
        </div>

    @else
        <div class="icon icon-ok">
            <svg fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2>Evaluation received!</h2>
        <p>We've received your request and sent a confirmation to your inbox. Our team will review your site and get back to you within 48 hours with a full assessment and recommended plan.</p>

        <div class="what-next">
            <div class="wn-title">📬 &nbsp;Check your inbox</div>
            <p>You should receive a confirmation email shortly. The proposal — when we send it — will include our findings and a personalised invite link to get started.</p>
        </div>
    @endif

    <a href="{{ url('/') }}" class="back-link">← Back to ReviveGuard</a>
</div>
</body>
</html>
