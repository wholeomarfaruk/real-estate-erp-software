@php
    $active ??= null;

    $navItems = [
        [
            'key'   => 'chart-of-accounts',
            'route' => 'admin.accounts.chart-of-accounts.index',
            'label' => 'Chart of Accounts',
            'icon'  => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
        ],
        [
            'key'   => 'payment-requests',
            'route' => 'admin.accounts.banking.index',
            'label' => 'Payment Requests',
            'icon'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        ],
        [
            'key'   => 'bank-accounts',
            'route' => 'admin.accounts.banks.list',
            'label' => 'Bank Accounts',
            'icon'  => '<path d="M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M2 10l10-7 10 7"/>',
        ],
        [
            'key'   => 'transactions',
            'route' => 'admin.accounts.transactions.index',
            'label' => 'Transactions',
            'icon'  => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        ],
        [
            'key'   => 'reports',
            'route' => 'admin.accounts.banking.reports',
            'label' => 'Reports',
            'icon'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        ],
    ];
@endphp

<nav class="flex gap-0 border-b border-gray-200 mb-5 -mx-0" role="tablist">
    @foreach($navItems as $tab)
        @php
            $isActive = $active === $tab['key'] || Route::is($tab['route']);
        @endphp
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                  {{ $isActive
                     ? 'border-gray-900 text-gray-900 font-semibold'
                     : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
           role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                {!! $tab['icon'] !!}
            </svg>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
