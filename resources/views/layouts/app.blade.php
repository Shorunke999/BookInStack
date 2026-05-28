<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') — BookInStack</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAzMiAzMic+PHJlY3Qgd2lkdGg9JzMyJyBoZWlnaHQ9JzMyJyByeD0nNicgZmlsbD0nIzI1NjNlYicvPjx0ZXh0IHg9JzUwJScgeT0nNTQlJyBkb21pbmFudC1iYXNlbGluZT0nbWlkZGxlJyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmb250LWZhbWlseT0nc3lzdGVtLXVpJyBmb250LXdlaWdodD0nNzAwJyBmb250LXNpemU9JzE0JyBmaWxsPSd3aGl0ZSc+QjwvdGV4dD48L3N2Zz4=" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <style>
        :root {
            --ink:          #0f1117;
            --ink-mid:      #3a3f52;
            --muted:        #7e8599;
            --border:       #e8eaef;
            --surface:      #f5f6f8;
            --accent:       #2563eb;
            --accent-h:     #1d4ed8;
            --accent-light: #eff6ff;
            --green:        #059669;
            --red:          #dc2626;
            --white:        #ffffff;
            --sidebar-w:    216px;
            --topbar-h:     58px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--surface);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        code, .mono {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12.5px;
        }

        /* ────────────────────────────────────────────────────
           OVERLAY (mobile)
        ──────────────────────────────────────────────────── */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
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

        /* Subtle grid texture on sidebar */
        #sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .sidebar-brand {
            padding: 20px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .sidebar-brand .logo {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
            line-height: 1;
        }

        .sidebar-brand .logo span { color: var(--accent); }

        .sidebar-brand .logo-sub {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9.5px;
            color: rgba(255,255,255,.2);
            margin-top: 4px;
            font-weight: 400;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        /* Close button — mobile only */
        .sidebar-close {
            display: none;
            background: none;
            border: none;
            color: rgba(255,255,255,.3);
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            line-height: 0;
            transition: color .15s, background .15s;
        }

        .sidebar-close:hover { color: #fff; background: rgba(255,255,255,.08); }

        .sidebar-nav {
            flex: 1;
            padding: 10px 10px;
            overflow-y: auto;
            padding-bottom: 80px;
            position: relative;
            z-index: 1;
        }

        .nav-section-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: rgba(255,255,255,.18);
            padding: 14px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 7px;
            color: rgba(255,255,255,.45);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 1px;
            width: 100%;
            cursor: pointer;
            font-family: 'Sora', sans-serif;
            border: none;
            background: none;
            letter-spacing: -.01em;
        }

        .nav-item:hover  { background: rgba(255,255,255,.06); color: #fff; }
        .nav-item.active { background: var(--accent); color: #fff; }
        .nav-item.active svg { opacity: 1; }
        .nav-item svg    { width: 16px; height: 16px; opacity: .5; flex-shrink: 0; }
        .nav-item.active svg { opacity: 1; }

        .sidebar-footer {
            padding: 10px 10px;
            border-top: 1px solid rgba(255,255,255,.07);
            position: relative;
            z-index: 1;
        }

        .nav-item.logout { color: rgba(248,113,113,.6); }
        .nav-item.logout:hover { background: rgba(220,38,38,.1); color: #fca5a5; }

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
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 40;
            gap: 12px;
        }

        .topbar-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ink);
            padding: 6px;
            border-radius: 7px;
            line-height: 0;
            flex-shrink: 0;
            transition: background .15s;
        }

        .topbar-menu-btn:hover { background: var(--surface); }

        .topbar-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -.02em;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .topbar-email {
            font-size: 12.5px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: var(--ink);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 12px;
            flex-shrink: 0;
            letter-spacing: 0;
        }

        .role-badge {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            background: #fef9c3;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        /* ────────────────────────────────────────────────────
           CONTENT
        ──────────────────────────────────────────────────── */
        .content {
            padding: 24px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-up { animation: fadeUp .2s ease both; }

        /* ────────────────────────────────────────────────────
           CARDS
        ──────────────────────────────────────────────────── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px 24px;
        }

        /* ────────────────────────────────────────────────────
           STAT GRID
        ──────────────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(176px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px 22px;
            transition: background .15s;
        }

        .stat-card:hover { background: #fafbfd; }

        .stat-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.05em;
            line-height: 1;
        }

        .stat-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 5px;
        }

        /* ────────────────────────────────────────────────────
           TABLE
        ──────────────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 480px; }

        th {
            text-align: left;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .08em;
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
        tr:hover td { background: var(--surface); }

        /* ────────────────────────────────────────────────────
           BADGES
        ──────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 4px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10.5px;
            font-weight: 500;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .badge-green  { background: #ecfdf5; color: #065f46; }
        .badge-yellow { background: #fefce8; color: #854d0e; }
        .badge-red    { background: #fef2f2; color: #991b1b; }
        .badge-gray   { background: var(--surface); color: var(--muted); }

        /* ────────────────────────────────────────────────────
           BUTTONS
        ──────────────────────────────────────────────────── */
        .btn {
            padding: 8px 16px;
            border-radius: 7px;
            border: none;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            font-family: 'Sora', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            letter-spacing: -.01em;
        }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-h); }

        .btn-dark { background: var(--ink); color: #fff; }
        .btn-dark:hover { background: #1a1f2e; }

        .btn-outline { background: var(--white); color: var(--ink); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--surface); }

        .btn-danger  { background: #fef2f2; color: var(--red); }
        .btn-danger:hover { background: #fee2e2; }

        .btn-white   { background: var(--white); color: var(--accent); font-weight: 600; }
        .btn-white:hover { background: var(--accent-light); }

        .btn-sm { padding: 6px 12px; font-size: 12.5px; }

        /* ────────────────────────────────────────────────────
           FORMS
        ──────────────────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 7px;
            letter-spacing: -.01em;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 13.5px;
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: var(--white);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .field-error { color: var(--red); font-size: 11.5px; margin-top: 4px; }

        /* ────────────────────────────────────────────────────
           ALERTS
        ──────────────────────────────────────────────────── */
        .alert {
            padding: 11px 14px;
            border-radius: 7px;
            font-size: 13.5px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-info    { background: var(--accent-light); color: var(--accent); border: 1px solid #bfdbfe; }

        /* ────────────────────────────────────────────────────
           BVN / VERIFY BANNER
        ──────────────────────────────────────────────────── */
        .verify-banner {
            background: var(--ink);
            color: #fff;
            border-radius: 10px;
            padding: 18px 22px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .verify-banner::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(37,99,235,.2) 0%, transparent 70%);
            top: -80px; right: -60px;
            pointer-events: none;
        }

        .verify-banner h4 { margin: 0 0 3px; font-size: 14px; font-weight: 600; letter-spacing: -.02em; }
        .verify-banner p  { margin: 0; opacity: .5; font-size: 13px; }

        /* ────────────────────────────────────────────────────
           KEY BOX
        ──────────────────────────────────────────────────── */
        .key-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 11px 15px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .key-value {
            flex: 1;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            color: var(--ink);
            word-break: break-all;
            min-width: 0;
        }

        /* ────────────────────────────────────────────────────
           CODE BLOCKS
        ──────────────────────────────────────────────────── */
        .code-block {
            background: var(--ink);
            border-radius: 10px;
            padding: 16px 18px;
            margin-top: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(255,255,255,.06);
        }

        .code-block pre {
            margin: 0;
            color: #cdd6f4;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12.5px;
            line-height: 1.7;
            white-space: pre;
        }

        .code-block .kw  { color: #cba6f7; }
        .code-block .str { color: #a6e3a1; }
        .code-block .fn  { color: #89dceb; }
        .code-block .cm  { color: rgba(255,255,255,.2); }

        /* ────────────────────────────────────────────────────
           MOBILE — 768px
        ──────────────────────────────────────────────────── */
        @media (max-width: 768px) {

            .sidebar-footer {
                position: sticky;
                bottom: 0;
                background: var(--ink);
                padding: 10px 10px;
                border-top: 1px solid rgba(255,255,255,.07);
            }

            #sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            #sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 32px rgba(0,0,0,.3);
            }

            .sidebar-close { display: flex; }

            #main { margin-left: 0; }

            .topbar-menu-btn { display: flex; }

            .topbar { padding: 0 16px; }
            .topbar-email { display: none; }

            .content { padding: 16px; }

            .stat-grid { grid-template-columns: 1fr 1fr; gap: 10px; background: transparent; border: none; border-radius: 0; }
            .stat-card { border: 1px solid var(--border); border-radius: 10px; }
            .stat-value { font-size: 22px; }

            .card { padding: 16px; }

            .verify-banner { flex-direction: column; align-items: flex-start; }

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

{{-- Overlay (mobile) --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<div id="sidebar">

    <div class="sidebar-brand">
        <div>
            <div class="logo">BookIn<span>Stack</span></div>
            <div class="logo-sub">Business Console</div>
        </div>
        <button class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        @if(auth()->user()->isSuperAdmin())
            <div class="nav-section-label" style="color:var(--accent);">Superadmin</div>
            @include('components.nav-item', ['route'=>'superadmin.dashboard',  'label'=>'SA Dashboard', 'icon'=>'grid'])
            @include('components.nav-item', ['route'=>'superadmin.developers', 'label'=>'Developers',   'icon'=>'users'])
            @include('components.nav-item', ['route'=>'superadmin.revenue',    'label'=>'Revenue',      'icon'=>'credit-card'])
        @else
            <div class="nav-section-label">Main</div>

            @if(auth()->user()->isAdmin())
                @include('components.nav-item', ['route' => 'dashboard',          'label' => 'Overview',  'icon' => 'grid'])
            @endif
            @include('components.nav-item', ['route' => 'dashboard.bookings', 'label' => 'Bookings',  'icon' => 'list'])
            @if(preg_match('/Android|iPhone|iPad|iPod|Mobile/i', request()->header('User-Agent', '')))
                @include('components.nav-item', ['route' => 'scan', 'label' => 'Scan QR', 'icon' => 'qr'])
            @endif

            @if(auth()->user()->isAdmin())
                <div class="nav-section-label" style="margin-top:8px;">Admin</div>
                @include('components.nav-item', ['route' => 'services.index',          'label' => 'Services',    'icon' => 'grid'])
                @include('components.nav-item', ['route' => 'api-keys',               'label' => 'API Keys',    'icon' => 'key'])
                @include('components.nav-item', ['route' => 'dashboard.integration',  'label' => 'Integration', 'icon' => 'code'])
                @include('components.nav-item', ['route' => 'staff.index',            'label' => 'Staff',       'icon' => 'users'])
            @endif
        @endif
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" style="margin:0; padding-bottom: env(safe-area-inset-bottom, 12px);">
            @csrf
            <button type="submit" class="nav-item logout">
                @include('components.icon', ['name' => 'logout'])
                Sign Out
            </button>
        </form>
    </div>

</div>

{{-- Main --}}
<div id="main">

    <div class="topbar">

        <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Open menu">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            @if(!auth()->user()->isSuperAdmin())
                @include('components.service-switcher')
            @endif
            <div class="topbar-email">{{ auth()->user()->email }}</div>
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        </div>

    </div>

    <div class="content fade-up">

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

    document.addEventListener('DOMContentLoaded', () => { closeSidebar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>

@stack('scripts')
</body>
</html>
