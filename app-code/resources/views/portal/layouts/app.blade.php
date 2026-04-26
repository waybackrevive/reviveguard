<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Client Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --portal-bg: #f4f8fb;
            --portal-bg-accent: radial-gradient(circle at top left, rgba(7, 106, 122, 0.1), transparent 34%), linear-gradient(180deg, #f8fbfd 0%, #eef4f7 100%);
            --portal-sidebar-bg: rgba(255, 255, 255, 0.94);
            --portal-sidebar-border: #dbe5ec;
            --portal-panel: rgba(255, 255, 255, 0.94);
            --portal-panel-strong: #ffffff;
            --portal-panel-soft: #eef5f7;
            --portal-border: #dbe5ec;
            --portal-text: #142233;
            --portal-text-muted: #64748b;
            --portal-text-soft: #8a9aae;
            --portal-link: #0f766e;
            --portal-brand: #0f766e;
            --portal-brand-strong: #0b3a53;
            --portal-brand-soft: rgba(15, 118, 110, 0.12);
            --portal-danger-soft: #fff1f2;
            --portal-danger-text: #be123c;
            --portal-success-soft: #ecfdf5;
            --portal-success-text: #047857;
            --portal-warning-soft: #fffbeb;
            --portal-warning-text: #b45309;
            --portal-shadow: 0 16px 40px rgba(11, 29, 44, 0.08);
        }

        body.portal-theme-dark {
            --portal-bg: #07111f;
            --portal-bg-accent: radial-gradient(circle at top left, rgba(16, 185, 129, 0.08), transparent 26%), linear-gradient(180deg, #07111f 0%, #081426 100%);
            --portal-sidebar-bg: rgba(10, 20, 35, 0.94);
            --portal-sidebar-border: #1e3147;
            --portal-panel: rgba(10, 20, 35, 0.88);
            --portal-panel-strong: #0d1b30;
            --portal-panel-soft: #12233a;
            --portal-border: #203246;
            --portal-text: #ebf2f9;
            --portal-text-muted: #a2b3c7;
            --portal-text-soft: #7890a9;
            --portal-link: #5eead4;
            --portal-brand: #34d399;
            --portal-brand-strong: #d9fff5;
            --portal-brand-soft: rgba(52, 211, 153, 0.12);
            --portal-danger-soft: rgba(190, 24, 93, 0.18);
            --portal-danger-text: #fda4af;
            --portal-success-soft: rgba(16, 185, 129, 0.18);
            --portal-success-text: #86efac;
            --portal-warning-soft: rgba(217, 119, 6, 0.16);
            --portal-warning-text: #fde68a;
            --portal-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }

        html, body {
            min-height: 100%;
        }

        body {
            background: var(--portal-bg-accent);
            color: var(--portal-text);
            font-family: 'Manrope', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .portal-sidebar {
            background: var(--portal-sidebar-bg);
            border-color: var(--portal-sidebar-border);
            backdrop-filter: blur(18px);
        }

        .portal-divider {
            border-color: var(--portal-border);
        }

        .portal-topbar,
        .portal-panel,
        .portal-modal-panel {
            background: var(--portal-panel);
            border: 1px solid var(--portal-border);
            box-shadow: var(--portal-shadow);
        }

        .portal-panel-strong {
            background: var(--portal-panel-strong);
            border: 1px solid var(--portal-border);
            box-shadow: var(--portal-shadow);
        }

        .portal-panel-soft {
            background: var(--portal-panel-soft);
            border: 1px solid var(--portal-border);
        }

        .portal-title {
            color: var(--portal-text);
        }

        .portal-muted {
            color: var(--portal-text-muted);
        }

        .portal-soft {
            color: var(--portal-text-soft);
        }

        .portal-link {
            color: var(--portal-link);
        }

        .portal-link:hover {
            text-decoration: underline;
        }

        .portal-nav-link {
            color: var(--portal-text-muted);
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .portal-nav-link:hover {
            background: var(--portal-brand-soft);
            color: var(--portal-brand-strong);
        }

        .portal-nav-link-active {
            background: var(--portal-brand-soft);
            color: var(--portal-brand-strong);
        }

        .portal-btn-icon,
        .portal-input,
        .portal-select,
        .portal-textarea,
        .portal-btn-secondary {
            background: var(--portal-panel-strong);
            border: 1px solid var(--portal-border);
            color: var(--portal-text);
        }

        .portal-input::placeholder,
        .portal-textarea::placeholder {
            color: var(--portal-text-soft);
        }

        .portal-btn-icon:hover,
        .portal-btn-secondary:hover {
            background: var(--portal-panel-soft);
        }

        .portal-input:focus,
        .portal-select:focus,
        .portal-textarea:focus {
            outline: none;
            border-color: rgba(15, 118, 110, 0.5);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.14);
        }

        .portal-btn-primary {
            background: linear-gradient(135deg, #0f766e 0%, #0b3a53 100%);
            color: #f8fffd;
        }

        .portal-btn-primary:hover {
            filter: brightness(1.04);
        }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
        }

        .portal-badge-success {
            background: var(--portal-success-soft);
            color: var(--portal-success-text);
        }

        .portal-badge-warning {
            background: var(--portal-warning-soft);
            color: var(--portal-warning-text);
        }

        .portal-badge-danger {
            background: var(--portal-danger-soft);
            color: var(--portal-danger-text);
        }

        .portal-badge-neutral {
            background: var(--portal-panel-soft);
            color: var(--portal-text-muted);
        }

        .portal-table-head {
            background: var(--portal-panel-soft);
            color: var(--portal-text-muted);
        }

        .portal-table-row {
            border-color: var(--portal-border);
        }

        .portal-table-row-hover:hover {
            background: var(--portal-panel-soft);
        }

        .portal-toast {
            background: var(--portal-panel-strong);
            border: 1px solid var(--portal-border);
            color: var(--portal-text-muted);
            box-shadow: var(--portal-shadow);
        }

        @media (min-width: 1024px) {
            body.portal-sidebar-collapsed #portalSidebar {
                transform: translateX(-100%);
            }

            body.portal-sidebar-collapsed #portalMain {
                padding-left: 0 !important;
            }
        }
    </style>
    @livewireStyles
