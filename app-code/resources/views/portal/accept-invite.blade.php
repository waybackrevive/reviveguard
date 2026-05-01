<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Account &mdash; ReviveGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-slate-900 px-8 py-6 text-center">
            <h1 class="text-white text-xl font-semibold tracking-tight">ReviveGuard</h1>
            <p class="text-slate-400 text-sm mt-1">Create your portal account</p>
        </div>

        <div class="px-8 py-6">
            <p class="text-gray-700 text-sm mb-6">
                Welcome, <strong>{{ $invite->name }}</strong>! Set a password to activate your account.
                @if ($invite->site_url)
                    <span class="block mt-1 text-gray-500">Site: {{ $invite->site_url }}</span>
                @endif
            </p>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <ul class="text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.accept-invite.submit') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="At least 8 characters"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm password
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Repeat your password"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors"
                >
                    Create Account &amp; Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
