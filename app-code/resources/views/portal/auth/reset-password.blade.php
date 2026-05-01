<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — ReviveGuard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--g:#059669;--gh:#047857;--gl:#d1fae5;--gb:#a7f3d0;--bg:#f7f9fc;--card:#fff;--t:#111827;--tm:#374151;--td:#6b7280;--bd:#e5e7eb;--re:#dc2626;--rel:#fee2e2}
        body{font-family:'Inter',sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1.25rem;color:var(--t)}
        a{color:var(--g);text-decoration:none}a:hover{text-decoration:underline}
        .brand{display:inline-flex;align-items:center;gap:.4rem;font-size:1.35rem;font-weight:800;letter-spacing:-.025em;text-decoration:none}
        .brand .dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--g)}
        .brand .r{color:var(--t)}.brand .g{color:var(--g)}
        .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--bd);border-radius:1rem;padding:2.25rem;box-shadow:0 4px 24px rgba(0,0,0,.07)}
        .hd{text-align:center;margin-bottom:1.75rem}
        .hd h1{margin-top:.85rem;font-size:1.25rem;font-weight:700;color:var(--t)}
        .hd p{margin-top:.3rem;font-size:.82rem;color:var(--td)}
        .f{margin-bottom:1rem}
        .f label{display:block;font-size:.82rem;font-weight:600;color:var(--tm);margin-bottom:.4rem}
        .f input{width:100%;padding:.65rem .875rem;border:1.5px solid var(--bd);border-radius:.55rem;font-size:.875rem;font-family:inherit;color:var(--t);background:#fff;outline:none;transition:border-color .15s,box-shadow .15s}
        .f input:focus{border-color:var(--g);box-shadow:0 0 0 3px rgba(5,150,105,.12)}
        .f .fe{font-size:.78rem;color:var(--re);margin-top:.3rem}
        .f input.err{border-color:var(--re)}
        .btn{width:100%;padding:.75rem;background:var(--g);color:#fff;border:none;border-radius:.6rem;font-size:.9rem;font-weight:700;font-family:inherit;cursor:pointer;transition:background .15s}
        .btn:hover{background:var(--gh)}
    </style>
</head>
<body>
<div class="card">
    <div class="hd">
        <a href="https://reviveguard.com" class="brand">
            <span class="dot"></span><span class="r">Revive</span><span class="g">Guard</span>
        </a>
        <h1>Choose a new password</h1>
        <p>Set a strong password for your secure portal access.</p>
    </div>

    <form method="POST" action="{{ route('portal.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="f">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   class="{{ $errors->has('email') ? 'err' : '' }}">
            @error('email')<p class="fe">{{ $message }}</p>@enderror
        </div>

        <div class="f">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required minlength="8"
                   class="{{ $errors->has('password') ? 'err' : '' }}">
            @error('password')<p class="fe">{{ $message }}</p>@enderror
        </div>

        <div class="f">
            <label for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
        </div>

        <button type="submit" class="btn">Reset password →</button>
    </form>
</div>
</body>
</html>
