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
    <!-- Bootstrap Icons -->
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
            /* User's Exact 5-Color Green Palette */
            --palette-1: #B2E0B2; /* Light Mint */
            --palette-2: #8CCB8C; /* Soft Sage Green */
            --palette-3: #5DAA5D; /* Medium Sage Green */
            --palette-4: #3B8A3B; /* Deep Forest Green */
            --palette-5: #2A6A2A; /* Dark Forest Green */

            --sidebar-width: 260px;
            --sidebar-collapsed-width: 72px;
            --bg-neutral: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
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

        /* App Layout Wrapper */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left Sidebar Styling */
        .app-sidebar {
            width: var(--sidebar-width);
            background-color: var(--palette-5);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.12);
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
            color: var(--palette-2);
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
            color: var(--palette-2);
            transition: color 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-link:hover svg {
            color: #ffffff;
        }

        .sidebar-link.active {
            color: #ffffff;
            background-color: var(--palette-4);
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            border-left: 4px solid var(--palette-1);
        }

        .sidebar-link.active svg {
            color: var(--palette-1);
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
            background-color: var(--palette-5);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid var(--palette-4);
            padding: 10px 14px;
            vertical-align: middle;
        }

        .table-clean td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table-clean tfoot {
            background-color: rgba(178, 224, 178, 0.25);
            font-weight: 700;
            color: var(--palette-5);
        }

        /* Buttons */
        .btn-rspad-primary {
            background-color: var(--palette-5);
            color: #ffffff;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .btn-rspad-primary:hover {
            background-color: var(--palette-4);
            color: #ffffff;
        }

        .btn-outline-rspad {
            color: var(--palette-5);
            border-color: var(--palette-3);
            background-color: transparent;
        }
        .btn-outline-rspad:hover {
            background-color: var(--palette-5);
            color: #ffffff;
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
                <div class="nav-category">Navigasi Utama</div>
                
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('imports.index') }}" class="sidebar-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" title="Import SIMRS">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span>Import SIMRS</span>
                </a>

                <div class="nav-category mt-3">Laporan Rekapitulasi</div>

                <a href="{{ route('reports.rl34') }}" class="sidebar-link {{ request()->routeIs('reports.rl34') ? 'active' : '' }}" title="RL 3.4 (Pengunjung)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>RL 3.4 (Pengunjung)</span>
                </a>

                <a href="{{ route('reports.rl35') }}" class="sidebar-link {{ request()->routeIs('reports.rl35') ? 'active' : '' }}" title="RL 3.5 (Kunjungan Poli)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>RL 3.5 (Kunjungan Poli)</span>
                </a>

                <a href="{{ route('reports.puskesad') }}" class="sidebar-link {{ request()->routeIs('reports.puskesad') ? 'active' : '' }}" title="Laporan Puskesad">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Laporan Puskesad</span>
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
            <!-- Content Area (Clean Workspace without Header Bar) -->
            <main class="container-fluid p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 small" style="background-color: var(--palette-1); color: var(--palette-5);" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 small" role="alert">
                        <strong>Terjadi Kesalahan:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
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
        });
    </script>
    @yield('scripts')
</body>
</html>
