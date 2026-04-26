<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 24px;
            box-sizing: border-box;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 1.25rem;
        }
        p {
            margin: 0 0 16px;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            background: #0b1220;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            box-sizing: border-box;
            margin-bottom: 12px;
        }
        button {
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            color: #fca5a5;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Access Required</h1>
        <p>Enter the access code to continue to the admin panel.</p>

        @if ($errors->has('code'))
            <div class="error">{{ $errors->first('code') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.access.submit') }}">
            @csrf
            <label for="code">Access Code</label>
            <input id="code" name="code" type="password" autocomplete="off" required>
            <button type="submit">Continue</button>
        </form>
    </div>
</body>
</html>
