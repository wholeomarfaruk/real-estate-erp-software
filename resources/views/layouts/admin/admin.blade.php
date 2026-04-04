<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ERP Software - Star Unity Development</title>
    <link rel="shortcut icon" href="{{ asset('assets/logo/sud-logo.png') }}" type="image/x-icon">
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>

<body x-data class="mx-auto antialiased flex justify-between">
    <!-- Mobile Menu Toggle -->
    <button @click="$store.sidebar.navOpen = !$store.sidebar.navOpen"
        class="sm:hidden absolute top-5 right-5 focus:outline-none">
        <!-- Menu Icons -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" x-bind:class="$store.sidebar.navOpen ? 'hidden' : ''"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>

        <!-- Close Menu -->
        <svg x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
            x-bind:class="$store.sidebar.navOpen ? '' : 'hidden'" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="h-screen z-10  bg-gray-900 transition-all duration-300 space-y-2 fixed sm:sticky flex justify-around  flex-col"
        x-bind:class="{
            'w-64': $store.sidebar.full,
            'w-64 sm:w-20': !$store.sidebar.full,
            'top-0 left-0': $store.sidebar
                .navOpen,
            'top-0 -left-64 sm:left-0': !$store.sidebar.navOpen
        }">
        <div>
            <h1 class="text-white font-black py-4"
                x-bind:class="$store.sidebar.full ? 'text-2xl px-4' : 'text-xl px-4 xm:px-2'">Admin</h1>
        </div>

        <div class="px-4 space-y-2">
            <!-- SideBar Toggle -->
            <button @click="$store.sidebar.full = !$store.sidebar.full"
                class="hidden sm:block focus:outline-none absolute p-1 -right-3 top-10 bg-gray-900 rounded-full shadow-md cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-all duration-300 text-white transform"
                    x-bind:class="$store.sidebar.full ? 'rotate-90' : '-rotate-90 '" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <!-- Sales Point -->

            <div class="h-[70vh]  scrollbar-thumb-gray-900  scrollbar-thin scrollbar-track-transparent"
                :class="$store.sidebar.full ? 'overflow-y-scroll' : ''">

                <!-- Quick Action -->
                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Quick Action</h2>
                </div>
                <a href="#" x-data="tooltip" x-on:mouseover="show = true" x-on:mouseleave="show = false"
                    @click="$store.sidebar.active = 'dashboard' "
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 mb-2
                    {{ Route::currentRouteName() == 'admin.dashboard' ? 'text-gray-200 bg-gray-800' : '' }}">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>

                    <p x-cloak
                        x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full && !show ?
                            'sm:hidden' : ''">
                        Dashboard</p>
                </a>
                <!-- General  -->
                <div class="mt-2 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition> General</h2>
                </div>
                <!-- Home -->
                <a href="{{ route('admin.dashboard') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::currentRouteName() == 'admin.dashboard' ? 'text-gray-200 bg-gray-800' : '' }}

                    ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <p x-cloak
                        x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                            'sm:hidden' : ''">
                        Dashboard</p>
                </a>
                <!-- uploads -->
                <a href="{{ route('admin.uploads') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::currentRouteName() == 'admin.uploads' ? 'text-gray-200 bg-gray-800' : '' }}

                    ">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                    </svg>

                    <p x-cloak
                        x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                            'sm:hidden' : ''">
                        Uploads</p>
                </a>

                <!-- Materials Management -->
                <div x-data="dropdown" class="relative">
                    <div @click="toggle('materials')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full,
                            'text-gray-200 bg-gray-800': $store.sidebar.active == 'materials',
                            'text-gray-400': $store.sidebar.active != 'materials'
                        }">

                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>

                            <p x-cloak class="text-xs"
                                :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Materials Management
                            </p>
                        </div>

                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                            xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                            stroke-width="1.5" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.materials.categories') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Product Categories</a>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">

                        <a href="{{ route('admin.materials.brands') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Product Brands</a>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">

                        <a href="{{ route('admin.materials.products') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Products &amp; Variants</a>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.materials.units') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Product Units</a>
                    </div>

                </div>
                <!-- Projects Management -->
                <div x-data="dropdown" class="relative">
                    <div @click="toggle('projects')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full,
                            'text-gray-200 bg-gray-800': $store.sidebar.active == 'projects',
                            'text-gray-400': $store.sidebar.active != 'projects'
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>

                            <p x-cloak class="text-xs"
                                :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Projects
                            </p>
                        </div>

                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                            xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                            stroke-width="1.5" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.projects.list') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Projects</a>

                        <!-- Properties Submenu -->
                        <div x-data="sub_dropdown" class="relative w-full">
                            <div @click="sub_toggle()" class="flex items-center justify-between cursor-pointer">
                                <p class="hover:text-gray-200 cursor-pointer text-xs">Properties</p>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div x-show="sub_open" class="ml-4 mt-2 space-y-2">
                                <a href="{{ route('admin.floors.list') }}"
                                    class="hover:text-gray-200 cursor-pointer block text-xs">Floors</a>
                                <a href="{{ route('admin.units.list') }}"
                                    class="hover:text-gray-200 cursor-pointer block text-xs">Units</a>
                            </div>
                        </div>

                        <a href="{{ route('admin.project.calendar') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Project Calendar</a>
                    </div>
                </div>
                <!-- Materials Management -->
                <div x-data="dropdown" class="relative">
                    <div @click="toggle('inventory')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full,
                            'text-gray-200 bg-gray-800': $store.sidebar.active == 'inventory',
                            'text-gray-400': $store.sidebar.active != 'inventory'
                        }">

                        <div class="relative flex items-center gap-2">

                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>


                            <p x-cloak class="text-xs"
                                :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Inventory Management
                            </p>
                        </div>

                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                            xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                            stroke-width="1.5" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>

                    </div>

                    @php
                        $inventoryMenuRoutes = [
                            ['label' => 'Inventory Dashboard', 'route' => 'admin.inventory.dashboard', 'permission' => 'inventory.dashboard.view'],
                            ['label' => 'Product Categories', 'route' => Route::has('admin.inventory.product-categories.index') ? 'admin.inventory.product-categories.index' : 'admin.materials.categories', 'permission' => 'inventory.product.view'],
                            ['label' => 'Product Units', 'route' => Route::has('admin.inventory.product-units.index') ? 'admin.inventory.product-units.index' : 'admin.materials.units', 'permission' => 'inventory.product.view'],
                            ['label' => 'Products', 'route' => Route::has('admin.inventory.products.index') ? 'admin.inventory.products.index' : 'admin.materials.products', 'permission' => 'inventory.product.view'],
                            ['label' => 'Stores', 'route' => 'admin.inventory.stores.index', 'permission' => 'inventory.store.view'],
                            ['label' => 'Suppliers', 'route' => 'admin.inventory.suppliers.index', 'permission' => 'inventory.supplier.view'],
                            ['label' => 'Purchase Orders', 'route' => 'admin.inventory.purchase-orders.index', 'permission' => 'inventory.purchase_order.view'],
                            ['label' => 'Stock Receive', 'route' => 'admin.inventory.stock-receives.index', 'permission' => 'inventory.stock.receive.view'],
                            ['label' => 'Purchase Return', 'route' => 'admin.inventory.purchase-returns.index', 'permission' => 'inventory.purchase_return.view'],
                            ['label' => 'Stock Request', 'route' => 'admin.inventory.stock-requests.index', 'permission' => 'inventory.stock_request.view'],
                            ['label' => 'Stock Transfer', 'route' => 'admin.inventory.stock-transfers.index', 'permission' => 'inventory.stock.transfer.view'],
                            ['label' => 'Stock Adjustment', 'route' => 'admin.inventory.stock-adjustments.index', 'permission' => 'inventory.stock.adjustment.view'],
                            ['label' => 'Stock Consumption', 'route' => 'admin.inventory.stock-consumptions.index', 'permission' => 'inventory.stock.consumption.view'],
                        ];

                        $ledgerReportRoutes = [
                            'Product Ledger' => 'admin.inventory.reports.product-ledger',
                            'Store Ledger' => 'admin.inventory.reports.store-ledger',
                            'Project Ledger' => 'admin.inventory.reports.project-ledger',
                            'Supplier Purchase History' => 'admin.inventory.reports.supplier-purchase-history',
                            'Stock Movement Report' => 'admin.inventory.reports.stock-movement',
                        ];

                        $summaryReportRoutes = [
                            'Total Stock Summary' => 'admin.inventory.reports.total-stock-summary',
                            'Office Store Summary' => 'admin.inventory.reports.office-store-summary',
                            'Project Store Summary' => 'admin.inventory.reports.project-store-summary',
                            'Product Stock Summary' => 'admin.inventory.reports.product-stock-summary',
                            'Low Stock Report' => 'admin.inventory.reports.low-stock',
                            'Out Of Stock Report' => 'admin.inventory.reports.out-of-stock',
                            'Store Stock Value Summary' => 'admin.inventory.reports.store-stock-value',
                        ];
                    @endphp

                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        @foreach ($inventoryMenuRoutes as $item)
                            @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                <a href="{{ route($item['route']) }}" class="hover:text-gray-200 cursor-pointer text-xs">
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach

                        @if (auth()->user()?->can('inventory.stock.ledger.view') || auth()->user()?->can('inventory.stock.report.view'))
                            <div class="pt-1">
                                <p class="text-[11px] uppercase tracking-wide text-gray-500">Reports</p>
                                <div class="mt-2 space-y-2">
                                    @can('inventory.stock.ledger.view')
                                        @foreach ($ledgerReportRoutes as $label => $routeName)
                                            @if (Route::has($routeName))
                                                <a href="{{ route($routeName) }}" class="hover:text-gray-200 cursor-pointer block text-xs pl-2">
                                                    {{ $label }}
                                                </a>
                                            @endif
                                        @endforeach
                                    @endcan

                                    @can('inventory.stock.report.view')
                                        @foreach ($summaryReportRoutes as $label => $routeName)
                                            @if (Route::has($routeName))
                                                <a href="{{ route($routeName) }}" class="hover:text-gray-200 cursor-pointer block text-xs pl-2">
                                                    {{ $label }}
                                                </a>
                                            @endif
                                        @endforeach
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </div>




                </div>
                <!-- User Management -->
                <div x-data="dropdown" class="relative">
                    <div @click="toggle('users')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full,
                            'text-gray-200 bg-gray-800': $store.sidebar.active == 'Reports',
                            'text-gray-400': $store.sidebar.active != 'Reports'
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            <p x-cloak class="text-xs"
                                :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                User Management
                            </p>
                        </div>

                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                            xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                            stroke-width="1.5" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.users') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Users</a>
                    </div>
                </div>



                <!-- Settings -->
                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Settings</h2>
                </div>

                <!-- Settings -->
                <!-- Role and permissions -->
                <a href="{{ route('admin.roles.list') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::currentRouteName() == 'admin.roles.list' || Route::currentRouteName() == 'admin.roles.create' || Route::currentRouteName() == 'admin.roles.edit' ? 'text-gray-200 bg-gray-800' : '' }}
                    ">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>

                    <p x-cloak class="text-xs"
                        x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                            'sm:hidden' : ''">
                        Permissions</p>

                </a>


                <!-- Ui elements -->
                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Ui Elements</h2>
                </div>

                <!-- UI Elements -->
                <div x-data="dropdown" class="relative">
                    <!-- Dropdown head -->
                    <div @click="toggle('uicomponents')" x-data="tooltip" x-on:mouseover="show = true"
                        x-on:mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                        x-bind:class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar
                                .full,
                            'text-gray-200 bg-gray-800': $store.sidebar.active == 'uielements',
                            'text-gray-400 ': $store
                                .sidebar.active != 'uielements'
                        }">
                        <div class="relative flex space-x-2 items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                            </svg>

                            <h1 x-cloak class="text-xs"
                                x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full && !show ?
                                    'sm:hidden' : ''">
                                Ui Components</h1>
                        </div>
                        <svg x-cloak x-bind:class="$store.sidebar.full ? '' : 'sm:hidden'"
                            xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                            stroke-width="1.5" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.ui.layouts') }}"
                            class="hover:text-gray-200 cursor-pointer text-xs">Layouts</a>
                    </div>

                </div>
            </div>

        </div>
        <div>

            <hr class="border-gray-700 mt-4">
            <!-- Profile / Dropup -->
            <div x-data="{ openProfile: false }" class="relative px-2 py-2">
                <div @click="openProfile = !openProfile"
                    class="flex items-center justify-between rounded-md p-2 cursor-pointer text-gray-300 hover:bg-gray-800 hover:text-white transition"
                    :class="{
                        'justify-center': !$store.sidebar.full,
                        'justify-between': $store.sidebar.full
                    }">

                    <div class="flex items-center gap-3 overflow-hidden">
                        <!-- Profile Image -->
                        <img src="{{ auth()->user()->profile_photo_path ? file_path(auth()->user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . auth()->user()->name . '&background=111827&color=ffffff&bold=true' }}"
                            alt="Profile"
                            class="w-10 h-10 rounded-full object-cover border border-gray-700 shrink-0">

                        <!-- User info -->
                        <div x-cloak x-show="$store.sidebar.full" x-transition class="min-w-0">
                            <h4 class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</h4>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <svg x-cloak x-show="$store.sidebar.full" xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': openProfile }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <!-- Dropup menu -->
                <div x-cloak x-show="openProfile" x-transition @click.outside="openProfile = false"
                    class="absolute bottom-16 left-2 right-2 bg-gray-800 border border-gray-700 rounded-lg shadow-lg overflow-hidden z-50">

                    <a href="{{ route('admin.profile') }}"
                        class="block px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                        My Profile
                    </a>

                    <a href="{{ route('admin.settings') }}"
                        class="block px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                        Account Settings
                    </a>

                    <button type="button" @click="$refs.logoutForm.submit()"
                        class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-gray-700">
                        Logout
                    </button>
                </div>
            </div>

            <!-- logout -->
            <div x-data="tooltip" @click="$refs.logoutForm.submit()" @mouseover="show = true"
                @mouseleave="show = false"
                class="relative flex justify-between items-center text-gray-400 hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer text-xs"
                :class="{
                    'justify-start': $store.sidebar.full,
                    'sm:justify-center': !$store.sidebar.full,
                    'text-gray-200 bg-gray-800': $store.sidebar.active == 'logout',
                    'text-gray-400': $store.sidebar.active != 'logout'
                }">
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>

                    <span x-cloak class="text-xs"
                        :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                        Logout
                    </span>
                </div>

                <form x-ref="logoutForm" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>


        </div>
    </div>
    <div class="min-h-screen flex-1  w-full p-6 bg-gray-100 " comment="Page Content">
        {{ $slot }}
    </div>


    <script>
        document.addEventListener('livewire:init', () => {
            // php code
            //      $this->dispatch('toast', [
            //     'type' => 'success',
            //     'message' => 'Item deleted successfully!'
            // ]);
            Livewire.on('toast', data => {
                // console.log(data);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: data[0].type,
                    title: data[0].message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                })
            })
        })
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            // Stores variable globally
            Alpine.store('sidebar', {
                full: false,
                active: 'dashboard',
                navOpen: false,
            });
            Alpine.store('pageName', {
                slug: '',
                name: '',

            });
            // Creating component Dropdown
            Alpine.data('dropdown', () => ({
                open: false,
                toggle(tab) {
                    this.open = !this.open;
                    Alpine.store('sidebar').active = tab;
                },
                activeClass: 'bg-gray-800 text-gray-200',
                expandedClass: 'border-l border-gray-400 ml-4 pl-4',
                shrinkedClass: 'sm:absolute top-0 left-20 sm:shadow-md sm:z-10 sm:bg-gray-900 sm:rounded-md sm:p-4 border-l sm:border-none border-gray-400 ml-4 pl-4 sm:ml-0 w-28'
            }));
            // Creating component Sub Dropdown
            Alpine.data('sub_dropdown', () => ({
                sub_open: false,
                sub_toggle() {
                    this.sub_open = !this.sub_open;
                },
                sub_expandedClass: 'border-l border-gray-400 ml-4 pl-4',
                sub_shrinkedClass: 'sm:absolute top-0 left-28 sm:shadow-md sm:z-10 sm:bg-gray-900 sm:rounded-md sm:p-4 border-l sm:border-none border-gray-400 ml-4 pl-4 sm:ml-0 w-28'
            }));
            // Creating tooltip
            Alpine.data('tooltip', () => ({
                show: false,
                visibleClass: 'block sm:absolute -top-7 sm:border border-gray-800 left-5 sm:text-sm sm:bg-gray-900 sm:px-2 sm:py-1 sm:rounded-md'
            }))

        })
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('livewireConfirm', () => ({
                confirmAction({
                    id = null,
                    method = null,
                    title = 'Are you sure?',
                    text = 'This action cannot be undone.',
                    confirmText = 'Yes, continue!',
                    cancelText = 'Cancel',
                    icon = 'warning',
                }) {
                    Swal.fire({
                        title,
                        text,
                        icon,
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonText: cancelText,
                        confirmButtonText: confirmText,
                        reverseButtons: true,
                        focusCancel: true,
                    }).then((result) => {
                        if (!result.isConfirmed || !method) return;

                        if (id !== null) {
                            this.$wire[method](id);
                        } else {
                            this.$wire[method]();
                        }
                    });
                }
            }));
        });
    </script>
    @livewireScripts
    @stack('scripts')
</body>

</html>
