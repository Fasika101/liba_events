<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Liba Events') }}</title>

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
    /* ══════════════════════════════════════════════════════
       LIBA EVENTS — MODERN UI LAYER  (2025)
       Built on top of AdminLTE 3.2 + Bootstrap 4
    ══════════════════════════════════════════════════════ */

    :root {
        --sidebar-bg:        #0d1b2a;
        --sidebar-border:    rgba(255,255,255,.06);
        --accent:            #4f6ef7;
        --accent-hover:      #3a57e8;
        --accent-soft:       rgba(79,110,247,.12);
        --success:           #10b981;
        --warning:           #f59e0b;
        --danger:            #ef4444;
        --info:              #3b82f6;
        --bg:                #f0f3fb;
        --card-bg:           #ffffff;
        --card-radius:       14px;
        --card-shadow:       0 2px 16px rgba(0,0,0,.07);
        --card-shadow-hover: 0 6px 28px rgba(0,0,0,.12);
        --text:              #1e293b;
        --muted:             #64748b;
        --border:            #e8edf5;
        --transition:        .18s ease;
    }

    /* ── Base ──────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }

    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 14px;
        color: var(--text);
        background: var(--bg);
        -webkit-font-smoothing: antialiased;
    }

    /* ── Scrollbar ─────────────────────────────────────── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #c8d3e8; border-radius: 99px; }

    /* ── Sidebar ───────────────────────────────────────── */
    .main-sidebar {
        background: var(--sidebar-bg) !important;
        border-right: 1px solid var(--sidebar-border);
        box-shadow: 4px 0 24px rgba(0,0,0,.18);
    }

    .brand-link {
        background: rgba(255,255,255,.04);
        border-bottom: 1px solid var(--sidebar-border) !important;
        padding: 14px 18px !important;
        transition: background var(--transition);
    }
    .brand-link:hover { background: rgba(255,255,255,.08) !important; }
    .brand-link .brand-text {
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: -.02em;
        color: #fff;
    }
    .brand-link .fas { color: var(--accent); font-size: 1.15rem; }

    /* User panel */
    .user-panel {
        border-bottom: 1px solid var(--sidebar-border) !important;
        padding: 14px 16px !important;
        margin: 0 0 6px !important;
    }
    .user-panel .info a {
        color: #e2e8f0 !important;
        font-weight: 600;
        font-size: .85rem;
    }
    .user-panel .info small { color: #94a3b8 !important; font-size: .72rem; }
    .user-panel .image span {
        background: linear-gradient(135deg, var(--accent), #7c5cbf) !important;
        font-weight: 700 !important;
        font-size: .9rem !important;
    }

    /* Nav headers */
    .nav-header {
        color: #475569 !important;
        font-size: .65rem !important;
        font-weight: 700 !important;
        letter-spacing: .1em !important;
        padding: 14px 18px 4px !important;
    }

    /* Nav links */
    .nav-sidebar .nav-link {
        border-radius: 8px !important;
        margin: 2px 10px !important;
        padding: 9px 14px !important;
        color: #94a3b8 !important;
        font-weight: 500;
        font-size: .84rem;
        transition: all var(--transition) !important;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .nav-sidebar .nav-link:hover {
        background: rgba(255,255,255,.07) !important;
        color: #e2e8f0 !important;
    }
    .nav-sidebar .nav-link.active,
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
        background: var(--accent-soft) !important;
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(79,110,247,.3) !important;
    }
    .nav-sidebar .nav-link .nav-icon {
        color: inherit !important;
        width: 18px;
        text-align: center;
        font-size: .95rem;
    }
    .nav-sidebar .nav-link.active .nav-icon { color: var(--accent) !important; }

    /* Logout button in sidebar */
    button.nav-link {
        background: none !important;
        border: none !important;
        color: #94a3b8 !important;
        width: calc(100% - 20px);
    }
    button.nav-link:hover {
        background: rgba(239,68,68,.12) !important;
        color: #fca5a5 !important;
    }

    /* ── Top Navbar ────────────────────────────────────── */
    .main-header.navbar {
        background: rgba(255,255,255,.85) !important;
        backdrop-filter: blur(12px) saturate(180%);
        -webkit-backdrop-filter: blur(12px) saturate(180%);
        border-bottom: 1px solid var(--border) !important;
        box-shadow: 0 1px 12px rgba(0,0,0,.06) !important;
        padding: 0 20px;
        min-height: 56px;
    }
    .main-header .nav-link {
        color: var(--text) !important;
        font-weight: 500;
        font-size: .85rem;
        padding: 16px 12px !important;
        transition: color var(--transition);
    }
    .main-header .nav-link:hover { color: var(--accent) !important; }

    .dropdown-menu {
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,.12);
        padding: 6px;
        min-width: 180px;
        animation: dropIn .15s ease;
    }
    @keyframes dropIn {
        from { opacity:0; transform:translateY(-6px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .dropdown-item {
        border-radius: 8px;
        padding: 8px 14px;
        font-size: .84rem;
        font-weight: 500;
        transition: background var(--transition);
    }
    .dropdown-item:hover { background: #f1f5fd; }
    .dropdown-item.text-danger:hover { background: #fff1f2; }

    /* ── Content ───────────────────────────────────────── */
    .content-wrapper { background: var(--bg) !important; }

    .content-header {
        padding: 20px 20px 0 !important;
    }
    .content-header h1 {
        font-size: 1.35rem !important;
        font-weight: 800 !important;
        letter-spacing: -.03em !important;
        color: var(--text);
    }

    /* Breadcrumb */
    .breadcrumb {
        background: transparent !important;
        font-size: .78rem;
        padding: 0;
        margin: 0;
    }
    .breadcrumb-item a { color: var(--accent); }
    .breadcrumb-item.active { color: var(--muted); }
    .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* ── Cards ─────────────────────────────────────────── */
    .card {
        border: 1px solid var(--border) !important;
        border-radius: var(--card-radius) !important;
        box-shadow: var(--card-shadow) !important;
        background: var(--card-bg);
        transition: box-shadow var(--transition);
    }
    .card:hover { box-shadow: var(--card-shadow-hover) !important; }

    .card-outline.card-primary { border-top: 3px solid var(--accent) !important; }
    .card-outline.card-success { border-top: 3px solid var(--success) !important; }
    .card-outline.card-info    { border-top: 3px solid var(--info) !important; }
    .card-outline.card-warning { border-top: 3px solid var(--warning) !important; }
    .card-outline.card-danger  { border-top: 3px solid var(--danger) !important; }

    .card-header {
        background: transparent !important;
        border-bottom: 1px solid var(--border) !important;
        padding: 16px 20px !important;
        border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
    }
    .card-title {
        font-weight: 700 !important;
        font-size: .95rem !important;
        color: var(--text) !important;
        margin: 0;
    }
    .card-footer {
        background: #fafbfd !important;
        border-top: 1px solid var(--border) !important;
        border-radius: 0 0 var(--card-radius) var(--card-radius) !important;
    }

    /* ── Stat boxes (small-box) ────────────────────────── */
    .small-box {
        border-radius: 14px !important;
        overflow: hidden;
        box-shadow: var(--card-shadow) !important;
        transition: transform var(--transition), box-shadow var(--transition) !important;
    }
    .small-box:hover {
        transform: translateY(-3px);
        box-shadow: var(--card-shadow-hover) !important;
    }
    .small-box .inner { padding: 18px 20px 10px !important; }
    .small-box .inner h3 {
        font-size: 2.1rem !important;
        font-weight: 800 !important;
        letter-spacing: -.04em !important;
    }
    .small-box .inner p {
        font-size: .78rem !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .85;
    }
    .small-box .icon { top: 12px !important; right: 16px !important; }
    .small-box .icon > i { font-size: 4rem !important; opacity: .18 !important; }
    .small-box-footer {
        font-size: .75rem !important;
        font-weight: 600 !important;
        letter-spacing: .03em;
        padding: 6px 12px !important;
        background: rgba(0,0,0,.12) !important;
    }

    .bg-success { background: linear-gradient(135deg, #059669, #10b981) !important; }
    .bg-info    { background: linear-gradient(135deg, #2563eb, #3b82f6) !important; }
    .bg-primary { background: linear-gradient(135deg, #3730a3, #4f6ef7) !important; }
    .bg-warning { background: linear-gradient(135deg, #d97706, #f59e0b) !important; }
    .bg-danger  { background: linear-gradient(135deg, #dc2626, #ef4444) !important; }
    .bg-teal    { background: linear-gradient(135deg, #0d9488, #14b8a6) !important; }
    .bg-secondary { background: linear-gradient(135deg, #475569, #64748b) !important; }

    /* ── Buttons ───────────────────────────────────────── */
    .btn {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: .82rem;
        border-radius: 8px;
        padding: 7px 16px;
        transition: all var(--transition);
        letter-spacing: .01em;
    }
    .btn-lg { font-size: .92rem !important; padding: 10px 24px !important; }
    .btn-sm { font-size: .77rem !important; padding: 5px 12px !important; border-radius: 6px; }
    .btn-xs { font-size: .72rem !important; padding: 3px 9px !important; border-radius: 5px; }

    .btn-primary {
        background: linear-gradient(135deg, #3730a3, var(--accent)) !important;
        border-color: var(--accent) !important;
        box-shadow: 0 2px 8px rgba(79,110,247,.35);
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #312e81, var(--accent-hover)) !important;
        box-shadow: 0 4px 16px rgba(79,110,247,.45);
        transform: translateY(-1px);
    }
    .btn-success {
        background: linear-gradient(135deg, #059669, #10b981) !important;
        border-color: #10b981 !important;
        box-shadow: 0 2px 8px rgba(16,185,129,.3);
    }
    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(16,185,129,.4);
    }
    .btn-danger {
        background: linear-gradient(135deg, #dc2626, #ef4444) !important;
        border-color: #ef4444 !important;
    }
    .btn-info {
        background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
        border-color: #3b82f6 !important;
        color: #fff !important;
    }
    .btn-warning {
        background: linear-gradient(135deg, #d97706, #f59e0b) !important;
        border-color: #f59e0b !important;
    }

    .btn-outline-primary {
        color: var(--accent) !important;
        border-color: var(--accent) !important;
    }
    .btn-outline-primary:hover {
        background: var(--accent) !important;
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(79,110,247,.3);
    }
    .btn-outline-secondary:hover { background: #e2e8f0 !important; }

    /* ── Forms ─────────────────────────────────────────── */
    .form-control {
        border-radius: 8px !important;
        border: 1.5px solid var(--border) !important;
        font-family: 'Inter', sans-serif;
        font-size: .85rem;
        color: var(--text);
        padding: 8px 14px;
        transition: border-color var(--transition), box-shadow var(--transition);
        background: #fff;
    }
    .form-control:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px rgba(79,110,247,.18) !important;
        outline: none;
    }
    .form-control.is-invalid { border-color: var(--danger) !important; }
    .form-control::placeholder { color: #aab4c8; }

    .input-group-text {
        border: 1.5px solid var(--border) !important;
        border-radius: 8px !important;
        background: #f8faff;
        color: var(--muted);
        font-size: .85rem;
    }
    .input-group > .form-control:not(:first-child),
    .input-group > .input-group-append > .input-group-text {
        border-left: none !important;
        border-radius: 0 8px 8px 0 !important;
    }
    .input-group > .input-group-prepend > .input-group-text {
        border-right: none !important;
        border-radius: 8px 0 0 8px !important;
    }
    .input-group > .form-control:not(:last-child) { border-radius: 8px 0 0 8px !important; }

    select.form-control { background-image: none; cursor: pointer; }
    label { font-weight: 600; font-size: .82rem; color: var(--text); margin-bottom: 5px; }

    .custom-file-label {
        border-radius: 8px !important;
        border: 1.5px solid var(--border) !important;
        font-size: .84rem;
    }
    .custom-file-input:focus ~ .custom-file-label {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px rgba(79,110,247,.18) !important;
    }

    /* ── Tables ────────────────────────────────────────── */
    .table { color: var(--text); font-size: .84rem; }
    .table th {
        font-weight: 700;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        border-top: none !important;
        padding: 12px 16px;
        white-space: nowrap;
    }
    .table td { padding: 12px 16px; vertical-align: middle !important; border-color: var(--border); }
    .table-hover tbody tr:hover { background: #f6f8ff !important; }
    .thead-dark th { background: var(--sidebar-bg) !important; color: #94a3b8 !important; }
    .thead-light th { background: #f8faff !important; color: var(--muted) !important; }

    /* ── Badges ────────────────────────────────────────── */
    .badge {
        font-weight: 600;
        font-size: .72rem;
        padding: 4px 9px;
        border-radius: 6px;
        letter-spacing: .02em;
    }
    .badge-success  { background: #d1fae5; color: #065f46; }
    .badge-info     { background: #dbeafe; color: #1e40af; }
    .badge-primary  { background: #e0e7ff; color: #3730a3; }
    .badge-danger   { background: #fee2e2; color: #991b1b; }
    .badge-warning  { background: #fef3c7; color: #92400e; }
    .badge-secondary{ background: #f1f5f9; color: #475569; }
    .badge-light    { background: #f8faff; color: var(--muted); border: 1px solid var(--border); }
    .badge-dark     { background: var(--sidebar-bg); color: #94a3b8; }

    /* ── Alerts ────────────────────────────────────────── */
    .alert {
        border-radius: 10px !important;
        border: none !important;
        font-size: .84rem;
        font-weight: 500;
        padding: 12px 18px;
    }
    .alert-success  { background: #d1fae5; color: #065f46; }
    .alert-danger   { background: #fee2e2; color: #991b1b; }
    .alert-warning  { background: #fef3c7; color: #92400e; }
    .alert-info     { background: #dbeafe; color: #1e40af; }

    /* ── Progress bar ──────────────────────────────────── */
    .progress { border-radius: 99px; background: #e8edf5; }
    .progress-bar { border-radius: 99px; }

    /* ── Info-box ──────────────────────────────────────── */
    .info-box {
        border-radius: 12px !important;
        box-shadow: var(--card-shadow) !important;
        background: #fff;
        border: 1px solid var(--border);
        min-height: 70px;
    }
    .info-box-icon {
        border-radius: 12px 0 0 12px !important;
        width: 60px;
        line-height: 60px;
        font-size: 1.2rem;
    }
    .info-box-text { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .info-box-number { font-size: 1.3rem; font-weight: 800; }

    /* ── Callout ───────────────────────────────────────── */
    .callout {
        border-radius: 10px !important;
        border-left-width: 4px !important;
        background: #f8faff;
    }
    .callout-info { border-left-color: var(--info) !important; }
    .callout-success { border-left-color: var(--success) !important; }
    .callout-warning { border-left-color: var(--warning) !important; }

    /* ── Modal ─────────────────────────────────────────── */
    .modal-content {
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 24px 64px rgba(0,0,0,.18);
    }
    .modal-header {
        border-radius: 16px 16px 0 0 !important;
        padding: 18px 24px !important;
        border-bottom: 1px solid var(--border) !important;
    }
    .modal-footer {
        border-radius: 0 0 16px 16px !important;
        padding: 14px 24px !important;
        border-top: 1px solid var(--border) !important;
        background: #fafbfd;
    }
    .modal-body { padding: 20px 24px; }

    /* ── Footer ────────────────────────────────────────── */
    .main-footer {
        background: #fff !important;
        border-top: 1px solid var(--border) !important;
        color: var(--muted) !important;
        font-size: .78rem;
        font-weight: 500;
    }

    /* ── Page transitions ──────────────────────────────── */
    .content-wrapper { animation: fadeIn .2s ease; }
    @keyframes fadeIn { from { opacity:.7; } to { opacity:1; } }

    /* ── Code ──────────────────────────────────────────── */
    code {
        background: #f1f5f9;
        color: #4f46e5;
        border-radius: 5px;
        padding: 2px 7px;
        font-size: .82rem;
        font-weight: 600;
    }

    /* ── Utility ───────────────────────────────────────── */
    .elevation-4 { box-shadow: none !important; }
    .text-muted { color: var(--muted) !important; }
    hr { border-color: var(--border); }
    </style>

    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    {{-- Top Navbar --}}
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        @php
            $navAvatarUrl = auth()->user()?->avatarUrl();
        @endphp
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#">
                    @if($navAvatarUrl)
                        <img src="{{ $navAvatarUrl }}" alt="{{ auth()->user()->name }}"
                             class="rounded-circle mr-2"
                             style="width:30px;height:30px;object-fit:cover;border:2px solid var(--accent);">
                    @else
                        <span class="d-flex align-items-center justify-content-center rounded-circle text-white mr-2"
                              style="width:30px;height:30px;font-size:.8rem;font-weight:700;
                                     background:linear-gradient(135deg,#3730a3,#4f6ef7);">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                    @endif
                    <span class="d-none d-md-inline">{{ auth()->user()?->name }}</span>
                    <i class="fas fa-chevron-down ml-2" style="font-size:.65rem;opacity:.6;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <div class="px-3 py-2 border-bottom" style="font-size:.78rem;color:var(--muted);">
                        {{ auth()->user()?->email }}
                    </div>
                    <a href="{{ route('profile') }}" class="dropdown-item mt-1">
                        <i class="fas fa-user-cog mr-2 text-primary"></i> Profile
                    </a>
                    <div class="dropdown-divider my-1"></div>
                    <button form="logout-form" type="submit" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                    </button>
                </div>
            </li>
        </ul>
    </nav>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>

    {{-- Sidebar --}}
    @php
        $logoExt = null;
        if (file_exists(public_path('images/logo.svg')))      $logoExt = 'svg';
        elseif (file_exists(public_path('images/logo.png')))  $logoExt = 'png';
        elseif (file_exists(public_path('images/logo.jpg')))  $logoExt = 'jpg';
        $sidebarAvatarUrl = auth()->user()?->avatarUrl();
    @endphp
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        @php
            $dash = match (true) {
                auth()->user()?->isSuperAdmin() => route('super-admin.dashboard'),
                auth()->user()?->isAdmin() => route('admin.dashboard'),
                default => route('agent.dashboard'),
            };
        @endphp
        <a href="{{ $dash }}"
           class="brand-link px-3 d-flex align-items-center">
            @if($logoExt)
                <img src="{{ asset('images/logo.'.$logoExt) }}"
                     alt="{{ config('app.name') }}"
                     style="max-height:32px;max-width:130px;object-fit:contain;margin-right:8px;">
            @else
                <i class="fas fa-ticket-alt brand-image mr-2" style="opacity:1;color:var(--accent);"></i>
                <span class="brand-text">{{ config('app.name', 'Liba Events') }}</span>
            @endif
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    @if($sidebarAvatarUrl)
                        <img src="{{ $sidebarAvatarUrl }}" alt="{{ auth()->user()->name }}"
                             class="rounded-circle"
                             style="width:36px;height:36px;object-fit:cover;border:2px solid var(--accent);">
                    @else
                        <span class="d-flex align-items-center justify-content-center rounded-circle text-white"
                              style="width:36px;height:36px;font-size:.9rem;font-weight:700;
                                     background:linear-gradient(135deg,var(--accent),#7c5cbf);">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div class="info ml-2">
                    <a href="{{ route('profile') }}" class="d-block font-weight-bold"
                       style="color:#e2e8f0;font-size:.84rem;">
                        {{ auth()->user()?->name }}
                    </a>
                    <small style="color:#64748b;font-size:.7rem;">
                        @php($r = auth()->user()?->role)
                        {{ $r === 'super_admin' ? 'Super Admin' : ucfirst((string) $r) }}
                    </small>
                </div>
            </div>

            <nav class="mt-1">
                <ul class="nav nav-pills nav-sidebar flex-column"
                    data-widget="treeview" role="menu" data-accordion="false">
                    @php($user = auth()->user())

                    @if($user && $user->isSuperAdmin())
                        <li class="nav-header">SUPER ADMIN</li>

                        @foreach ([
                            ['super-admin.dashboard',        'fa-tachometer-alt', 'Dashboard'],
                            ['super-admin.companies.index',  'fa-building',       'Companies'],
                            ['super-admin.companies.create', 'fa-plus-circle',    'New company'],
                            ['super-admin.company-admins.index', 'fa-users-cog', 'Company admins'],
                            ['super-admin.company-admins.create', 'fa-user-shield', 'Add company admin'],
                            ['super-admin.crm.index',        'fa-address-book',   'CRM'],
                        ] as [$route, $icon, $label])
                            <li class="nav-item">
                                <a href="{{ route($route) }}"
                                   class="nav-link {{ request()->routeIs($route) || ($route === 'super-admin.companies.index' && request()->routeIs('super-admin.companies.*')) || ($route === 'super-admin.crm.index' && request()->routeIs('super-admin.crm.*')) ? 'active' : '' }}">
                                    <i class="nav-icon fas {{ $icon }}"></i>
                                    <p>{{ $label }}</p>
                                </a>
                            </li>
                        @endforeach

                    @elseif($user && $user->isAdmin())
                        <li class="nav-header">ADMIN</li>

                        @foreach ([
                            ['admin.dashboard',         'fa-tachometer-alt', 'Dashboard'],
                            ['admin.events.index',      'fa-calendar-alt',   'Events'],
                            ['admin.agents.index',      'fa-users',          'Agents'],
                            ['admin.ticket-sales.index','fa-chart-bar',      'Ticket Sales'],
                            ['admin.customers.index',   'fa-address-book',   'Customers'],
                            ['admin.checkin.index',     'fa-qrcode',         'Check-In'],
                        ] as [$route, $icon, $label])
                            <li class="nav-item">
                                <a href="{{ route($route) }}"
                                   class="nav-link {{ request()->routeIs(explode('.', $route)[0].'.'.(explode('.', $route)[1] ?? '').'.*') || request()->routeIs($route) ? 'active' : '' }}">
                                    <i class="nav-icon fas {{ $icon }}"></i>
                                    <p>{{ $label }}</p>
                                </a>
                            </li>
                        @endforeach

                    @else
                        <li class="nav-header">MENU</li>

                        @foreach ([
                            ['agent.dashboard',       'fa-tachometer-alt', 'Dashboard'],
                            ['agent.tickets.create',  'fa-plus-circle',    'Sell Ticket'],
                            ['agent.tickets.index',   'fa-ticket-alt',     'My Tickets'],
                            ['agent.checkin.index',   'fa-qrcode',         'Check-In'],
                        ] as [$route, $icon, $label])
                            <li class="nav-item">
                                <a href="{{ route($route) }}"
                                   class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}">
                                    <i class="nav-icon fas {{ $icon }}"></i>
                                    <p>{{ $label }}</p>
                                </a>
                            </li>
                        @endforeach
                    @endif

                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item">
                        <a href="{{ route('profile') }}"
                           class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>Profile</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <button form="logout-form" type="submit"
                                class="nav-link text-left w-100"
                                style="background:none;border:none;cursor:pointer;">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Sign Out</p>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    {{-- Content Wrapper --}}
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                @if (isset($header))
                    {{ $header }}
                @endif
            </div>
        </div>
        <section class="content">
            <div class="container-fluid pb-4">
                {{ $slot }}
            </div>
        </section>
    </div>

    <footer class="main-footer text-sm">
        <strong>&copy; {{ date('Y') }} {{ config('app.name', 'Liba Events') }}</strong>
        <span class="float-right d-none d-sm-inline text-muted">Ticket Sales Management</span>
    </footer>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

@stack('scripts')
</body>
</html>
