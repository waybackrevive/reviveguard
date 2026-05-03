<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Health Report — ReviveGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--bg2:#eef2f7;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--re:#dc2626;--rel:#fee2e2;--am:#d97706;--yl:#fef9c3;--ylb:#fde68a}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;color:var(--t);padding:3rem 1.25rem 4rem}
        .wrap{max-width:580px;margin:0 auto}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.2rem;font-weight:800;text-decoration:none;margin-bottom:2rem}
        .brand .dot{width:.45rem;height:.45rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .card{background:var(--card);border:1px solid var(--bd);border-radius:1rem;padding:2rem;box-shadow:0 2px 16px rgba(0,0,0,.06)}
        .badge{display:inline-block;background:var(--am);color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:9999px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.75rem}
        h1{font-size:1.4rem;font-weight:800;color:var(--t);line-height:1.25;margin-bottom:.5rem}
        .sub{font-size:.88rem;color:var(--tm);line-height:1.6;margin-bottom:1.5rem}
        .site-url{background:var(--bg2);border:1px solid var(--bd);border-radius:.5rem;padding:.55rem .85rem;font-size:.82rem;color:var(--td);margin-bottom:1.5rem;word-break:break-all}
        .steps{display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.5rem}
        .step{display:flex;align-items:flex-start;gap:.65rem}
        .step-n{min-width:1.4rem;height:1.4rem;border-radius:50%;background:var(--am);color:#fff;font-weight:700;font-size:.68rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem}
        .step p{font-size:.82rem;color:var(--tm);line-height:1.5}
        .step strong{color:var(--t)}
        .dl-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .9rem;background:var(--am);color:#fff;border-radius:.5rem;font-size:.82rem;font-weight:700;text-decoration:none;margin-bottom:1.5rem;transition:opacity .15s}
        .dl-btn:hover{opacity:.88;text-decoration:none;color:#fff}
        .sep{display:flex;align-items:center;gap:.75rem;margin:1rem 0;color:var(--td);font-size:.78rem;font-weight:600}
        .sep::before,.sep::after{content:'';flex:1;height:1px;background:var(--bd)}
        .f{margin-bottom:1rem}
        .f label{display:block;font-size:.82rem;font-weight:600;color:var(--tm);margin-bottom:.4rem}
        .f textarea{width:100%;padding:.65rem .875rem;border:1.5px solid var(--bd);border-radius:.55rem;font-size:.78rem;font-family:'Courier New',monospace;color:var(--t);background:#fff;outline:none;resize:vertical;min-height:100px;transition:border-color .15s}
        .f textarea:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(5,150,105,.12)}
        .a-err{background:var(--rel);border:1px solid #fca5a5;border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#991b1b;margin-bottom:1rem}
        .a-success{background:#f0fdf4;border:1px solid var(--gb);border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#065f46;margin-bottom:1rem}
        .btn{width:100%;padding:.8rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;transition:background .15s}
        .btn:hover{background:var(--gh)}
        .privacy-note{font-size:.75rem;color:var(--td);margin-top:.85rem;line-height:1.55}
        .skip-link{display:block;text-align:center;margin-top:1.25rem;font-size:.82rem;color:var(--td)}
    </style>
</head>
<body>
<div class="wrap">
    <a href="https://reviveguard.com" class="brand">
        <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
    </a>

    <div class="card">
        <div class="badge">Optional — Faster Approval</div>
        <h1>Upload your Health Check report</h1>
        <p class="sub">
            Our Health Check plugin scans your site internally and generates a one-time report code.
            Paste it below and we'll see your full WordPress health data — plugins, backups, versions, security status.
        </p>

        <div class="site-url">
            &#127760; {{ $evaluation->site_url }}
        </div>

        @if ($errors->any())
            <div class="a-err">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="a-success">{{ session('success') }}</div>
        @endif

        <p style="font-size:.85rem;font-weight:600;color:var(--t);margin-bottom:.85rem">Step 1 — Install the plugin</p>
        <div class="steps">
            <div class="step">
                <div class="step-n">1</div>
                <p>Download our free <strong>ReviveGuard Health Check</strong> plugin below</p>
            </div>
            <div class="step">
                <div class="step-n">2</div>
                <p>In your WordPress admin: <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong> &rarr; upload the zip &rarr; Activate</p>
            </div>
            <div class="step">
                <div class="step-n">3</div>
                <p>Go to <strong>Tools &rarr; ReviveGuard Health Check</strong> and click <strong>"Generate Report"</strong></p>
            </div>
            <div class="step">
                <div class="step-n">4</div>
                <p>Copy the report code that appears and paste it below</p>
            </div>
        </div>

        <a href="{{ asset('reviveguard-healthcheck.zip') }}" class="dl-btn" download>
            &#8659; Download ReviveGuard Health Check Plugin
        </a>

        @if($pluginSecret)
        <div style="margin-top:.75rem;background:var(--bg2);border:1px solid var(--bd);border-radius:.6rem;padding:.85rem 1rem">
            <p style="font-size:.8rem;font-weight:600;color:var(--t);margin-bottom:.4rem">Your Secret Key (paste this into the plugin):</p>
            <div style="display:flex;gap:.5rem;align-items:center">
                <input id="rg_secret" type="text" readonly value="{{ $pluginSecret }}"
                    style="flex:1;font-family:monospace;font-size:.78rem;padding:.4rem .65rem;border:1px solid var(--bd);border-radius:.4rem;background:#fff;color:var(--t)">
                <button type="button" onclick="var e=document.getElementById('rg_secret');e.select();document.execCommand('copy');this.textContent='Copied!';"
                    style="padding:.4rem .7rem;font-size:.78rem;font-weight:600;font-family:inherit;border:1px solid var(--bd);border-radius:.4rem;cursor:pointer;background:#fff;white-space:nowrap">Copy</button>
            </div>
        </div>
        @endif

        <div class="sep">Step 2 — Paste your report code</div>

        <form method="POST" action="{{ route('evaluate.report.store', $evaluation->id) }}">
            @csrf
            <div class="f">
                <label>Report code (from plugin)</label>
                <textarea name="report_token" placeholder="Paste your report code here…" required>{{ old('report_token') }}</textarea>
            </div>
            <button type="submit" class="btn">Submit Report &rarr;</button>
        </form>

        <p class="privacy-note">
            &#128274; This report is read-only data collected by the plugin — no passwords, no database contents, no sensitive files.
            The plugin auto-deactivates after 48 hours and can be deleted at any time.
        </p>

        <a href="https://reviveguard.com" class="skip-link">I'll skip this for now &rarr;</a>
    </div>
</div>
</body>
</html>
