<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed — ReviveGuard</title>
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
                <h1 class="mt-8 text-4xl font-bold leading-tight text-white">Your site is now under protection.</h1>
                <p class="mt-4 text-slate-300">Payment confirmed. We're setting up your account right now — it takes less than a minute. Check your inbox for the activation link.</p>
            </div>

            {{-- What happens next --}}
            <div class="space-y-4 mt-8">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex-shrink-0 h-6 w-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs font-bold">1</div>
                    <div>
                        <p class="text-sm font-medium text-slate-200">Check your email</p>
                        <p class="text-xs text-slate-400 mt-0.5">You'll receive an activation link at the email you used for payment.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex-shrink-0 h-6 w-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs font-bold">2</div>
                    <div>
                        <p class="text-sm font-medium text-slate-200">Set your password</p>
                        <p class="text-xs text-slate-400 mt-0.5">Click the link in the email and create a secure password for your portal.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex-shrink-0 h-6 w-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs font-bold">3</div>
                    <div>
                        <p class="text-sm font-medium text-slate-200">Add your website</p>
                        <p class="text-xs text-slate-400 mt-0.5">Install the lightweight agent plugin and your site is live in the portal.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Right panel — confirmation --}}
        <section class="rounded-3xl border border-slate-700 bg-slate-900/85 p-6 sm:p-8 shadow-2xl shadow-slate-950/40 flex flex-col justify-center">
            <div class="text-center">
                {{-- Logo --}}
                <div class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span class="text-white">Revive</span><span class="text-emerald-400">Guard</span>
                </div>
                <p class="mt-2 text-sm text-slate-400">Client Portal</p>

                {{-- Checkmark --}}
                <div class="mt-8 flex justify-center">
                    <div class="h-20 w-20 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                        <svg class="h-10 w-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>

                <h2 class="mt-6 text-2xl font-semibold text-white">Payment confirmed!</h2>
                <p class="mt-3 text-slate-300 text-sm leading-relaxed">
                    Your account is being created. You'll receive an <strong class="text-emerald-300">activation email</strong> at the address you used during checkout within the next few minutes.
                </p>
                <p class="mt-2 text-slate-400 text-sm">
                    Click the link in that email to set your password and access your portal.
                </p>

                {{-- Email hint --}}
                <div class="mt-6 rounded-xl border border-slate-700 bg-slate-800/60 p-4 text-sm text-slate-400 text-left">
                    <span class="inline-flex items-center gap-1.5 text-emerald-300 font-medium mb-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Check your inbox
                    </span><br>
                    Subject line: <span class="text-slate-200 font-medium">"Welcome to ReviveGuard — activate your account"</span><br>
                    <span class="mt-1 block text-xs">Not there in 5 minutes? Check your spam folder or contact <a href="mailto:support@reviveguard.com" class="text-emerald-400 hover:text-emerald-300">support@reviveguard.com</a></span>
                </div>

                {{-- Login link --}}
                <p class="mt-6 text-sm text-slate-500">
                    Already activated your account?
                    <a href="{{ route('portal.login') }}" class="text-emerald-400 hover:text-emerald-300 font-medium">Sign in →</a>
                </p>
            </div>
        </section>

    </div>
</div>
</body>
</html>
