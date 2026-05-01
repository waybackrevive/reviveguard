<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;color:var(--t)}
        a{color:var(--g);text-decoration:none}a:hover{text-decoration:underline}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.35rem;font-weight:800;letter-spacing:-.025em;text-decoration:none}
        .brand .dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem}
        .grid{width:100%;max-width:900px;display:grid;gap:1.5rem}
        @media(min-width:860px){.grid{grid-template-columns:1fr 1fr;align-items:stretch}}
        .pl{display:none;flex-direction:column;justify-content:space-between;background:linear-gradient(150deg,#f0fdf4,#ecfdf5 60%,#f7f9fc);border:1px solid var(--gb);border-radius:1.25rem;padding:2.25rem}
        @media(min-width:860px){.pl{display:flex}}
        .pl-sub{font-size:.78rem;color:var(--td);margin-top:.3rem}
        .pl h1{margin-top:2rem;font-size:1.8rem;font-weight:800;line-height:1.2;color:var(--t)}
        .pl p{margin-top:.7rem;font-size:.88rem;color:var(--tm);line-height:1.65}
        .step-list{display:flex;flex-direction:column;gap:.9rem;padding-top:2rem}
        .step{display:flex;align-items:flex-start;gap:.75rem}
        .sn{flex-shrink:0;width:1.5rem;height:1.5rem;border-radius:50%;background:var(--gl);border:1px solid var(--gb);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--gh);margin-top:.1rem}
        .sb p{font-size:.83rem;font-weight:600;color:var(--t)}
        .sb span{font-size:.76rem;color:var(--td)}
        .pr{background:var(--card);border:1px solid var(--bd);border-radius:1.25rem;padding:2.25rem;box-shadow:0 4px 24px rgba(0,0,0,.07);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
        .check{width:3.5rem;height:3.5rem;border-radius:50%;background:var(--gl);border:2px solid var(--gb);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem}
        .check svg{width:1.75rem;height:1.75rem;color:var(--g)}
        .pr h2{font-size:1.35rem;font-weight:800;color:var(--t)}
        .pr .sub{font-size:.88rem;color:var(--tm);margin-top:.5rem;line-height:1.65;max-width:300px}
        .inbox-box{margin-top:1.5rem;background:var(--gl);border:1px solid var(--gb);border-radius:.75rem;padding:1rem 1.25rem;text-align:left;width:100%}
        .inbox-box .ib-title{font-size:.82rem;font-weight:700;color:var(--gh);margin-bottom:.5rem}
        .inbox-box p{font-size:.8rem;color:var(--tm);line-height:1.55}
        .foot{font-size:.78rem;color:var(--td);margin-top:1.5rem}
    </style>
</head>
<body>
<div class="wrap">
    <div class="grid">

        <section class="pl">
            <div>
                <a href="https://reviveguard.com" class="brand">
                    <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
                </a>
                <p class="pl-sub">A product by the <a href="https://waybackrevive.com" target="_blank" rel="noopener">WaybackRevive</a> team</p>
                <h1>Your site is now under protection.</h1>
                <p>Payment confirmed. We're setting up your account right now — check your inbox for the activation link.</p>
            </div>
            <div class="step-list">
                <div class="step">
                    <div class="sn">1</div>
                    <div class="sb"><p>Check your email</p><span>Activation link sent to your payment email address.</span></div>
                </div>
                <div class="step">
                    <div class="sn">2</div>
                    <div class="sb"><p>Set your password</p><span>Click the link and create a secure password for your portal.</span></div>
                </div>
                <div class="step">
                    <div class="sn">3</div>
                    <div class="sb"><p>Add your website</p><span>Install the agent plugin — your site is monitored within minutes.</span></div>
                </div>
            </div>
        </section>

        <section class="pr">
            <div class="check">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <a href="https://reviveguard.com" class="brand" style="margin-bottom:.85rem">
                <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
            </a>
            <h2>Payment confirmed!</h2>
            <p class="sub">Your ReviveGuard account is being created. You'll receive an email with your activation link within the next minute.</p>

            <div class="inbox-box">
                <div class="ib-title">📬 &nbsp;Check your inbox</div>
                <p>Look for an email from <strong>ReviveGuard</strong> with the subject "Activate your account". The link is valid for 72 hours.</p>
            </div>

            <p class="foot">
                Didn't receive the email? <a href="mailto:support@reviveguard.com">Contact support</a>
            </p>
        </section>

    </div>
</div>
</body>
</html>
