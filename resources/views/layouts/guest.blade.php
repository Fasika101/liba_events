<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    @include('partials.mobile-head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Liba Events') }} — Sign In</title>

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
        align-items: center;
        justify-content: center;
        background: #0d1b2a;
        position: relative;
        overflow: hidden;
        padding: 20px;
    }

    /* Animated gradient orbs in background */
    body::before, body::after {
        content: '';
        position: fixed;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .5;
        animation: float 8s ease-in-out infinite;
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

    /* Card */
    .login-card {
        width: 100%;
        max-width: 420px;
        background: rgba(255,255,255,.06);
        backdrop-filter: blur(24px) saturate(160%);
        -webkit-backdrop-filter: blur(24px) saturate(160%);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 20px;
        box-shadow: 0 32px 80px rgba(0,0,0,.4);
        padding: 40px 36px 36px;
        position: relative;
        z-index: 10;
        animation: slideUp .4s cubic-bezier(.16,1,.3,1);
    }
    @keyframes slideUp {
        from { opacity:0; transform:translateY(24px); }
        to   { opacity:1; transform:translateY(0); }
    }

    /* Logo */
    .login-logo {
        text-align: center;
        margin-bottom: 28px;
    }
    .login-logo .icon-wrap {
        width: 60px; height: 60px;
        background: linear-gradient(135deg, #3730a3, #4f6ef7);
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: #fff;
        box-shadow: 0 8px 24px rgba(79,110,247,.5);
        margin-bottom: 14px;
    }
    .login-logo h1 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.03em;
        margin: 0;
    }
    .login-logo p {
        color: rgba(255,255,255,.5);
        font-size: .82rem;
        margin: 4px 0 0;
        font-weight: 400;
    }

    /* Form */
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
        font-size: 16px; /* Prevents iOS zoom on focus */
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
        border-right: none !important;
        color: rgba(255,255,255,.5) !important;
        border-radius: 10px 0 0 10px !important;
    }
    .input-group .form-control { border-left: none !important; border-radius: 0 10px 10px 0 !important; }

    .form-group { margin-bottom: 20px; }

    /* Remember me */
    .custom-control-label {
        color: rgba(255,255,255,.6);
        font-size: .82rem;
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        cursor: pointer;
    }
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #4f6ef7;
        border-color: #4f6ef7;
    }

    /* Submit button */
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
        letter-spacing: .02em;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(79,110,247,.45);
        transition: all .18s ease;
        margin-top: 8px;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(79,110,247,.55);
    }
    .btn-login:active { transform: translateY(0); }

    /* Footer note */
    .login-footer {
        text-align: center;
        margin-top: 20px;
        color: rgba(255,255,255,.3);
        font-size: .74rem;
    }
    </style>
    @include('partials.mobile-styles')
</head>
<body class="guest-auth">
    <div class="login-card">
        <div class="login-logo">
            @if(file_exists(public_path('images/logo.png')) || file_exists(public_path('images/logo.svg')) || file_exists(public_path('images/logo.jpg')))
                @php
                    $ext = file_exists(public_path('images/logo.svg')) ? 'svg'
                         : (file_exists(public_path('images/logo.png')) ? 'png' : 'jpg');
                @endphp
                <img src="{{ asset('images/logo.'.$ext) }}"
                     alt="{{ config('app.name') }}"
                     style="max-height:72px;max-width:220px;object-fit:contain;margin-bottom:14px;
                            filter:drop-shadow(0 4px 16px rgba(79,110,247,.4));">
            @else
                <div class="icon-wrap">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            @endif
            <!-- <h1>{{ config('app.name', 'Liba Events') }}</h1> -->
            <p>Ticket Sales Management</p>
        </div>

        {{ $slot }}

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; All rights reserved
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
