{{-- resources/views/components/bottom-nav.blade.php
     Usage: <x-bottom-nav active="dashboard" />
     UI phase: static links. Backend phase: routes already correct. --}}
@props(['active' => 'menu'])

<nav class="bottom-nav" x-data>
    <a href="{{ route('dashboard') }}"
       class="bottom-nav__item {{ $active === 'dashboard' ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>
        </svg>
        <span>Dashboard</span>
    </a>

    <a href="{{ route('offers') }}"
       class="bottom-nav__item {{ $active === 'offers' ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7"/>
        </svg>
        <span>Offers</span>
    </a>

    <a href="{{ route('news') }}"
       class="bottom-nav__item {{ $active === 'news' ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/>
        </svg>
        <span>News</span>
    </a>

    {{-- Menu opens the shared drawer via a global Alpine store --}}
    <button type="button" @click="$store.drawer.open = true"
            class="bottom-nav__item {{ $active === 'menu' ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
        <span>Menu</span>
    </button>
</nav>

{{-- Register the drawer store once, e.g. in resources/js/app.js:

    document.addEventListener('alpine:init', () => {
        Alpine.store('drawer', { open: false });
    });
--}}
