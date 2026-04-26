<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — ReviveGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 10% 0%, #0d2a3e 0%, #041226 40%, #020817 100%);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 text-slate-100">
<div class="w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900/85 p-8 shadow-2xl shadow-slate-950/40">

    <div class="text-center mb-8">
        <span class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span><span class="text-white">Revive</span><span class="text-emerald-400">Guard</span></span>
        <h1 class="mt-4 text-2xl font-semibold text-white">Reset your password</h1>
        <p class="mt-2 text-sm text-slate-400">Enter your email and we will send a secure reset link.</p>
        <p class="mt-1 text-xs text-slate-500">Operated by WaybackRevive LLC</p>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-200 text-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-200 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full px-3 py-2.5 border border-slate-600 bg-slate-950 text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/80 @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-semibold rounded-lg transition-colors">
            Send reset link
        </button>

        <p class="text-center text-sm text-slate-400">
            <a href="{{ route('portal.login') }}" class="text-emerald-300 hover:text-emerald-200">Back to sign in</a>
        </p>
    </form>

</div>
</body>
</html>
