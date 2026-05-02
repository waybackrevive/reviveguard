<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Client Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
<body class="h-full bg-gray-50 text-gray-900">

<div class="min-h-full flex">

    {{-- ── Sidebar (desktop) ─────────────────────────────────────────── --}}
    <aside id="portalSidebar" class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-200 transition-transform duration-200">
        <div class="flex h-16 items-center px-6 border-b border-gray-200">
            <span class="inline-flex items-center gap-2 text-xl font-bold">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-gray-900">Revive</span><span class="text-emerald-600">Guard</span>
            </span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            @php $current = request()->route()->getName(); @endphp

            {{-- ── Prominent Add Website CTA (per portal spec) ── --}}
            <a href="{{ route('portal.my-websites') }}"
               class="flex items-center justify-center gap-2 w-full px-3 py-2 mb-3 rounded-lg text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add website
            </a>

            <a href="{{ route('portal.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.dashboard') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <a href="{{ route('portal.my-websites') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.my-websites') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                My Websites
            </a>

            <a href="{{ route('portal.events') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.events') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Activity Log
            </a>

            <a href="{{ route('portal.reports') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.reports') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>

            <a href="{{ route('portal.backups') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.backups') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Backups
            </a>

            <a href="{{ route('portal.tickets') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.tickets') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Support
            </a>

            <div class="pt-4 mt-4 border-t border-gray-200 space-y-1">
                <a href="{{ route('portal.account') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.account') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Account
                </a>

                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign out
                    </button>
                </form>
            </div>

            <div class="px-3 pt-4 mt-4 border-t border-gray-200 text-xs text-gray-400">
                Operated by WaybackRevive LLC
            </div>
        </nav>
    </aside>

    {{-- ── Mobile top nav ──────────────────────────────────────────────── --}}
    <div class="lg:hidden w-full">
        <div class="flex items-center justify-between h-14 px-4 bg-white border-b border-gray-200">
            <span class="inline-flex items-center gap-2 text-lg font-bold"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span><span class="text-gray-900">Revive</span><span class="text-emerald-600">Guard</span></span>
            <details class="relative">
                <summary class="cursor-pointer list-none p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <nav class="absolute right-0 mt-1 w-52 bg-white border border-gray-200 rounded-xl shadow-lg py-2 z-50">
                    <a href="{{ route('portal.dashboard') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <a href="{{ route('portal.my-websites') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">My Websites</a>
                    <a href="{{ route('portal.events') }}"      class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Activity Log</a>
                    <a href="{{ route('portal.reports') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Reports</a>
                    <a href="{{ route('portal.backups') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Backups</a>
                    <a href="{{ route('portal.tickets') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Support</a>
                    <a href="{{ route('portal.account') }}"   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Account</a>
                    <div class="border-t border-gray-200 mt-1 pt-1">
                        <form method="POST" action="{{ route('portal.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sign out</button>
                        </form>
                    </div>
                </nav>
            </details>
        </div>
    </div>

    {{-- ── Main content ────────────────────────────────────────────────── --}}
    <main id="portalMain" class="flex-1 lg:pl-64 transition-all duration-200">
        <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-6xl mx-auto">
            <div class="hidden lg:flex items-center justify-between mb-6 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" type="button" class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-100" aria-label="Toggle sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Client Portal</p>
                        <p class="text-xs text-gray-400">Monitored by ReviveGuard, operated by WaybackRevive LLC</p>
                    </div>
                </div>
                <div class="text-xs text-emerald-700 border border-emerald-200 bg-emerald-50 rounded-full px-3 py-1">Secure session active</div>
            </div>

            <div class="lg:hidden mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
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
        var toggle = document.getElementById('sidebarToggle');
        var storageKey = 'portal_sidebar_collapsed';

        if (localStorage.getItem(storageKey) === '1') {
            body.classList.add('portal-sidebar-collapsed');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                body.classList.toggle('portal-sidebar-collapsed');
                localStorage.setItem(storageKey, body.classList.contains('portal-sidebar-collapsed') ? '1' : '0');
            });
        }
    })();
</script>
</body>
</html>






