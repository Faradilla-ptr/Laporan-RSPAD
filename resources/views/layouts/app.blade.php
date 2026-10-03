<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Rekapitulasi Pelaporan') | RSPAD Gatot Soebroto</title>
    <!-- Favicon / Tab Logo RSPAD -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-rspad.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-rspad.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-rspad.png') }}">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Local Asset Primary + Multi-CDN Fallbacks) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08cmemwbmxsnUILbBHUvrxD621rYDQcdL7sGB82Nso09Fpu367VvhgceeqT9vUXTHleGJVy6g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @guest
    <style>
        .app-main { margin-left: 0 !important; }
        .app-main > main { padding: 0 !important; }
    </style>
    @endguest

    <style>
        :root {
            /* Option 1: RSPAD Classic Forest Palette */
            --green-primary: #3B6E4A;
            --green-darker: #2E5A3C;
            --green-darkest: #23422E;
            --green-lighter: #4A855B;
            --green-lightest: #E8F5E9;

            /* Neutral Slate & Text Palette */
            --gray-main: #4a5568;
            --gray-medium: #cbd5e1;
            --gray-dark: #1e293b;

            /* Danger Red Palette */
            --red-main: #c84c3b;
            --red-dark: #a03d2f;

            /* Mapped Variables for App Uniformity */
            --palette-1: #E8F5E9;
            --palette-2: #81B29A;
            --palette-3: #4A855B;
            --palette-4: #3B6E4A;
            --palette-5: #2E5A3C;

            --sidebar-width: 260px;
            --sidebar-collapsed-width: 72px;
            --bg-neutral: #f8faf9;
            --border-color: #cbd5e1;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-neutral);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-main);
            font-size: 0.9rem;
            letter-spacing: -0.01em;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* Enforce Bootstrap Icon font-family rendering */
        i[class*=" bi-"], i[class^="bi-"], .bi {
            font-family: 'bootstrap-icons' !important;
            font-style: normal;
            font-weight: normal !important;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            vertical-align: -.125em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Instant Centered Pop-up Modals (No Top-Slide Animation) */
        .modal.fade .modal-dialog {
            transition: opacity 0.15s ease-in-out !important;
            transform: none !important;
        }
        .modal-dialog {
            margin-top: auto !important;
            margin-bottom: auto !important;
        }
        .modal-body {
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }
        .modal-content {
            max-width: 100% !important;
            overflow: hidden;
        }

        /* App Layout Wrapper */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left Sidebar Styling */
        .app-sidebar {
            width: var(--sidebar-width);
            background-color: #23422E;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.25);
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar Brand Header */
        .sidebar-brand {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 68px;
        }

        .brand-logo-img {
            height: 42px;
            width: auto;
            object-fit: contain;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.25));
            flex-shrink: 0;
        }

        .brand-text-container {
            flex-grow: 1;
            overflow: hidden;
            white-space: nowrap;
        }

        .brand-text-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: 0.04em;
            margin: 0;
            text-transform: uppercase;
        }

        .brand-text-sub {
            font-size: 0.85rem;
            color: var(--palette-1);
            margin: 0;
            font-weight: 600;
            line-height: 1.1;
        }

        .sidebar-toggle-btn {
            background: transparent;
            border: none;
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            padding: 0;
        }

        .sidebar-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--palette-1);
        }

        .sidebar-toggle-btn svg {
            transition: transform 0.25s ease;
        }

        /* Sidebar Nav List */
        .sidebar-nav {
            padding: 16px 10px;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-category {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #81B29A;
            padding: 12px 12px 6px 12px;
            white-space: nowrap;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #e2e8f0;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            margin-bottom: 4px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .sidebar-link svg {
            color: #81B29A;
            transition: color 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .sidebar-link:hover svg {
            color: #ffffff;
        }

        .sidebar-link.active {
            color: #ffffff;
            background-color: #3B6E4A;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            border-left: 4px solid #81B29A;
        }

        .sidebar-link.active svg {
            color: #ffffff;
        }

        /* Sidebar Footer (User Info) */
        .sidebar-user-panel {
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-info-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .user-avatar-circle {
            width: 36px;
            height: 36px;
            background-color: var(--palette-3);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            border: 1px solid var(--palette-1);
            flex-shrink: 0;
        }

        .user-details {
            overflow: hidden;
            flex-grow: 1;
            white-space: nowrap;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .user-role-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--palette-5);
            background-color: var(--palette-1);
            padding: 1px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .btn-sidebar-logout {
            background: transparent;
            border: none;
            color: var(--palette-2);
            padding: 6px;
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-sidebar-logout:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.15);
        }

        /* Collapsed Sidebar State (Buka-Tutup) */
        body.sidebar-collapsed .app-sidebar {
            width: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed .app-main {
            margin-left: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed .brand-text-container,
        body.sidebar-collapsed .nav-category,
        body.sidebar-collapsed .sidebar-link span,
        body.sidebar-collapsed .user-details {
            display: none !important;
        }

        body.sidebar-collapsed .sidebar-brand {
            padding: 16px 12px;
            justify-content: center;
        }

        body.sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding: 12px;
        }

        body.sidebar-collapsed .sidebar-user-panel {
            padding: 12px 8px;
        }

        body.sidebar-collapsed .user-info-box {
            justify-content: center;
        }

        body.sidebar-collapsed .sidebar-toggle-btn svg {
            transform: rotate(180deg);
        }

        /* Main Workspace */
        .app-main {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mobile Mobile Toggle Floating Button */
        .mobile-toggle-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            background-color: var(--palette-5);
            color: #ffffff;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            justify-content: center;
        }

        /* Cards & Panels */
        .card-panel {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        /* Metric Cards - UNIFORM 4-CARD STYLING */
        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--palette-5) !important;
            border-radius: 8px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            height: 100%;
        }

        .metric-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--palette-5) !important;
            line-height: 1;
            margin-bottom: 4px;
        }

        .metric-desc {
            font-size: 0.775rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Tables */
        .table-clean {
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .table-clean th {
            background-color: #2E5A3C !important;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 2px solid #23422E;
            padding: 11px 14px;
            vertical-align: middle;
        }

        .table-clean td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table-clean tfoot {
            background-color: #E8F5E9;
            font-weight: 700;
            color: #2E5A3C;
        }

        /* Buttons Custom Overrides */
        .btn-primary, .btn-rspad-primary {
            background-color: var(--green-darker) !important;
            border-color: var(--green-darker) !important;
            color: #ffffff !important;
            font-weight: 500;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-rspad-primary:hover {
            background-color: var(--green-primary) !important;
            border-color: var(--green-primary) !important;
            color: #ffffff !important;
        }

        .btn-danger {
            background-color: var(--red-main) !important;
            border-color: var(--red-main) !important;
            color: #ffffff !important;
            font-weight: 500;
        }
        .btn-danger:hover, .btn-danger:focus {
            background-color: var(--red-dark) !important;
            border-color: var(--red-dark) !important;
            color: #ffffff !important;
        }

        .btn-outline-rspad, .btn-outline-primary {
            color: var(--green-darker) !important;
            border-color: var(--green-darker) !important;
            background-color: transparent !important;
        }
        .btn-outline-rspad:hover, .btn-outline-primary:hover {
            background-color: var(--green-darker) !important;
            color: #ffffff !important;
        }

        .btn-outline-danger {
            color: var(--red-main) !important;
            border-color: var(--red-main) !important;
            background-color: transparent !important;
        }
        .btn-outline-danger:hover {
            background-color: var(--red-main) !important;
            color: #ffffff !important;
        }

        /* Footer */
        .app-footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 16px 28px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Responsive Sidebar for Mobile */
        @media (max-width: 991.98px) {
            .app-sidebar {
                transform: translateX(-100%);
            }
            body.mobile-sidebar-open .app-sidebar {
                transform: translateX(0);
                width: var(--sidebar-width) !important;
            }
            body.mobile-sidebar-open .brand-text-container,
            body.mobile-sidebar-open .nav-category,
            body.mobile-sidebar-open .sidebar-link span,
            body.mobile-sidebar-open .user-details {
                display: block !important;
            }
            .app-main {
                margin-left: 0 !important;
            }
            .mobile-toggle-btn {
                display: flex;
            }
        }
    </style>
</head>
<body>

    <div class="app-wrapper">
        @auth
        <!-- Left Sidebar Navigation -->
        <aside class="app-sidebar" id="appSidebar">
            <!-- Sidebar Brand Header with RSPAD HD Logo & Toggle -->
            <div class="sidebar-brand">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" class="brand-logo-img">
                    <div class="brand-text-container">
                        <h1 class="brand-text-title">RSPAD</h1>
                        <p class="brand-text-sub">Gatot Soebroto</p>
                    </div>
                </div>
                <button type="button" class="sidebar-toggle-btn d-none d-lg-flex" id="sidebarToggle" title="Buka / Tutup Sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="sidebar-nav">
                @php
                    $rolePrefix = (Auth::check() && Auth::user()->role === 'admin') ? 'admin' : 'petugas';
                @endphp

                <div class="nav-category">Navigasi Utama</div>
                
                <a href="{{ route($rolePrefix . '.dashboard') }}" class="sidebar-link {{ request()->routeIs('*.dashboard') || request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route($rolePrefix . '.imports.index') }}" class="sidebar-link {{ request()->routeIs('*.imports.*') || request()->routeIs('imports.*') ? 'active' : '' }}" title="Import SIMRS">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span>Import SIMRS</span>
                </a>

                @if(Auth::check() && Auth::user()->role === 'admin')
                @php
                    $pendingCount = \App\Models\User::where('is_approved', false)->count();
                @endphp
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="Validasi Akun Petugas">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                    <span>Validasi Akun Petugas</span>
                    @if($pendingCount > 0)
                        <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.7rem;">{{ $pendingCount }}</span>
                    @endif
                </a>
                @endif

                <div class="nav-category mt-3">Laporan Rekapitulasi</div>

                <a href="{{ route($rolePrefix . '.reports.rl34') }}" class="sidebar-link {{ request()->routeIs('*.reports.rl34') || request()->routeIs('reports.rl34') ? 'active' : '' }}" title="RL 3.4 (Pengunjung)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>RL 3.4 (Pengunjung)</span>
                </a>

                <a href="{{ route($rolePrefix . '.reports.rl35') }}" class="sidebar-link {{ request()->routeIs('*.reports.rl35') || request()->routeIs('reports.rl35') ? 'active' : '' }}" title="RL 3.5 (Kunjungan Poli)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>RL 3.5 (Kunjungan Poli)</span>
                </a>

                <a href="{{ route($rolePrefix . '.reports.puskesad') }}" class="sidebar-link {{ request()->routeIs('*.reports.puskesad') || request()->routeIs('reports.puskesad') ? 'active' : '' }}" title="Laporan Puskesad">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Laporan Puskesad</span>
                </a>

                <div class="nav-category mt-3">Sistem & Akun</div>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('admin.activity_logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.activity_logs.*') ? 'active' : '' }}" title="Log Aktivitas (Trail Log)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <circle cx="12" cy="14" r="3"></circle>
                    </svg>
                    <span>Trail Log Aktivitas</span>
                </a>
                @endif

                <a href="{{ route('profile.index') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" title="Profil Saya">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Pengaturan Profil</span>
                </a>
            </nav>

            <!-- Sidebar User Profile Footer -->
            <div class="sidebar-user-panel">
                <div class="user-info-box">
                    <div class="user-avatar-circle">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <p class="user-name" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</p>
                        <span class="user-role-badge">{{ Auth::user()->role }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn-sidebar-logout" title="Keluar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile Floating Toggle Button -->
        <button class="mobile-toggle-btn" type="button" onclick="document.body.classList.toggle('mobile-sidebar-open')">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        @endauth

        <!-- Main Workspace -->
        <div class="app-main">
            @auth
            <!-- Top Header Bar with Live Real-time Clock (WIB) -->
            <header class="bg-white border-bottom px-4 py-2.5 d-flex align-items-center justify-content-between shadow-sm" style="min-height: 52px;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 small d-flex align-items-center gap-1.5 fw-semibold" style="font-size: 0.78rem;">
                        <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;" role="status"></span>
                        <i class="bi bi-clock-history"></i> Real-time System (WIB)
                    </span>
                    <span class="text-muted small fw-medium d-none d-md-inline ms-1">RSPAD Gatot Soebroto — SIMRS</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div id="realtimeSystemClock" class="fw-bold text-dark font-monospace" style="font-size: 0.92rem; letter-spacing: 0.04em; color: #1e293b !important;">--:--:-- WIB</div>
                        <div id="realtimeSystemDate" class="text-muted small" style="font-size: 0.73rem;">---</div>
                    </div>
                </div>
            </header>
            @endauth

            <!-- Content Area -->
            <main class="container-fluid p-4">
                <!-- Universal Notification Pop-up Modal -->
                @if(session('success'))
                <div class="modal fade show d-block" id="appSuccessNotificationModal" tabindex="-1" style="background: rgba(0, 0, 0, 0.45); z-index: 1070;" aria-modal="true" role="dialog">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow-lg text-center p-3" style="border-radius: 16px;">
                            <div class="modal-body p-3">
                                <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 58px; height: 58px; background-color: #E8F5E9; color: #2E7D32;">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                </div>
                                <h6 class="fw-bold text-dark mb-2">Berhasil!</h6>
                                <p class="text-muted small mb-3">{{ session('success') }}</p>
                                <button type="button" class="btn btn-sm btn-rspad-primary px-4 rounded-pill fw-semibold" onclick="document.getElementById('appSuccessNotificationModal').remove()">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    setTimeout(function() {
                        var m = document.getElementById('appSuccessNotificationModal');
                        if (m) {
                            m.style.transition = 'opacity 0.25s ease';
                            m.style.opacity = '0';
                            setTimeout(function(){ m.remove(); }, 250);
                        }
                    }, 4000);
                </script>
                @endif

                @if(session('error') || $errors->any())
                <div class="modal fade show d-block" id="appErrorNotificationModal" tabindex="-1" style="background: rgba(0, 0, 0, 0.45); z-index: 1070;" aria-modal="true" role="dialog">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow-lg text-center p-3" style="border-radius: 16px;">
                            <div class="modal-body p-3">
                                <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 58px; height: 58px; background-color: #FFEBEE; color: #C62828;">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                </div>
                                <h6 class="fw-bold text-dark mb-2">Perhatian / Gagal</h6>
                                <p class="text-muted small mb-3">
                                    {{ session('error') ?? $errors->first() }}
                                </p>
                                <button type="button" class="btn btn-sm btn-danger px-4 rounded-pill fw-semibold text-white" onclick="document.getElementById('appErrorNotificationModal').remove()">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    setTimeout(function() {
                        var m = document.getElementById('appErrorNotificationModal');
                        if (m) {
                            m.style.transition = 'opacity 0.25s ease';
                            m.style.opacity = '0';
                            setTimeout(function(){ m.remove(); }, 250);
                        }
                    }, 5000);
                </script>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script (Persisted) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const body = document.body;

            // Restore saved collapsed state
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                body.classList.add('sidebar-collapsed');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = body.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
                });
            }

            // Real-time System Clock (WIB - Asia/Jakarta)
            function updateRealtimeClock() {
                const now = new Date();
                const clockEl = document.getElementById('realtimeSystemClock');
                const dateEl = document.getElementById('realtimeSystemDate');
                
                if (clockEl) {
                    const timeStr = now.toLocaleTimeString('id-ID', {
                        timeZone: 'Asia/Jakarta',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    }).replace(/\./g, ':');
                    clockEl.textContent = timeStr + ' WIB';
                }
                
                if (dateEl) {
                    const dateStr = now.toLocaleDateString('id-ID', {
                        timeZone: 'Asia/Jakarta',
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    dateEl.textContent = dateStr;
                }
            }
            setInterval(updateRealtimeClock, 1000);
            updateRealtimeClock();
        });
    </script>
    @yield('scripts')
</body>
</html>
