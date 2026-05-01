<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Received &mdash; ReviveGuard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
        @if ($waitlisted)
            <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">You're on the waitlist</h2>
            <p class="text-gray-500 text-sm">
                We've received your request, but we've reached our evaluation limit for this month.
                You're on the waitlist and we'll reach out as soon as a spot opens up.
            </p>
        @else
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Evaluation received!</h2>
            <p class="text-gray-500 text-sm">
                We've received your evaluation request and sent a confirmation to your inbox.
                Our team will review your site and get back to you within 48 hours.
            </p>
        @endif

        <a
            href="{{ url('/') }}"
            class="inline-block mt-6 text-sm text-blue-600 hover:underline"
        >
            &larr; Back to homepage
        </a>
    </div>
</body>
</html>
