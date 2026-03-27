<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Revive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #e94560;
            --dark-bg: #0f172a;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
        }

        body { background: #f1f5f9; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--sidebar-bg);
            z-index: 1000;
            transition: transform .3s;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .sidebar-brand .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), #0f3460);
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .nav-label {
            font-size: .65rem; font-weight: 700; letter-spacing: .1em;
            color: #64748b; text-transform: uppercase;
            padding: .75rem 1.25rem .25rem;
        }
        #sidebar .nav-link {
            color: #94a3b8;
            padding: .6rem 1.25rem;
            border-radius: .5rem;
            margin: .1rem .75rem;
            display: flex; align-items: center; gap: .65rem;
            font-size: .875rem; font-weight: 500;
            transition: background .15s, color .15s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: var(--sidebar-hover);
            color: #f8fafc;
        }
        #sidebar .nav-link.active { color: var(--primary); }
        #sidebar .nav-link i { font-size: 1.1rem; width: 1.2rem; text-align: center; }

        /* Content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Topbar */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }

        /* Stat cards */
        .stat-card {
            border: none; border-radius: .875rem;
            padding: 1.5rem;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), #0f3460);
            box-shadow: 0 4px 15px rgba(233,69,96,.25);
        }
        .stat-card.green  { background: linear-gradient(135deg, #10b981, #065f46); box-shadow: 0 4px 15px rgba(16,185,129,.25); }
        .stat-card.blue   { background: linear-gradient(135deg, #3b82f6, #1e3a8a); box-shadow: 0 4px 15px rgba(59,130,246,.25); }
        .stat-card.purple { background: linear-gradient(135deg, #8b5cf6, #4c1d95); box-shadow: 0 4px 15px rgba(139,92,246,.25); }
        .stat-card.orange { background: linear-gradient(135deg, #f59e0b, #78350f); box-shadow: 0 4px 15px rgba(245,158,11,.25); }
        .stat-card.teal   { background: linear-gradient(135deg, #14b8a6, #134e4a); box-shadow: 0 4px 15px rgba(20,184,166,.25); }
        .stat-card .icon  { font-size: 2rem; opacity: .85; }
        .stat-card .value { font-size: 2rem; font-weight: 800; line-height: 1; }
        .stat-card .label { font-size: .8rem; opacity: .85; margin-top: .25rem; }

        /* Table */
        .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .table td { vertical-align: middle; font-size: .875rem; }

        /* Badge status */
        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-waiting  { background: #fef3c7; color: #92400e; }
        .badge-ended    { background: #f1f5f9; color: #475569; }
        .badge-scheduled{ background: #ede9fe; color: #5b21b6; }

        @media (max-width: 991px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
    @stack('head')
</head>
<body>

{{-- Sidebar --}}
<div id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2">
        <div class="logo-icon">
            <i class="bi bi-lightning-charge-fill text-white fs-5"></i>
        </div>
        <div>
            <div class="text-white fw-bold" style="font-size:.95rem;line-height:1.2">Revive</div>
            <div class="text-secondary" style="font-size:.7rem">Admin Panel</div>
        </div>
    </div>

    <nav class="mt-2 flex-grow-1">
        <div class="nav-label">Main</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="nav-label">Manage</div>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Users
        </a>
        <a href="{{ route('admin.sessions.index') }}" class="nav-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
            <i class="bi bi-broadcast-pin"></i> Sessions
        </a>
        <a href="{{ route('admin.groups.index') }}" class="nav-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
            <i class="bi bi-collection-fill"></i> Groups
        </a>
    </nav>

    <div class="p-3" style="border-top:1px solid rgba(255,255,255,.07)">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                <i class="bi bi-person-fill text-white"></i>
            </div>
            <div style="overflow:hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size:.8rem">{{ auth()->user()->name }}</div>
                <div class="text-secondary text-truncate" style="font-size:.7rem">{{ auth()->user()->email }}</div>
            </div>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm w-100" style="background:#334155;color:#94a3b8;font-size:.8rem">
                <i class="bi bi-box-arrow-left me-1"></i>Logout
            </button>
        </form>
    </div>
</div>

{{-- Main --}}
<div id="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none" id="sidebar-toggle" style="border:1px solid #e2e8f0">
                <i class="bi bi-list fs-5"></i>
            </button>
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        <span class="text-muted small d-none d-md-inline">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->format('M d, Y') }}
        </span>
    </div>

    <main class="p-4 flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>
@stack('scripts')
</body>
</html>
