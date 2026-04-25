<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4">
<div class="w-full max-w-sm">

    <div class="text-center mb-8">
        <span class="text-2xl font-bold text-blue-600">ReviveGuard</span>
        <h1 class="mt-4 text-xl font-semibold text-gray-900">Reset your password</h1>
        <p class="mt-2 text-sm text-gray-500">Enter your email and we'll send you a reset link.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('portal.password.email') }}" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors">
            Send reset link
        </button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('portal.login') }}" class="text-blue-600 hover:underline">Back to sign in</a>
        </p>
    </form>

</div>
</body>
</html>