</head>
<body class="h-full">
@php $current = request()->route()->getName(); @endphp

<div class="min-h-full flex">
    <aside id="portalSidebar" class="portal-sidebar hidden lg:flex lg:flex-col lg:w-72 lg:fixed lg:inset-y-0 border-r transition-transform duration-200">
        <div class="flex h-20 items-center px-6 border-b portal-divider">
            <div>
                <span class="inline-flex items-center gap-2 text-xl font-extrabold tracking-tight portal-title">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span>Revive</span><span class="text-emerald-500">Guard</span>
                </span>
                <p class="mt-1 text-xs portal-muted">Client portal by WaybackRevive LLC</p>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="{{ route('portal.dashboard') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.dashboard') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('portal.events') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.events') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Activity Log
            </a>
            <a href="{{ route('portal.reports') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.reports') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>
            <a href="{{ route('portal.backups') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.backups') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Backups
            </a>
            <a href="{{ route('portal.tickets') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.tickets') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Support
            </a>

            <div class="pt-4 mt-4 border-t portal-divider space-y-1">
                <a href="{{ route('portal.account') }}" class="portal-nav-link {{ str_starts_with($current, 'portal.account') ? 'portal-nav-link-active' : '' }} flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Account
                </a>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="portal-nav-link w-full flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign out
                    </button>
                </form>
            </div>

            <div class="mt-6 rounded-2xl portal-panel-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] portal-soft">Managed Service</p>
                <p class="mt-2 text-sm font-semibold portal-title">Transparent website care</p>
                <p class="mt-1 text-xs portal-muted">Monitoring, backup visibility, reports, and support in one place.</p>
            </div>
        </nav>
    </aside>

    <div class="lg:hidden w-full">
        <div class="portal-sidebar flex items-center justify-between h-16 px-4 border-b portal-divider">
            <span class="inline-flex items-center gap-2 text-lg font-extrabold tracking-tight portal-title"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span><span>Revive</span><span class="text-emerald-500">Guard</span></span>
            <div class="flex items-center gap-2">
                <button id="themeToggleMobile" type="button" class="portal-btn-icon inline-flex items-center justify-center h-10 w-10 rounded-xl" aria-label="Toggle theme">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8-9h1M3 12H2m15.364 6.364l.707.707M6.929 6.929l-.707-.707m12.142 0l-.707.707M6.929 17.071l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <details class="relative">
                    <summary class="portal-btn-icon cursor-pointer list-none p-2 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </summary>
                    <nav class="portal-modal-panel absolute right-0 mt-2 w-56 rounded-2xl py-2 z-50">
                        <a href="{{ route('portal.dashboard') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Dashboard</a>
                        <a href="{{ route('portal.events') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Activity Log</a>
                        <a href="{{ route('portal.reports') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Reports</a>
                        <a href="{{ route('portal.backups') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Backups</a>
                        <a href="{{ route('portal.tickets') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Support</a>
                        <a href="{{ route('portal.account') }}" class="portal-nav-link block px-4 py-2.5 text-sm font-semibold">Account</a>
                        <div class="border-t mt-2 pt-2 portal-divider">
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit" class="portal-nav-link w-full text-left px-4 py-2.5 text-sm font-semibold">Sign out</button>
                            </form>
                        </div>
                    </nav>
                </details>
            </div>
        </div>
    </div>

    <main id="portalMain" class="flex-1 lg:pl-72 transition-all duration-200">
        <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-7xl mx-auto">
            <div class="portal-topbar hidden lg:flex items-center justify-between mb-6 rounded-2xl px-4 py-4">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" type="button" class="portal-btn-icon inline-flex items-center justify-center h-10 w-10 rounded-xl" aria-label="Toggle sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <p class="text-sm font-extrabold portal-title">Client Portal</p>
                        <p class="text-xs portal-muted">Monitored by ReviveGuard, operated by WaybackRevive LLC</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="themeToggleDesktop" type="button" class="portal-btn-secondary inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold" aria-label="Toggle theme">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8-9h1M3 12H2m15.364 6.364l.707.707M6.929 6.929l-.707-.707m12.142 0l-.707.707M6.929 17.071l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span id="themeToggleLabel">Dark mode</span>
                    </button>
                    <div class="portal-badge portal-badge-success">Secure session active</div>
                </div>
            </div>

            <div class="lg:hidden mb-4 rounded-2xl portal-panel-soft px-4 py-3 text-xs font-medium portal-muted">
                Protected by ReviveGuard • WaybackRevive LLC
            </div>

            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
