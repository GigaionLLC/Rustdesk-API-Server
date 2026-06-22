<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · rustdesk-api</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="{{ asset('assets/css/theme-dark.css') }}" rel="stylesheet">
    <style>
        .rd-login { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .rd-login__card { width: 100%; max-width: 400px; }
        .rd-login__brand { display:flex; align-items:center; gap:10px; justify-content:center;
            font-weight:700; font-size:20px; color:var(--rd-text-bright); margin-bottom:22px; }
        .rd-login__brand .rd-logo { width:34px; height:34px; font-size:18px; }
    </style>
</head>
<body>
<div class="rd-login">
    <div class="rd-login__card">
        <div class="rd-login__brand">
            <span class="rd-logo"><i class="ri-remote-control-line"></i></span> rustdesk-api
        </div>

        <div class="rd-card"><div class="rd-card__body">
            <h1 class="rd-page-title" style="text-align:center;">Welcome back</h1>
            <p class="rd-muted" style="text-align:center;margin-top:0;margin-bottom:22px;">
                Sign in to the admin console
            </p>

            @if ($errors->any())
                <div class="rd-toast rd-toast--error" style="margin-bottom:16px;">
                    <i class="ri-error-warning-line"></i><span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/admin/login">
                @csrf
                <div class="rd-field">
                    <label class="rd-label" for="username">Username</label>
                    <input class="rd-input" id="username" name="username" autocomplete="username"
                           value="{{ old('username') }}" required autofocus>
                </div>
                <div class="rd-field">
                    <label class="rd-label" for="password">Password</label>
                    <input class="rd-input" id="password" name="password" type="password"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="rd-btn rd-btn--primary" style="width:100%;">
                    <i class="ri-login-box-line"></i> Sign in
                </button>
            </form>
        </div></div>

        <p class="rd-muted" style="text-align:center;margin-top:18px;font-size:12px;">
            RustDesk self-hosted API &amp; admin console
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
