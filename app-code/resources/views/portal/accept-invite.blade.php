<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Account — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--re:#dc2626}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem;color:var(--t)}
        a{color:var(--g);text-decoration:none}a:hover{text-decoration:underline}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.35rem;font-weight:800;letter-spacing:-.025em;text-decoration:none}
        .brand .dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .card{width:100%;max-width:440px;background:var(--card);border:1px solid var(--bd);border-radius:1rem;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07)}
        .card-top{background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-bottom:1px solid var(--gb);padding:1.75rem 2rem;text-align:center}
        .card-top .sub{font-size:.78rem;color:var(--td);margin-top:.25rem}
        .card-top h2{margin-top:.85rem;font-size:1.15rem;font-weight:700;color:var(--t)}
        .card-body{padding:1.75rem 2rem}
        .welcome-msg{background:var(--gl);border:1px solid var(--gb);border-radius:.6rem;padding:.75rem 1rem;font-size:.83rem;color:#065f46;margin-bottom:1.25rem;line-height:1.55}
        .f{margin-bottom:1rem}
        .f label{display:block;font-size:.82rem;font-weight:600;color:var(--tm);margin-bottom:.4rem}
        .f input{width:100%;padding:.65rem .875rem;border:1.5px solid var(--bd);border-radius:.55rem;font-size:.875rem;font-family:inherit;color:var(--t);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s}
        .f input:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(5,150,105,.12)}
        .f .fe{font-size:.78rem;color:var(--re);margin-top:.3rem}
        .a-err{background:#fee2e2;border:1px solid #fca5a5;border-radius:.55rem;padding:.7rem 1rem;font-size:.82rem;color:#991b1b;margin-bottom:1.25rem}
        .btn{width:100%;padding:.75rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;transition:background .15s}
        .btn:hover{background:var(--gh)}
        .foot{font-size:.76rem;color:var(--td);text-align:center;margin-top:1rem}
    </style>
</head>
<body>
<div class="card">
    <div class="card-top">
        <a href="https://reviveguard.com" class="brand">
            <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
        </a>
        <p class="sub">Client Portal — Invite Accepted</p>
        <h2>Create your portal account</h2>
    </div>
    <div class="card-body">

        <div class="welcome-msg">
            👋 &nbsp;Welcome, <strong>{{ $invite->name }}</strong>!
            @if ($invite->site_url)
                <br><span style="font-size:.78rem;opacity:.85">Site: {{ $invite->site_url }}</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="a-err">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('portal.accept-invite.submit') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="f">
                <label for="password">Choose a password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="new-password" placeholder="At least 8 characters">
            </div>

            <div class="f">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       autocomplete="new-password" placeholder="Repeat your password">
            </div>

            <button type="submit" class="btn">Create Account &amp; Sign In →</button>
        </form>

        <p class="foot">Problems? <a href="mailto:support@reviveguard.com">Contact support</a></p>
    </div>
</div>
</body>
</html>
