<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Received — ReviveGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;color:var(--t);display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem}
        .card{background:var(--card);border:1px solid var(--bd);border-radius:1.25rem;padding:2.5rem 2rem;max-width:480px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.07);text-align:center}
        .icon{width:3.5rem;height:3.5rem;border-radius:50%;background:var(--gl);border:2px solid var(--gb);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 1.25rem}
        h1{font-size:1.5rem;font-weight:800;color:var(--t);margin-bottom:.6rem}
        .sub{font-size:.9rem;color:var(--tm);line-height:1.6;margin-bottom:1.75rem}
        .info-box{background:#f0fdf4;border:1px solid var(--gb);border-radius:.75rem;padding:1rem 1.25rem;text-align:left;margin-bottom:1.5rem}
        .info-box p{font-size:.84rem;color:#065f46;line-height:1.6}
        .info-box strong{color:#064e3b}
        .btn{display:block;width:100%;padding:.75rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;text-align:center;transition:background .15s}
        .btn:hover{background:var(--gh);text-decoration:none;color:#fff}
        .note{font-size:.78rem;color:var(--td);margin-top:1rem;line-height:1.5}
    </style>
</head>
<body>
<div class="card">
    <div class="icon">&#128269;</div>
    <h1>Report received!</h1>
    <p class="sub">
        We've added your internal health scan data to your evaluation. You're now at the front of the queue.
    </p>

    <div class="info-box">
        <p>
            <strong>What happens next:</strong><br>
            Our team can now see your WordPress version, plugin status, backup configuration, and security setup.
            We'll have a full proposal ready for you within <strong>24 hours</strong>.
        </p>
    </div>

    <a href="https://reviveguard.com" class="btn">Back to ReviveGuard.com &rarr;</a>

    <p class="note">
        You can safely remove the Health Check plugin from your WordPress admin now &mdash;
        it will also auto-deactivate within 48 hours.
    </p>
</div>
</body>
</html>
