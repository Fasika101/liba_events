<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    @include('partials.mobile-head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Liba Events') }} — Register Organization</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
        font-family: 'Inter', system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
        min-height: 100vh;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        background: #0d1b2a;
        position: relative;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 24px 20px 40px;
    }

    body::before, body::after {
        content: '';
        position: fixed;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .5;
        animation: float 8s ease-in-out infinite;
        pointer-events: none;
    }
    body::before {
        width: 500px; height: 500px;
        background: radial-gradient(circle, #4f6ef7 0%, transparent 70%);
        top: -150px; left: -100px;
    }
    body::after {
        width: 400px; height: 400px;
        background: radial-gradient(circle, #7c5cbf 0%, transparent 70%);
        bottom: -120px; right: -80px;
        animation-delay: -4s;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0) scale(1); }
        50%       { transform: translateY(-20px) scale(1.05); }
    }

    .login-card {
        width: 100%;
        max-width: 640px;
        background: rgba(255,255,255,.06);
        backdrop-filter: blur(24px) saturate(160%);
        -webkit-backdrop-filter: blur(24px) saturate(160%);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 20px;
        box-shadow: 0 32px 80px rgba(0,0,0,.4);
        padding: 36px 32px 32px;
        position: relative;
        z-index: 10;
        animation: slideUp .4s cubic-bezier(.16,1,.3,1);
        margin: auto 0;
    }
    @keyframes slideUp {
        from { opacity:0; transform:translateY(24px); }
        to   { opacity:1; transform:translateY(0); }
    }

    .login-logo {
        text-align: center;
        margin-bottom: 24px;
    }
    .login-logo .icon-wrap {
        width: 56px; height: 56px;
        background: linear-gradient(135deg, #3730a3, #4f6ef7);
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        box-shadow: 0 8px 24px rgba(79,110,247,.5);
        margin-bottom: 12px;
    }
    .login-logo h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.03em;
        margin: 0;
    }
    .login-logo p {
        color: rgba(255,255,255,.5);
        font-size: .82rem;
        margin: 4px 0 0;
    }

    label {
        font-weight: 600;
        font-size: .78rem;
        color: rgba(255,255,255,.7);
        letter-spacing: .02em;
        text-transform: uppercase;
        margin-bottom: 6px;
        display: block;
    }

    .form-control {
        background: rgba(255,255,255,.08) !important;
        border: 1.5px solid rgba(255,255,255,.12) !important;
        border-radius: 10px !important;
        color: #fff !important;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        padding: 10px 14px;
        transition: border-color .18s, box-shadow .18s;
    }
    @media (min-width: 992px) {
        .form-control { font-size: .88rem; }
    }
    .form-control::placeholder { color: rgba(255,255,255,.3); }
    .form-control:focus {
        background: rgba(255,255,255,.12) !important;
        border-color: #4f6ef7 !important;
        box-shadow: 0 0 0 3px rgba(79,110,247,.3) !important;
        outline: none;
        color: #fff !important;
    }
    .form-control.is-invalid { border-color: #ef4444 !important; }
    .invalid-feedback { color: #fca5a5; font-size: .77rem; margin-top: 4px; }

    .input-group-text {
        background: rgba(255,255,255,.08) !important;
        border: 1.5px solid rgba(255,255,255,.12) !important;
        color: rgba(255,255,255,.7) !important;
        border-radius: 10px 0 0 10px !important;
        font-size: .85rem;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0 !important; }
    .input-group .input-group-prepend + .form-control { border-left: none !important; }

    .form-group { margin-bottom: 18px; }
    .section-title {
        color: rgba(255,255,255,.85);
        font-size: .9rem;
        font-weight: 700;
        margin: 8px 0 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .btn-login {
        width: 100%;
        padding: 11px;
        background: linear-gradient(135deg, #3730a3, #4f6ef7);
        border: none;
        border-radius: 10px;
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: .9rem;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(79,110,247,.45);
        transition: all .18s ease;
    }
    .btn-login:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(79,110,247,.55);
    }
    .btn-login:disabled { opacity: .6; cursor: not-allowed; }

    .btn-secondary-outline {
        width: 100%;
        padding: 10px;
        background: transparent;
        border: 1.5px solid rgba(255,255,255,.2);
        border-radius: 10px;
        color: rgba(255,255,255,.8);
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: .85rem;
        cursor: pointer;
        transition: all .18s ease;
    }
    .btn-secondary-outline:hover {
        background: rgba(255,255,255,.06);
        border-color: rgba(255,255,255,.35);
    }

    .login-footer {
        text-align: center;
        margin-top: 20px;
        color: rgba(255,255,255,.3);
        font-size: .74rem;
    }
    .help-text {
        color: rgba(255,255,255,.45);
        font-size: .75rem;
        margin-top: 4px;
    }
    .alert-custom {
        border-radius: 10px;
        padding: 12px 14px;
        font-size: .85rem;
        margin-bottom: 16px;
    }
    .alert-success-custom {
        background: rgba(16,185,129,.2);
        color: #6ee7b7;
        border: 1px solid rgba(16,185,129,.3);
    }
    .alert-info-custom {
        background: rgba(59,130,246,.2);
        color: #93c5fd;
        border: 1px solid rgba(59,130,246,.3);
    }
    .otp-input {
        letter-spacing: .4em;
        text-align: center;
        font-size: 1.25rem !important;
        font-weight: 700;
    }
    .back-link {
        color: rgba(255,255,255,.5);
        font-size: .78rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 16px;
    }
    .back-link:hover { color: rgba(255,255,255,.8); }
    </style>
    @include('partials.mobile-styles')
</head>
<body class="guest-wide">
    <div class="login-card">
        <div class="login-logo">
            @if(file_exists(public_path('images/logo.png')) || file_exists(public_path('images/logo.svg')) || file_exists(public_path('images/logo.jpg')))
                @php
                    $ext = file_exists(public_path('images/logo.svg')) ? 'svg'
                         : (file_exists(public_path('images/logo.png')) ? 'png' : 'jpg');
                @endphp
                <img src="{{ asset('images/logo.'.$ext) }}"
                     alt="{{ config('app.name') }}"
                     style="max-height:64px;max-width:200px;object-fit:contain;margin-bottom:12px;
                            filter:drop-shadow(0 4px 16px rgba(79,110,247,.4));">
            @else
                <div class="icon-wrap">
                    <i class="fas fa-building"></i>
                </div>
            @endif
            <h1>Register your organization</h1>
            <p>Submit your details and verify your organization phone number</p>
        </div>

        {{ $slot }}

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; All rights reserved
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