<script>
    (function () {
        var body = document.body;
        var sidebarToggle = document.getElementById('sidebarToggle');
        var themeToggleDesktop = document.getElementById('themeToggleDesktop');
        var themeToggleMobile = document.getElementById('themeToggleMobile');
        var themeLabel = document.getElementById('themeToggleLabel');
        var sidebarKey = 'portal_sidebar_collapsed';
        var themeKey = 'portal_theme';

        function applyTheme(theme) {
            var dark = theme === 'dark';
            body.classList.toggle('portal-theme-dark', dark);
            if (themeLabel) {
                themeLabel.textContent = dark ? 'Light mode' : 'Dark mode';
            }
        }

        var savedTheme = localStorage.getItem(themeKey) || 'light';
        applyTheme(savedTheme);

        if (localStorage.getItem(sidebarKey) === '1') {
            body.classList.add('portal-sidebar-collapsed');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                body.classList.toggle('portal-sidebar-collapsed');
                localStorage.setItem(sidebarKey, body.classList.contains('portal-sidebar-collapsed') ? '1' : '0');
            });
        }

        function bindThemeToggle(button) {
            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                var nextTheme = body.classList.contains('portal-theme-dark') ? 'light' : 'dark';
                localStorage.setItem(themeKey, nextTheme);
                applyTheme(nextTheme);
            });
        }

        bindThemeToggle(themeToggleDesktop);
        bindThemeToggle(themeToggleMobile);
    })();
</script>
</body>
</html>
