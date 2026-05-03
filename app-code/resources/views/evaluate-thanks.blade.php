<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Received — ReviveGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--bg2:#eef2f7;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--am:#d97706;--yl:#fef9c3;--ylb:#fde68a}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;color:var(--t);display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem}
        .card{background:var(--card);border:1px solid var(--bd);border-radius:1.25rem;padding:2.5rem 2rem;max-width:540px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.07);text-align:center}
        .icon{width:3.5rem;height:3.5rem;border-radius:50%;background:var(--gl);border:2px solid var(--gb);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 1.25rem}
        h1{font-size:1.5rem;font-weight:800;color:var(--t);margin-bottom:.6rem;line-height:1.25}
        .sub{font-size:.9rem;color:var(--tm);line-height:1.6;margin-bottom:1.75rem}
        .steps{text-align:left;display:flex;flex-direction:column;gap:.75rem;margin-bottom:1.75rem}
        .step{display:flex;align-items:flex-start;gap:.75rem}
        .step-n{min-width:1.5rem;height:1.5rem;border-radius:50%;background:var(--g);color:#fff;font-weight:700;font-size:.72rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem}
        .step p{font-size:.85rem;color:var(--tm);line-height:1.5}
        .step strong{color:var(--t)}
        /* Deep scan promo */
        .deepscan{background:linear-gradient(135deg,var(--yl),#fef3c7);border:1.5px solid var(--ylb);border-radius:.85rem;padding:1.25rem;text-align:left;margin-bottom:1.5rem}
        .ds-head{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
        .ds-badge{background:var(--am);color:#fff;font-size:.68rem;font-weight:700;padding:.15rem .5rem;border-radius:9999px;text-transform:uppercase;letter-spacing:.04em}
        .deepscan h3{font-size:.92rem;font-weight:700;color:#92400e}
        .deepscan p{font-size:.82rem;color:#78350f;line-height:1.55;margin-bottom:.85rem}
        .btn-primary{display:block;width:100%;padding:.75rem;background:var(--am);color:#fff;border:none;border-radius:.6rem;font-size:.88rem;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;transition:opacity .15s;text-align:center}
        .btn-primary:hover{opacity:.88;text-decoration:none;color:#fff}
        .btn-sec{display:block;width:100%;padding:.7rem;background:transparent;color:var(--td);border:1.5px solid var(--bd);border-radius:.6rem;font-size:.84rem;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;margin-top:.6rem;transition:background .15s;text-align:center}
        .btn-sec:hover{background:var(--bg2);color:var(--tm);text-decoration:none}
        .waitlist-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:.65rem;padding:.85rem 1rem;font-size:.82rem;color:#7c2d12;margin-bottom:1.25rem;text-align:left}
    </style>
</head>
<body>
<div class="card">
    <div class="icon">&#10003;</div>

    @if($waitlisted)
        <h1>You're on the waitlist!</h1>
        <p class="sub">We've received your evaluation request. We're currently at capacity for new onboardings this month — you're on the waitlist and will be our first priority next month.</p>
        <div class="waitlist-note">
            <strong>While you wait:</strong> Install our free Health Check plugin and share your report — it helps us prioritise and means we can move faster when a spot opens.
        </div>
    @else
        <h1>Evaluation received!</h1>
        <p class="sub">We've kicked off an automatic scan of your site. Our team will complete the full manual review and get back to you within <strong>48 hours</strong>.</p>
    @endif

    <div class="steps">
        <div class="step">
            <div class="step-n">1</div>
            <p><strong>Automatic scan running</strong> — we're checking your SSL, domain expiry, security headers, and uptime right now.</p>
        </div>
        <div class="step">
            <div class="step-n">2</div>
            <p><strong>Manual review within 48 hours</strong> — a real team member reviews everything and writes a personalised assessment.</p>
        </div>
        <div class="step">
            <div class="step-n">3</div>
            <p><strong>You'll receive a proposal email</strong> with what we found, the risks, our recommended plan, and a one-click accept button.</p>
        </div>
    </div>

    @if($evaluationId)
    <div class="deepscan">
        <div class="ds-head">
            <span class="ds-badge">Optional</span>
            <h3>Speed up your evaluation</h3>
        </div>
        <p>Install our free Health Check plugin to give us a full internal view — plugin versions, backups, security issues. Takes 3 minutes and gets you reviewed first.</p>
        <a href="{{ route('evaluate.report.show', $evaluationId) }}" class="btn-primary">&#8659; Upload Deep Scan Report</a>
    </div>
    @endif

    <a href="https://reviveguard.com" class="btn-sec">Back to ReviveGuard.com</a>
</div>
</body>
</html>
