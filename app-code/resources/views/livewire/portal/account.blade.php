<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Account Settings</h1>

    {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200">
        @foreach (['profile' => 'Profile & Password', 'plan' => 'Plan', 'billing' => 'Billing & Invoices'] as $tab => $label)
            <button
                wire:click="$set('activeTab', '{{ $tab }}')"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                    {{ $activeTab === $tab
                        ? 'border-emerald-600 text-emerald-700'
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── Profile & Password tab ───────────────────────────────────────── --}}
    @if ($activeTab === 'profile')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Your Details</h2>

            @if ($profileSaved)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    Changes saved.
                </div>
            @endif

            <form wire:submit.prevent="saveProfile" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" wire:model="name"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="email"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone / WhatsApp</label>
                    <input type="text" wire:model="phone" placeholder="Optional"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('phone') border-red-400 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display timezone</label>
                    <select wire:model="timezone"
                            class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('timezone') border-red-400 @enderror">
                        @foreach (\App\Support\ClientTimezone::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-gray-500 max-w-md">Uptime checks, incidents, and alerts show in this timezone. Billing dates stay as calendar dates. Server stores all times in UTC.</p>
                </div>

                <button type="submit"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Save Changes
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Change Password</h2>

            @if ($passwordSaved)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    Password updated successfully.
                </div>
            @endif

            <form wire:submit.prevent="changePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                    <input type="password" wire:model="currentPassword"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('currentPassword') border-red-400 @enderror">
                    @error('currentPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input type="password" wire:model="newPassword" minlength="8"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('newPassword') border-red-400 @enderror">
                    @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                    <input type="password" wire:model="confirmPassword" minlength="8"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <button type="submit"
                        class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Update Password
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Plan tab ─────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'plan')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @error('planChange')
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm text-gray-700">
            <p class="font-semibold text-gray-900">Done-for-you protection by the WaybackRevive team</p>
            <p class="mt-1 text-gray-600">Same experts who restored 500+ websites — we watch, backup, and fix so you don't have to. Upgrade or downgrade anytime from here. Upgrades charge your card today (prorated); downgrades credit your next bill.</p>
        </div>

        {{-- Active subscriptions --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Your subscriptions</h2>
                    <p class="text-sm text-gray-500 mt-0.5">One plan per site — upgrade anytime as your needs grow.</p>
                </div>
                @if ($client->stripeCustomerId())
                    <button type="button" wire:click="openBillingPortal"
                            class="px-4 py-2 text-sm font-semibold text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-50 transition-colors shrink-0"
                            wire:loading.attr="disabled" wire:target="openBillingPortal">
                        Manage billing
                    </button>
                @endif
            </div>

            @error('billing')
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ $message }}</div>
            @enderror

            @if ($siteSubscriptions->isEmpty())
                <p class="text-sm text-gray-500">No active subscriptions yet. <a href="{{ route('portal.sites.add') }}" class="text-brand font-medium hover:underline">Add a site</a> and choose a plan to get started.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs text-gray-500 uppercase tracking-wide">
                                <th class="pb-2 pr-4">Site</th>
                                <th class="pb-2 pr-4">Current plan</th>
                                <th class="pb-2 pr-4">Status</th>
                                <th class="pb-2 pr-4">Next billing</th>
                                <th class="pb-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($siteSubscriptions as $siteSub)
                                @php $currentSlug = $siteSub->plan?->slug; @endphp
                                <tr>
                                    <td class="py-3 pr-4 text-gray-800 font-medium">
                                        {{ $siteSub->site?->displayName() ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-light text-brand">
                                            {{ $siteSub->plan?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $siteSub->isActive() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $siteSub->billingStatusLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-600">
                                        {{ $siteSub->nextBillingDate()?->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="py-3">
                                        @if ($siteSub->site_id && $currentSlug !== 'shield')
                                            <button type="button" wire:click="goToSitePlan('{{ $siteSub->site_id }}')"
                                                class="text-xs font-semibold text-brand hover:underline">
                                                Compare &amp; upgrade
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p class="mt-5 pt-5 border-t border-gray-100 text-xs text-gray-400">
                <strong>Manage billing</strong> is for payment method &amp; receipts only. To change plan, use the buttons below — upgrades charge your card today (prorated); downgrades credit your next bill.
            </p>
        </div>

        {{-- Plan cards with features --}}
        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-1">Compare plans</h2>
            <p class="text-sm text-gray-500 mb-4">See exactly what you get at each level — no surprises.</p>

            <div class="grid gap-4 lg:grid-cols-3">
                @foreach ($plans as $catalogPlan)
                    @php
                        $isCurrentForAny = $siteSubscriptions->contains(fn ($s) => $s->plan_id === $catalogPlan->id);
                        $isRecommended = $catalogPlan->isRecommended();
                    @endphp
                    <div class="bg-white rounded-2xl border-2 p-5 flex flex-col relative
                        {{ $isCurrentForAny ? 'border-brand' : ($isRecommended ? 'border-emerald-200' : 'border-gray-200') }}">
                        @if ($isCurrentForAny)
                            <span class="absolute -top-2.5 left-4 text-[10px] font-bold uppercase tracking-wide bg-brand text-white px-2 py-0.5 rounded">Your plan</span>
                        @elseif ($isRecommended)
                            <span class="absolute -top-2.5 left-4 text-[10px] font-bold uppercase tracking-wide bg-emerald-600 text-white px-2 py-0.5 rounded">Most popular</span>
                        @endif

                        <h3 class="text-lg font-bold text-gray-900">{{ $catalogPlan->name }}</h3>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            ${{ number_format($catalogPlan->price_monthly, 0) }}
                            <span class="text-sm font-normal text-gray-500">/mo per site</span>
                        </p>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ \App\Support\PlanCatalog::tagline($catalogPlan) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ \App\Support\PlanCatalog::bestFor($catalogPlan) }}</p>

                        <ul class="mt-4 space-y-2 flex-1">
                            @foreach (\App\Support\PlanCatalog::included($catalogPlan) as $feature)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong class="font-medium">{{ $feature['name'] }}</strong>@if ($feature['desc'])<span class="text-gray-500"> — {{ $feature['desc'] }}</span>@endif</span>
                                </li>
                            @endforeach
                        </ul>
                        @if (\App\Support\PlanCatalog::notIncluded($catalogPlan))
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-2">Not included</p>
                                <ul class="space-y-1">
                                    @foreach (\App\Support\PlanCatalog::notIncluded($catalogPlan) as $item)
                                        <li class="text-xs text-gray-500">– {{ $item['name'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Full comparison matrix --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 overflow-hidden">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Feature comparison</h2>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="pb-3 pr-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-1/4">Feature</th>
                            @foreach ($plans as $p)
                                <th class="pb-3 px-3 text-center text-xs font-semibold uppercase tracking-wide
                                    {{ $siteSubscriptions->contains(fn ($s) => $s->plan_id === $p->id) ? 'text-brand' : 'text-gray-700' }}">
                                    {{ $p->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($comparisonRows as $row)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-gray-800">{{ $row['label'] }}</td>
                                @foreach ($plans as $p)
                                    <td class="py-3 px-3 text-center text-gray-600 text-xs sm:text-sm">
                                        {{ \App\Support\PlanCatalog::cellForRow($row, $p->slug) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Plan change prompts per site --}}
        @foreach ($sites as $site)
            @if ($site->hasPaidSubscription() && $site->plan)
                @php
                    $hasUpgrade = $plans->contains(fn ($p) => \App\Support\PlanCatalog::isUpgrade($site->plan, $p));
                    $hasDowngrade = $plans->contains(fn ($p) => \App\Support\PlanCatalog::isDowngrade($site->plan, $p));
                @endphp
                @if ($hasUpgrade)
                <div class="bg-white border border-violet-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-sm font-semibold text-gray-900">Upgrade {{ $site->displayName() }}</p>
                    <p class="text-sm text-gray-500 mt-0.5 mb-4">
                        On <strong>{{ $site->plan->name }}</strong> (${{ number_format($site->plan->price_monthly, 0) }}/mo).
                        Your card on file is charged the prorated difference today. Receipt appears under Billing &amp; Invoices.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($plans as $upgradePlan)
                            @if (\App\Support\PlanCatalog::isUpgrade($site->plan, $upgradePlan))
                                <div class="border border-gray-200 rounded-xl p-4">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="font-bold text-gray-900">{{ $upgradePlan->name }}</p>
                                        <p class="text-lg font-bold text-brand">${{ number_format($upgradePlan->price_monthly, 0) }}<span class="text-xs font-normal text-gray-500">/mo</span></p>
                                    </div>
                                    <ul class="mt-3 space-y-1.5">
                                        @foreach (\App\Support\PlanCatalog::upgradeGains($site->plan, $upgradePlan) as $gain)
                                            <li class="text-xs text-gray-700 flex gap-1.5"><span class="text-emerald-500 font-bold">+</span> {{ $gain }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button"
                                        wire:click="openPlanChangeModal('{{ $site->id }}', '{{ $upgradePlan->slug }}')"
                                        wire:loading.attr="disabled"
                                        class="mt-4 w-full text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2.5 rounded-lg">
                                        Upgrade to {{ $upgradePlan->name }}
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                @if ($hasDowngrade)
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-sm font-semibold text-gray-900">Switch to a lower plan — {{ $site->displayName() }}</p>
                    <p class="text-sm text-gray-500 mt-0.5 mb-4">
                        On <strong>{{ $site->plan->name }}</strong>. Changes apply immediately. Unused time is credited toward your next bill — no charge today.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($plans as $lowerPlan)
                            @if (\App\Support\PlanCatalog::isDowngrade($site->plan, $lowerPlan))
                                <div class="border border-gray-200 rounded-xl p-4">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="font-bold text-gray-900">{{ $lowerPlan->name }}</p>
                                        <p class="text-lg font-bold text-gray-700">${{ number_format($lowerPlan->price_monthly, 0) }}<span class="text-xs font-normal text-gray-500">/mo</span></p>
                                    </div>
                                    <button type="button"
                                        wire:click="openPlanChangeModal('{{ $site->id }}', '{{ $lowerPlan->slug }}')"
                                        wire:loading.attr="disabled"
                                        class="mt-4 w-full text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2.5 rounded-lg">
                                        Switch to {{ $lowerPlan->name }}
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            @endif
        @endforeach
    </div>
    @endif

    {{-- ── Billing & Invoices tab ───────────────────────────────────────── --}}
    @if ($activeTab === 'billing')
    <div class="space-y-6">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
            <p class="font-semibold">How billing works</p>
            <ul class="mt-2 space-y-1 list-disc list-inside text-amber-800">
                <li><strong>Upgrades</strong> — your card on file is charged the prorated difference today. A receipt appears below.</li>
                <li><strong>Downgrades</strong> — plan changes immediately; unused time is credited on your next bill.</li>
                <li><strong>Monthly renewals</strong> — charged automatically on your billing date.</li>
            </ul>
        </div>

        @if ($client->stripeCustomerId())
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Payment &amp; subscriptions</h2>
                <p class="text-sm text-gray-500 mb-4">Update your card, download receipts, or manage subscriptions.</p>
                <button type="button" wire:click="openBillingPortal"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors"
                        wire:loading.attr="disabled" wire:target="openBillingPortal">
                    Open billing portal
                </button>
            </div>
        @endif

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Invoices</h2>

        @if ($invoices->isEmpty())
            <p class="text-sm text-gray-500">No invoices yet. Your first invoice will appear here after your next billing cycle.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs text-gray-500 uppercase tracking-wide">
                            <th class="pb-2 pr-4">Invoice #</th>
                            <th class="pb-2 pr-4">Description</th>
                            <th class="pb-2 pr-4">Period</th>
                            <th class="pb-2 pr-4">Amount</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-gray-800 text-xs">{{ $invoice->invoice_number }}</td>
                                <td class="py-3 pr-4 text-gray-600 text-xs max-w-[12rem]">{{ $invoice->summaryDescription() }}</td>
                                <td class="py-3 pr-4 text-gray-600">
                                    {{ $invoice->period_start->format('M j') }} – {{ $invoice->period_end->format('M j, Y') }}
                                </td>
                                <td class="py-3 pr-4 font-medium text-gray-800">{{ $invoice->formatted_total }}</td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $invoice->isPlanChangeCredit() ? 'bg-blue-100 text-blue-700' : ($invoice->status === 'paid' ? 'bg-green-100 text-green-700' : ($invoice->status === 'void' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-700')) }}">
                                        {{ $invoice->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    @if ($invoice->receiptUrl())
                                        <a href="{{ $invoice->receiptUrl() }}" target="_blank" rel="noopener"
                                           class="text-emerald-700 hover:underline text-xs">
                                            {{ $invoice->stripe_hosted_invoice_url ? 'View receipt' : 'Download' }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </div>
    @endif

    <x-portal.plan-change-modal :show="$showPlanChangeModal" :modal="$planChangeModal" />
</div>
