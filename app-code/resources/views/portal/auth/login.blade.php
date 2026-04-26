<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Sign In — ReviveGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 10% 0%, #0d2a3e 0%, #041226 40%, #020817 100%);
        }
    </style>
</head>
<body class="h-full text-slate-100">
<div class="min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-8 items-stretch">
        <section class="hidden lg:flex flex-col justify-between rounded-3xl border border-emerald-500/20 bg-slate-950/60 p-8">
            <div>
                <div class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span class="text-white">Revive</span><span class="text-emerald-400">Guard</span>
                </div>
                <p class="mt-2 text-sm text-slate-400">A website care product by WaybackRevive LLC</p>
                <h1 class="mt-8 text-4xl font-bold leading-tight text-white">Secure portal access for your protected website.</h1>
                <p class="mt-4 text-slate-300">Track uptime, reports, backups, and support in one trusted workspace.</p>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-3">
                    <div class="font-semibold text-emerald-300">24/7</div>
                    <div class="text-slate-400">Monitoring</div>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-3">
                    <div class="font-semibold text-emerald-300">Cloud</div>
                    <div class="text-slate-400">Backups</div>
                </div>
                <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-3">
                    <div class="font-semibold text-emerald-300">Trusted</div>
                    <div class="text-slate-400">Support</div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-700 bg-slate-900/85 p-6 sm:p-8 shadow-2xl shadow-slate-950/40">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span class="text-white">Revive</span><span class="text-emerald-400">Guard</span>
                </div>
                <p class="mt-2 text-sm text-slate-400">Client Portal</p>
                <h2 class="mt-4 text-2xl font-semibold text-white">Sign in to your dashboard</h2>
                <p class="mt-2 text-sm text-slate-400">Managed by WaybackRevive LLC</p>
            </div>

            @if (session('status'))
                <div class="mb-4 p-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-200 text-sm">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3 py-2.5 border border-slate-600 bg-slate-950 text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/80 @error('email') border-red-400 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2.5 border border-slate-600 bg-slate-950 text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/80 @error('password') border-red-400 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-400">
                        <input type="checkbox" name="remember" class="rounded border-slate-500 bg-slate-950 text-emerald-500"> Remember me
                    </label>
                    <a href="{{ route('portal.password.request') }}" class="text-emerald-300 hover:text-emerald-200">Forgot password?</a>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-semibold rounded-lg transition-colors">
                    Sign in securely
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500">Your data is protected and monitored continuously by ReviveGuard operations.</p>
        </section>
    </div>
</div>
</body>
</html>
