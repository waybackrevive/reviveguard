<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Client Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] },
                    colors: { brand: { DEFAULT: '#0A7A3E', dark: '#086332', light: '#E8F5EE' } },
                    borderRadius: { card: '10px' }
                }
            }
        }
    </script>
    <style>
        @media (min-width: 1024px) {
            body.portal-sidebar-collapsed #portalSidebar { transform: translateX(-100%); }
            body.portal-sidebar-collapsed #portalMain { padding-left: 0 !important; }
            body.portal-sidebar-collapsed #sidebarExpandBtn { display: inline-flex; }
        }
    </style>
    <script>
        (function () {
            try {
                if (localStorage.getItem('rg-sidebar-collapsed') === '1') {
                    document.body.classList.add('portal-sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @livewireStyles
</head>
<body class="h-full bg-gray-50 text-gray-900 font-sans antialiased" x-data="{ sidebarCollapsed: false }" x-init="
    try { sidebarCollapsed = localStorage.getItem('rg-sidebar-collapsed') === '1'; } catch (e) {}
    $watch('sidebarCollapsed', v => {
        document.body.classList.toggle('portal-sidebar-collapsed', v);
        try { localStorage.setItem('rg-sidebar-collapsed', v ? '1' : '0'); } catch (e) {}
    });
    if (sidebarCollapsed) document.body.classList.add('portal-sidebar-collapsed');
">

<div class="min-h-full flex flex-col lg:flex-row relative">

    <aside id="portalSidebar" class="hidden lg:flex lg:flex-col lg:w-60 lg:fixed lg:inset-y-0 bg-white border-r border-gray-200 transition-transform duration-200 z-30">
        <div class="flex h-16 items-center justify-between px-5 border-b border-gray-200">
            <span class="inline-flex items-center gap-2 text-lg font-bold tracking-tight">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white text-xs font-bold">RG</span>
                <span>Revive<span class="text-brand">Guard</span></span>
            </span>
            <button type="button" @click="sidebarCollapsed = true" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Collapse sidebar" aria-label="Collapse sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
            @php $current = request()->route()->getName(); @endphp

            <a href="{{ route('portal.sites.add') }}"
               class="flex items-center justify-center gap-2 w-full px-3 py-2.5 mb-4 rounded-card text-sm font-semibold bg-brand hover:bg-brand-dark text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add site
            </a>

            @php
                $nav = [
                    ['portal.sites', 'Sites', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z'],
                    ['portal.alerts', 'Alerts', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ['portal.reports', 'Reports', 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['portal.addons', 'Add-ons', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['portal.tickets', 'Support', 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp

            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, $route) ? 'bg-brand-light text-brand' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach

            <div class="pt-4 mt-4 border-t border-gray-200 space-y-1">
                <a href="{{ route('portal.billing') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ str_starts_with($current, 'portal.billing') || str_starts_with($current, 'portal.account') ? 'bg-brand-light text-brand' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Billing
                </a>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign out
                    </button>
                </form>
            </div>

            <p class="px-3 pt-4 text-[11px] text-gray-400">Operated by WaybackRevive LLC</p>
        </nav>
    </aside>

    {{-- Mobile nav --}}
    <div class="lg:hidden w-full" x-data="{ open: false }">
        <div class="flex items-center justify-between h-14 px-4 bg-white border-b border-gray-200">
            <span class="inline-flex items-center gap-2 font-bold text-sm">
                <span class="h-7 w-7 rounded-lg bg-brand text-white text-[10px] flex items-center justify-center font-bold">RG</span>
                Revive<span class="text-brand">Guard</span>
            </span>
            <button @click="open = !open" type="button" class="p-2 rounded-lg hover:bg-gray-100" aria-label="Menu">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <nav x-show="open" class="bg-white border-b border-gray-200 py-2 z-50 shadow-lg">
            <a href="{{ route('portal.sites') }}" class="block px-5 py-2.5 text-sm">Sites</a>
            <a href="{{ route('portal.alerts') }}" class="block px-5 py-2.5 text-sm">Alerts</a>
            <a href="{{ route('portal.reports') }}" class="block px-5 py-2.5 text-sm">Reports</a>
            <a href="{{ route('portal.addons') }}" class="block px-5 py-2.5 text-sm">Add-ons</a>
            <a href="{{ route('portal.tickets') }}" class="block px-5 py-2.5 text-sm">Support</a>
            <a href="{{ route('portal.billing') }}" class="block px-5 py-2.5 text-sm">Billing</a>
        </nav>
    </div>

    <main id="portalMain" class="flex-1 lg:pl-60 transition-all duration-200">
        <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-7xl mx-auto">
            <button id="sidebarExpandBtn" type="button" @click="sidebarCollapsed = false"
                class="hidden mb-4 items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-200 bg-white px-3 py-1.5 rounded-lg shadow-sm"
                title="Show sidebar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                Menu
            </button>
            @php $client = auth('client')->user(); @endphp
    @if (\App\Support\StripeConfig::isTestMode())
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-900">
            <strong>Stripe test mode.</strong> Payments are simulated — no real charges.
        </div>
    @endif
            @if ($client?->workspace_name)
                <p class="hidden lg:block text-xs text-gray-400 mb-4">{{ $client->workspace_name }}</p>
            @endif
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
