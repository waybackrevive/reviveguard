<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Sign In — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--re:#dc2626;--rel:#fee2e2}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;color:var(--t)}
        a{color:var(--g);text-decoration:none}a:hover{text-decoration:underline}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.35rem;font-weight:800;letter-spacing:-.025em;text-decoration:none}
        .brand .dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem}
        .grid{width:100%;max-width:900px;display:grid;gap:1.5rem}
        @media(min-width:860px){.grid{grid-template-columns:1fr 1fr;align-items:stretch}}
        /* left panel */
        .pl{display:none;flex-direction:column;justify-content:space-between;background:linear-gradient(150deg,#f0fdf4,#ecfdf5 60%,#f7f9fc);border:1px solid var(--gb);border-radius:1.25rem;padding:2.25rem}
        @media(min-width:860px){.pl{display:flex}}
        .pl-sub{font-size:.78rem;color:var(--td);margin-top:.3rem}
        .pl h1{margin-top:2rem;font-size:1.8rem;font-weight:800;line-height:1.2;color:var(--t)}
        .pl p{margin-top:.7rem;font-size:.88rem;color:var(--tm);line-height:1.65}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;text-align:center}
        .stat{border:1px solid var(--bd);border-radius:.7rem;background:#fff;padding:.8rem .4rem}
        .stat .v{font-size:.9rem;font-weight:700;color:var(--g)}
        .stat .l{font-size:.7rem;color:var(--td);margin-top:.12rem}
        /* right panel */
        .pr{background:var(--card);border:1px solid var(--bd);border-radius:1.25rem;padding:2.25rem;box-shadow:0 4px 24px rgba(0,0,0,.07)}
        .hd{text-align:center;margin-bottom:1.75rem}
        .hd h2{margin-top:.85rem;font-size:1.25rem;font-weight:700;color:var(--t)}
        .hd p{margin-top:.3rem;font-size:.82rem;color:var(--td)}
        /* form */
        .f{margin-bottom:1rem}
        .f label{display:block;font-size:.82rem;font-weight:600;color:var(--tm);margin-bottom:.4rem}
        .f input{width:100%;padding:.65rem .875rem;border:1.5px solid var(--bd);border-radius:.55rem;font-size:.875rem;font-family:inherit;color:var(--t);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s}
        .f input:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(5,150,105,.12)}
        .f .fe{font-size:.78rem;color:var(--re);margin-top:.3rem}
        .f input.err{border-color:var(--re)}
        .a-err{background:var(--rel);border:1px solid #fca5a5;border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#991b1b;margin-bottom:1.25rem}
        .a-ok{background:var(--gl);border:1px solid var(--gb);border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#065f46;margin-bottom:1.25rem}
        .row-bw{display:flex;justify-content:space-between;align-items:center;font-size:.82rem;margin-bottom:1.25rem;color:var(--td)}
        .row-bw label{display:flex;align-items:center;gap:.4rem;cursor:pointer}
        .btn{width:100%;padding:.75rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;transition:background .15s;letter-spacing:.01em}
        .btn:hover{background:var(--gh)}
        .foot{font-size:.76rem;color:var(--td);text-align:center;margin-top:1.25rem;line-height:1.6}
    </style>
</head>
<body>
<div class="wrap">
    <div class="grid">

        <!-- Left brand panel -->
        <section class="pl">
            <div>
                <a href="https://reviveguard.com" class="brand">
                    <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
                </a>
                <p class="pl-sub">A product by the <a href="https://waybackrevive.com" target="_blank" rel="noopener">WaybackRevive</a> team</p>
                <h1>Your site is being watched. Sign in to see how.</h1>
                <p>Track uptime, backups, reports, and support — everything about your protected website, in one trusted place.</p>
            </div>
            <div class="stats">
                <div class="stat"><div class="v">24/7</div><div class="l">Monitoring</div></div>
                <div class="stat"><div class="v">Cloud</div><div class="l">Backups</div></div>
                <div class="stat"><div class="v">Human</div><div class="l">Support</div></div>
            </div>
        </section>

        <!-- Right form panel -->
        <section class="pr">
            <div class="hd">
                <a href="https://reviveguard.com" class="brand">
                    <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
                </a>
                <h2>Sign in to your portal</h2>
                <p>Client Portal — invite-only access</p>
            </div>

            @if (session('status'))
                <div class="a-ok">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login.submit') }}">
                @csrf

                <div class="f">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="{{ $errors->has('email') ? 'err' : '' }}">
                    @error('email')<p class="fe">{{ $message }}</p>@enderror
                </div>

                <div class="f">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           class="{{ $errors->has('password') ? 'err' : '' }}">
                    @error('password')<p class="fe">{{ $message }}</p>@enderror
                </div>

                <div class="row-bw">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="{{ route('portal.password.request') }}">Forgot password?</a>
                </div>

                <button type="submit" class="btn">Sign in securely →</button>
            </form>

            <p class="foot">Your data is encrypted and monitored continuously.<br>
                Problems signing in? <a href="mailto:support@reviveguard.com">Contact support</a></p>
        </section>

    </div>
</div>
</body>
</html>
