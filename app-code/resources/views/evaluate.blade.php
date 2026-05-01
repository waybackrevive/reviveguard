<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Site Evaluation &mdash; ReviveGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">
    <div class="max-w-xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900">ReviveGuard</h1>
            <p class="text-gray-500 mt-1 text-sm">Free WordPress Site Evaluation</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Get your site evaluated</h2>
            <p class="text-gray-500 text-sm mb-6">
                Tell us about your site and we'll review it within 48 hours. No commitment required.
            </p>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-5">
                    <ul class="text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('evaluate.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your name <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Jane Smith"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email address <span class="text-red-500">*</span></label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="jane@example.com"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Website URL <span class="text-red-500">*</span></label>
                    <input
                        type="url"
                        name="site_url"
                        value="{{ old('site_url') }}"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="https://example.com"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site type <span class="text-red-500">*</span></label>
                    <select
                        name="site_type"
                        required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Select...</option>
                        <option value="wordpress" @selected(old('site_type') === 'wordpress')>WordPress</option>
                        <option value="html"      @selected(old('site_type') === 'html')>HTML / Static</option>
                        <option value="other"     @selected(old('site_type') === 'other')>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">What's your biggest concern?</label>
                    <textarea
                        name="concern"
                        rows="3"
                        maxlength="2000"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        placeholder="Site speed, security, outdated plugins..."
                    >{{ old('concern') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors"
                >
                    Submit for Free Evaluation
                </button>

                <p class="text-xs text-center text-gray-400">
                    We review evaluations within 48 hours. No spam, no pressure.
                </p>
            </form>
        </div>
    </div>
</body>
</html>
