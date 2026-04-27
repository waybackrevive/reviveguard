<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your Account — ReviveGuard</title>
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

        {{-- Left panel (desktop) --}}
        <section class="hidden lg:flex flex-col justify-between rounded-3xl border border-emerald-500/20 bg-slate-950/60 p-8">
            <div>
                <div class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span class="text-white">Revive</span><span class="text-emerald-400">Guard</span>
                </div>
                <p class="mt-2 text-sm text-slate-400">A website care product by WaybackRevive LLC</p>
                <h1 class="mt-8 text-4xl font-bold leading-tight text-white">You're one step away from your protected dashboard.</h1>
                <p class="mt-4 text-slate-300">Set a strong password to secure your account. You'll be taken straight to your portal after.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/70 p-4 text-sm text-slate-400">
                <span class="inline-flex items-center gap-1.5 text-emerald-300 font-medium mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Magic link active
                </span><br>
                This activation link is unique to your account and expires in 72 hours.
            </div>
        </section>

        {{-- Right panel — form --}}
        <section class="rounded-3xl border border-slate-700 bg-slate-900/85 p-6 sm:p-8 shadow-2xl shadow-slate-950/40">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span class="text-white">Revive</span><span class="text-emerald-400">Guard</span>
                </div>
                <p class="mt-2 text-sm text-slate-400">Client Portal</p>
                <h2 class="mt-4 text-2xl font-semibold text-white">Activate your account</h2>
                <p class="mt-2 text-sm text-slate-400">Hi {{ $client->name }} — set your password to get started.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-lg border border-red-500/30 bg-red-500/10 text-red-200 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.activate.submit', ['client' => $client->id, 'token' => $token]) }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">
                        Choose a password
                    </label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border {{ $errors->has('password') ? 'border-red-500' : 'border-slate-700' }} text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                           placeholder="Minimum 8 characters">
                    @error('password')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password_confirmation">
                        Confirm password
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                           placeholder="Repeat your password">
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white font-semibold text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    Activate account &amp; go to dashboard
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500">
                Having trouble?
                <a href="{{ route('portal.password.request') }}" class="text-emerald-400 hover:text-emerald-300 underline">
                    Use forgot password instead
                </a>
            </p>
        </section>

    </div>
</div>
</body>
</html>
