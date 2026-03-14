<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') — BookStackIn</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <style>
        :root {
            --ink:          #0d0d14;
            --muted:        #64748b;
            --soft:         #f1f5f9;
            --border:       #e2e8f0;
            --accent:       #4f46e5;
            --accent-light: #eef2ff;
            --green:        #10b981;
            --red:          #ef4444;
            --sidebar-w:    220px;
            --topbar-h:     60px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f8fafc;
            color: var(--ink);
        }

        code, .mono {
            font-family: 'DM Mono', monospace;
            font-size: 13px;
        }

        /* ────────────────────────────────────────────────────
           OVERLAY (mobile only)
        ──────────────────────────────────────────────────── */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 49;
            backdrop-filter: blur(2px);
        }

        #sidebar-overlay.open { display: block; }

        /* ────────────────────────────────────────────────────
           SIDEBAR
        ──────────────────────────────────────────────────── */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--ink);
            display: flex;
            flex-direction: column;
            z-index: 50;
            transition: transform .25s cubic-bezier(.4,0,.2,1);
        }

        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-brand .logo {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
            line-height: 1;
        }

        .sidebar-brand .logo span { color: #818cf8; }

        .sidebar-brand .logo-sub {
            font-size: 10px;
            color: #475569;
            margin-top: 3px;
            font-weight: 400;
        }

        /* Close button — mobile only */
        .sidebar-close {
            display: none;
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            line-height: 0;
        }

        .sidebar-close:hover { color: #fff; background: rgba(255,255,255,.08); }

        .sidebar-nav {
            flex: 1;
            padding: 12px 10px;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #334155;
            padding: 12px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 2px;
            width: 100%;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            border: none;
            background: none;
        }

        .nav-item:hover  { background: rgba(255,255,255,.06); color: #fff; }
        .nav-item.active { background: var(--accent); color: #fff; }
        .nav-item.active svg { opacity: 1; }
        .nav-item svg    { width: 17px; height: 17px; opacity: .6; flex-shrink: 0; }

        .sidebar-footer {
            padding: 12px 10px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .nav-item.logout { color: #f87171; }
        .nav-item.logout:hover { background: rgba(239,68,68,.1); color: #fca5a5; }

        /* ────────────────────────────────────────────────────
           TOPBAR
        ──────────────────────────────────────────────────── */
        #main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            transition: margin-left .25s cubic-bezier(.4,0,.2,1);
        }

        .topbar {
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 40;
            gap: 12px;
        }

        /* Hamburger — mobile only */
        .topbar-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ink);
            padding: 6px;
            border-radius: 8px;
            line-height: 0;
            flex-shrink: 0;
        }

        .topbar-menu-btn:hover { background: var(--soft); }

        .topbar-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--ink);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .topbar-email {
            font-size: 13px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 13px;
            flex-shrink: 0;
        }

        .role-badge {
            font-size: 11px;
            background: #fef9c3;
            color: #92400e;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 500;
            white-space: nowrap;
        }

        /* ────────────────────────────────────────────────────
           CONTENT
        ──────────────────────────────────────────────────── */
        .content {
            padding: 24px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-up { animation: fadeUp .22s ease both; }

        /* ────────────────────────────────────────────────────
           CARDS
        ──────────────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
        }

        /* ────────────────────────────────────────────────────
           STAT GRID
        ──────────────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.5px;
            line-height: 1.1;
        }

        .stat-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* ────────────────────────────────────────────────────
           TABLE
        ──────────────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 480px; }

        th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            padding: 11px 16px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--ink);
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--soft); }

        /* ────────────────────────────────────────────────────
           BADGES
        ──────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-yellow { background: #fef9c3; color: #92400e; }
        .badge-red    { background: #fee2e2; color: #dc2626; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* ────────────────────────────────────────────────────
           BUTTONS
        ──────────────────────────────────────────────────── */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-outline { background: #fff; color: var(--ink); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--soft); }
        .btn-danger  { background: #fee2e2; color: var(--red); }
        .btn-danger:hover { background: #fecaca; }
        .btn-white   { background: #fff; color: var(--accent); font-weight: 600; }
        .btn-white:hover { background: #eef2ff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        /* ────────────────────────────────────────────────────
           FORMS
        ──────────────────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            outline: none;
            transition: border-color .15s;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(79,70,229,.1);
        }

        .field-error { color: var(--red); font-size: 12px; margin-top: 4px; }

        /* ────────────────────────────────────────────────────
           ALERTS
        ──────────────────────────────────────────────────── */
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error   { background: #fee2e2; color: #dc2626; }
        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-info    { background: var(--accent-light); color: var(--accent); }

        /* ────────────────────────────────────────────────────
           BVN / VERIFY BANNER
        ──────────────────────────────────────────────────── */
        .verify-banner {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .verify-banner h4 { margin: 0 0 3px; font-size: 15px; }
        .verify-banner p  { margin: 0; opacity: .8; font-size: 13px; }

        /* ────────────────────────────────────────────────────
           KEY BOX
        ──────────────────────────────────────────────────── */
        .key-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .key-value {
            flex: 1;
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--ink);
            word-break: break-all;
            min-width: 0;
        }

        /* ────────────────────────────────────────────────────
           CODE BLOCKS
        ──────────────────────────────────────────────────── */
        .code-block {
            background: #1e1e2e;
            border-radius: 10px;
            padding: 16px 18px;
            margin-top: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .code-block pre {
            margin: 0;
            color: #cdd6f4;
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            white-space: pre;
        }

        .code-block .kw  { color: #cba6f7; }
        .code-block .str { color: #a6e3a1; }
        .code-block .fn  { color: #89dceb; }
        .code-block .cm  { color: #585b70; }

        /* ────────────────────────────────────────────────────
           MOBILE — breakpoint 768px
        ──────────────────────────────────────────────────── */
        @media (max-width: 768px) {

            /* Sidebar slides off left by default */
            #sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            /* When open class added by JS */
            #sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 32px rgba(0,0,0,.25);
            }

            .sidebar-close { display: flex; }

            /* Main takes full width */
            #main { margin-left: 0; }

            /* Show hamburger */
            .topbar-menu-btn { display: flex; }

            /* Tighten topbar on small screens */
            .topbar { padding: 0 16px; }
            .topbar-email { display: none; }

            /* Content padding reduced */
            .content { padding: 16px; }

            /* Stat grid — 2 columns on mobile */
            .stat-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-value { font-size: 22px; }

            /* Cards */
            .card { padding: 16px; }

            /* Banner stacks vertically */
            .verify-banner { flex-direction: column; align-items: flex-start; }

            /* Tables — hide less important columns via utility class */
            .hide-mobile { display: none !important; }
        }

        /* ────────────────────────────────────────────────────
           VERY SMALL — 480px
        ──────────────────────────────────────────────────── */
        @media (max-width: 480px) {
            .stat-grid { grid-template-columns: 1fr; }
            .btn-sm { padding: 5px 10px; font-size: 12px; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ── Overlay (mobile) ─────────────────────────────────────────────────────── --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

{{-- ── Sidebar ─────────────────────────────────────────────────────────────── --}}
<div id="sidebar">

    <div class="sidebar-brand">
        <div>
            <div class="logo">Book<span>Stack</span></div>
            <div class="logo-sub">Developer Console</div>
        </div>
        {{-- Close button shown only on mobile --}}
        <button class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section-label">Main</div>

        @include('components.nav-item', ['route' => 'dashboard',          'label' => 'Overview',  'icon' => 'grid'])
        @include('components.nav-item', ['route' => 'dashboard.bookings', 'label' => 'Bookings',  'icon' => 'list'])
        @include('components.nav-item', ['route' => 'dashboard.payments', 'label' => 'Payments',  'icon' => 'credit-card'])

        @if(auth()->user()->isAdmin())
            <div class="nav-section-label" style="margin-top:8px;">Admin</div>
            @include('components.nav-item', ['route' => 'dashboard.api-keys',        'label' => 'API Keys',    'icon' => 'key'])
            @include('components.nav-item', ['route' => 'dashboard.integration',      'label' => 'Integration', 'icon' => 'code'])
            @include('components.nav-item', ['route' => 'dashboard.booking-settings', 'label' => 'Settings',    'icon' => 'settings'])
            @include('components.nav-item', ['route' => 'staff.index',                'label' => 'Staff',       'icon' => 'users'])
        @endif

    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="nav-item logout">
                @include('components.icon', ['name' => 'logout'])
                Sign Out
            </button>
        </form>
    </div>

</div>

{{-- ── Main ─────────────────────────────────────────────────────────────────── --}}
<div id="main">

    <div class="topbar">

        {{-- Hamburger — mobile only --}}
        <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Open menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6"  x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>

        <div class="topbar-right">
            @if(auth()->user()->isStaff())
                <span class="role-badge">Staff</span>
            @endif
            <div class="topbar-email">{{ auth()->user()->email }}</div>
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        </div>

    </div>

    <div class="content fade-up">

        {{-- BVN verification banner --}}
        @if(! auth()->user()->bvn_verified)
            <div class="verify-banner">
                <div>
                    <h4>⚠️ Verify your identity to activate your account</h4>
                    <p>Submit your BVN and bank details to receive your API key and start accepting payments.</p>
                </div>
                <a href="{{ route('dashboard.api-keys') }}" class="btn btn-white btn-sm">Verify Now →</a>
            </div>
        @endif

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')

    </div>

</div>

{{-- ── Mobile sidebar JS ───────────────────────────────────────────────────── --}}


<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebar-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebar-overlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    // ── Always start closed on page load ──────────────────
    document.addEventListener('DOMContentLoaded', () => {
        closeSidebar();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });
</script>

@stack('scripts')
</body>
</html>