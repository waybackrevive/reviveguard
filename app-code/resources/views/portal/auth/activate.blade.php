<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your Account — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--re:#dc2626}
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
        .step-list{display:flex;flex-direction:column;gap:.9rem;margin-top:auto;padding-top:2rem}
        .step{display:flex;align-items:flex-start;gap:.75rem}
        .sn{flex-shrink:0;width:1.5rem;height:1.5rem;border-radius:50%;background:var(--gl);border:1px solid var(--gb);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--gh);margin-top:.1rem}
        .sb p{font-size:.83rem;font-weight:600;color:var(--t)}
        .sb span{font-size:.76rem;color:var(--td)}
        .pr{background:var(--card);border:1px solid var(--bd);border-radius:1.25rem;padding:2.25rem;box-shadow:0 4px 24px rgba(0,0,0,.07)}
        .hd{text-align:center;margin-bottom:1.75rem}
        .hd h2{margin-top:.85rem;font-size:1.25rem;font-weight:700;color:var(--t)}
        .hd p{margin-top:.3rem;font-size:.82rem;color:var(--td)}
        .f{margin-bottom:1rem}
        .f label{display:block;font-size:.82rem;font-weight:600;color:var(--tm);margin-bottom:.4rem}
        .f input{width:100%;padding:.65rem .875rem;border:1.5px solid var(--bd);border-radius:.55rem;font-size:.875rem;font-family:inherit;color:var(--t);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s}
        .f input:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(5,150,105,.12)}
        .f .fe{font-size:.78rem;color:var(--re);margin-top:.3rem}
        .a-err{background:#fee2e2;border:1px solid #fca5a5;border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#991b1b;margin-bottom:1.25rem}
        .info-box{background:var(--gl);border:1px solid var(--gb);border-radius:.6rem;padding:.8rem 1rem;font-size:.8rem;color:#065f46;margin-bottom:1.25rem;line-height:1.5}
        .btn{width:100%;padding:.75rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;transition:background .15s}
        .btn:hover{background:var(--gh)}
        .foot{font-size:.76rem;color:var(--td);text-align:center;margin-top:1.25rem}
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
                <h1>One step from your protected dashboard.</h1>
                <p>Set a password to secure your account. You'll be taken straight to your portal after.</p>
            </div>
            <div class="step-list">
                <div class="step">
                    <div class="sn">1</div>
                    <div class="sb"><p>Set your password</p><span>Choose a strong password to secure your portal.</span></div>
                </div>
                <div class="step">
                    <div class="sn">2</div>
                    <div class="sb"><p>Access your dashboard</p><span>You'll be logged in automatically after activation.</span></div>
                </div>
                <div class="step">
                    <div class="sn">3</div>
                    <div class="sb"><p>Add your website</p><span>Install the agent plugin — 60 seconds, then you're protected.</span></div>
                </div>
            </div>
        </section>

        <section class="pr">
            <div class="hd">
                <a href="https://reviveguard.com" class="brand">
                    <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
                </a>
                <h2>Activate your account</h2>
                <p>Hi {{ $client->name }} — set your password to get started.</p>
            </div>

            <div class="info-box">
                🔐 &nbsp;This activation link is unique to your account and expires in 72 hours.
            </div>

            @if ($errors->any())
                <div class="a-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('portal.activate.submit', ['client' => $client->id, 'token' => $token]) }}">
                @csrf

                <div class="f">
                    <label for="password">Choose a password</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           autocomplete="new-password" placeholder="Minimum 8 characters">
                    @error('password')<p class="fe">{{ $message }}</p>@enderror
                </div>

                <div class="f">
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           required minlength="8" autocomplete="new-password" placeholder="Repeat your password">
                </div>

                <button type="submit" class="btn">Activate &amp; go to dashboard →</button>
            </form>

            <p class="foot">Need help? <a href="mailto:support@reviveguard.com">support@reviveguard.com</a></p>
        </section>

    </div>
</div>
</body>
</html>
