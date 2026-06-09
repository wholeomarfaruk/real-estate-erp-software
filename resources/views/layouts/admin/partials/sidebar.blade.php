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
        <div class="px-4 mt-4 space-y-2 ">
            {{-- <h1 class="text-white font-black py-4"
                x-bind:class="$store.sidebar.full ? 'text-2xl px-4' : 'text-xl px-4 xm:px-2'">Admin</h1> --}}
                <div class="bg-white p-2 overflow-hidden" x-bind:class="$store.sidebar.full ? 'rounded-md  max-h-auto' : 'rounded-full  max-h-15' "><img x-bind:src="$store.sidebar.full ? '{{ asset('assets/logo/sud-logo-black.png') }}' : '{{ asset('assets/logo/sud-logo.png') }}'" alt=""></div>
                {{-- <div><img src="{{ asset('assets/logo/sud-logo.png') }}" alt=""></div> --}}
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

            <div class="h-[60vh]  scrollbar-thumb-gray-900  scrollbar-thin scrollbar-track-transparent"
                :class="$store.sidebar.full ? 'overflow-y-scroll' : ''">
                @can('section.quick_actions.access')
                <!-- Quick Action -->
                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Quick Action</h2>
                </div>
                <a href="{{ route('admin.dashboard') }}" x-data="tooltip" x-on:mouseover="show = true" x-on:mouseleave="show = false"
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
                @endcan
                @if(auth()->user()?->canAny(['customer.view','crm.lead.view','crm.task.view','module.crm.access']))
                    <!-- CRM -->
                    <div class="mt-2 mb-1">
                        <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                            x-transition>CRM</h2>
                    </div>
                    <div x-data="dropdown" class="relative">
                        <div @click="toggle('crm')" x-data="tooltip" @mouseover="show = true"
                            @mouseleave="show = false"
                            class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                            :class="{
                                'justify-start': $store.sidebar.full,
                                'sm:justify-center': !$store.sidebar.full,
                                'text-gray-200 bg-gray-800': $store.sidebar.active == 'crm',
                                'text-gray-400': $store.sidebar.active != 'crm'
                            }">
                            <div class="relative flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 18.72a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <p x-cloak class="text-xs"
                                    :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                    CRM
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
                            @can('customer.view')
                                <a href="{{ route('admin.crm.customers.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.crm.customers.*') ? 'text-gray-200' : '' }}">
                                    Customers
                                </a>
                            @endcan
                            @can('crm.lead.view')
                                <a href="{{ route('admin.crm.leads.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.crm.leads.*') ? 'text-gray-200' : '' }}">
                                    Leads
                                </a>
                            @endcan
                            @can('crm.lead_source.view')
                                <a href="{{ route('admin.crm.lead-sources.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.crm.lead-sources.*') ? 'text-gray-200' : '' }}">
                                    Lead Sources
                                </a>
                            @endcan
                            @can('crm.task.view')
                                <a href="{{ route('admin.crm.tasks.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.crm.tasks.*') ? 'text-gray-200' : '' }}">
                                    Tasks
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                @if(auth()->user()?->canAny(['marketing.template.view','marketing.audience.view','marketing.campaign.view','marketing.message.view','marketing.automation.view']))
                    <!-- Marketing -->
                    <div class="mt-2 mb-1">
                        <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                            x-transition>Marketing</h2>
                    </div>
                    <div x-data="dropdown" class="relative">
                        <div @click="toggle('marketing')" x-data="tooltip" @mouseover="show = true"
                            @mouseleave="show = false"
                            class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                            :class="{
                                'justify-start': $store.sidebar.full,
                                'sm:justify-center': !$store.sidebar.full,
                                'text-gray-200 bg-gray-800': $store.sidebar.active == 'marketing',
                                'text-gray-400': $store.sidebar.active != 'marketing'
                            }">
                            <div class="relative flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                                </svg>
                                <p x-cloak class="text-xs"
                                    :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                    Marketing
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-3 ml-auto" x-cloak
                                :class="[$store.sidebar.full ? '' : 'sm:hidden', open ? 'rotate-180' : '']">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                        <div x-cloak x-show="open || $store.sidebar.active == 'marketing'"
                            :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                            @can('marketing.template.view')
                                <a href="{{ route('admin.marketing.templates.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.marketing.templates.*') ? 'text-gray-200' : '' }}">
                                    Templates
                                </a>
                            @endcan
                            @can('marketing.audience.view')
                                <a href="{{ route('admin.marketing.audiences.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.marketing.audiences.*') ? 'text-gray-200' : '' }}">
                                    Audiences
                                </a>
                            @endcan
                            @can('marketing.campaign.view')
                                <a href="{{ route('admin.marketing.campaigns.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.marketing.campaigns.*') ? 'text-gray-200' : '' }}">
                                    Campaigns
                                </a>
                            @endcan
                            @can('marketing.message.view')
                                <a href="{{ route('admin.marketing.messages.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.marketing.messages.*') ? 'text-gray-200' : '' }}">
                                    Messages
                                </a>
                            @endcan
                            @can('marketing.automation.view')
                                <a href="{{ route('admin.marketing.automations.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.marketing.automations.*') ? 'text-gray-200' : '' }}">
                                    Automations
                                </a>
                            @endcan
                        </div>
                    </div>
                @endif

                @can('property.view')
                    <!-- Real Estate -->
                    <div class="mt-2 mb-1">
                        <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                            x-transition>Real Estate</h2>
                    </div>
                    <div x-data="dropdown('realestate')" class="relative">
                        <div @click="toggle('realestate')" x-data="tooltip" @mouseover="show = true"
                            @mouseleave="show = false"
                            class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                            :class="{
                                'justify-start': $store.sidebar.full,
                                'sm:justify-center': !$store.sidebar.full,
                                'text-gray-200 bg-gray-800': $store.sidebar.active == 'realestate',
                                'text-gray-400': $store.sidebar.active != 'realestate'
                            }">
                            <div class="relative flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                </svg>
                                <p x-cloak class="text-xs"
                                    :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                    Properties
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
                            <a href="{{ route('admin.properties.index') }}"
                                class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.properties.*') && !Route::is('admin.properties.sales.*') ? 'text-gray-200' : '' }}">
                                Property Catalog
                            </a>
                            @can('property_sale.view')
                                <a href="{{ route('admin.properties.sales.index') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.properties.sales.*') && !Route::is('admin.properties.sales.create') ? 'text-gray-200' : '' }}">
                                    Property Sales
                                </a>
                            @endcan
                            @can('property_sale.create')
                                <a href="{{ route('admin.properties.sales.create') }}"
                                    class="hover:text-gray-200 block cursor-pointer text-xs {{ Route::is('admin.properties.sales.create') ? 'text-gray-200' : '' }}">
                                    + New Sale
                                </a>
                            @endcan
                        </div>
                    </div>

                @endcan
                  @can('module.projects.access')
                                   <a href="{{ route('admin.projects.list') }}" x-data="tooltip" x-on:mouseover="show = true"
                            x-on:mouseleave="show = false"
                            class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::currentRouteName() == 'admin.projects.list' ? 'text-gray-200 bg-gray-800' : '' }}

                    ">

                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>

                            <p x-cloak
                                x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                                    'sm:hidden' : ''">
                                Projects</p>
                        </a>
                  @endcan

                @can('section.general.access')
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
                    @can('module.uploads.access')
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
                    @endcan

                        <!-- uploads -->
                        <a href="{{ route('admin.engineers') }}" x-data="tooltip" x-on:mouseover="show = true"
                            x-on:mouseleave="show = false"
                            class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::currentRouteName() == 'admin.engineers' ? 'text-gray-200 bg-gray-800' : '' }}

                    ">

                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>

                            <p x-cloak
                                x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                                    'sm:hidden' : ''">
                                Site Engineers</p>
                        </a>

                    @can('module.materials.access')
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

                                <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 size-6" viewBox="0 0 20 20" stroke-width="1.5" fill="currentColor">
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
                                    class="hover:text-gray-200 cursor-pointer text-xs">Products</a>
                            </div>
                            <div x-cloak x-show="open" @click.outside="open=false"
                                :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                                <a href="{{ route('admin.materials.units') }}"
                                    class="hover:text-gray-200 cursor-pointer text-xs">Product Units</a>
                            </div>

                        </div>
                    @endcan


                    @can('module.suppliers.access')
                        <!-- Supplier Management -->
                        <div x-data="dropdown" class="relative">
                            <div @click="toggle('supplier')" x-data="tooltip" @mouseover="show = true"
                                @mouseleave="show = false"
                                class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                                :class="{
                                    'justify-start': $store.sidebar.full,
                                    'sm:justify-center': !$store.sidebar.full,
                                    'text-gray-200 bg-gray-800': $store.sidebar.active == 'supplier',
                                    'text-gray-400': $store.sidebar.active != 'supplier'
                                }">

                                <div class="relative flex items-center gap-2">

                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>

                                    <p x-cloak class="text-xs"
                                        :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                        Supplier Management
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
                                $supplierMenuRoutes = [
                                    [
                                        'label' => 'Supplier Management',
                                        'route' => 'admin.supplier.suppliers.index',
                                        'permission' => 'supplier.list.view',
                                    ],
                                ];
                            @endphp

                            <div x-cloak x-show="open" @click.outside="open=false"
                                :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                                @foreach ($supplierMenuRoutes as $item)
                                    @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                        <a href="{{ route($item['route']) }}"
                                            class="hover:text-gray-200 block cursor-pointer text-xs">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endcan
                    @can('module.hrm.access')
                        <!-- HRM Management -->
                        <div x-data="dropdown" class="relative">
                            <div @click="toggle('hrm')" x-data="tooltip" @mouseover="show = true"
                                @mouseleave="show = false"
                                class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                                :class="{
                                    'justify-start': $store.sidebar.full,
                                    'sm:justify-center': !$store.sidebar.full,
                                    'text-gray-200 bg-gray-800': $store.sidebar.active == 'hrm',
                                    'text-gray-400': $store.sidebar.active != 'hrm'
                                }">
                                <div class="relative flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 18.72a8.94 8.94 0 0 0 3.74-7.22A8.96 8.96 0 0 0 12 2.25a8.96 8.96 0 0 0-9.74 9.25A8.94 8.94 0 0 0 6 18.72m12 0a9 9 0 0 1-12 0m12 0v.53a2.25 2.25 0 0 1-2.25 2.25H8.25A2.25 2.25 0 0 1 6 19.25v-.53" />
                                    </svg>
                                    <p x-cloak class="text-xs"
                                        :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                        HRM
                                    </p>
                                </div>

                                <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                                    xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                                    stroke-width="1.5" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            @php
                                $hrmMenuRoutes = [
                                    [
                                        'label' => 'Departments',
                                        'route' => 'admin.hrm.departments.index',
                                        'permission' => 'hrm.departments.view',
                                    ],
                                    [
                                        'label' => 'Designations',
                                        'route' => 'admin.hrm.designations.index',
                                        'permission' => 'hrm.designations.view',
                                    ],
                                    [
                                        'label' => 'Employees',
                                        'route' => 'admin.hrm.employees.index',
                                        'permission' => 'hrm.employees.view',
                                    ],
                                    [
                                        'label' => 'Payrolls',
                                        'route' => 'admin.hrm.payrolls.index',
                                        'permission' => 'hrm.payrolls.view',
                                    ],
                                    [
                                        'label' => 'Employee Advances',
                                        'route' => 'admin.hrm.employee-advances.index',
                                        'permission' => 'hrm.employee-advances.view',
                                    ],
                                    [
                                        'label' => 'Payroll Payments',
                                        'route' => 'admin.hrm.payroll-payments.index',
                                        'permission' => 'hrm.payroll-payments.view',
                                    ],
                                ];
                            @endphp

                            <div x-cloak x-show="open" @click.outside="open=false"
                                :class="$store.sidebar.full ? expandedClass : shrinkedClass"
                                class="text-gray-400 space-y-3">
                                @foreach ($hrmMenuRoutes as $item)
                                    @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                        <a href="{{ route($item['route']) }}"
                                            class="hover:text-gray-200 block cursor-pointer text-xs">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endcan
                    @can('module.accounts.access')
                        <!-- Accounts Management -->
                        <div x-data="dropdown" class="relative">
                            <div @click="toggle('accounts')" x-data="tooltip" @mouseover="show = true"
                                @mouseleave="show = false"
                                class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer text-xs"
                                :class="{
                                    'justify-start': $store.sidebar.full,
                                    'sm:justify-center': !$store.sidebar.full,
                                    'text-gray-200 bg-gray-800': $store.sidebar.active == 'accounts',
                                    'text-gray-400': $store.sidebar.active != 'accounts'
                                }">
                                <div class="relative flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 18.75a60.088 60.088 0 0 1 15.549-15.549M18.75 2.25v4.5m0-4.5h-4.5m4.5 0 3 3m-3-3-3 3m-12 12h4.5m-4.5 0v-4.5m0 4.5 3-3m-3 3 3 3m8.25-2.25h4.5m-4.5 0v-4.5m0 4.5 3-3m-3 3 3 3" />
                                    </svg>

                                    <p x-cloak class="text-xs"
                                        :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                        Accounts Management
                                    </p>
                                </div>

                                <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'"
                                    xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 size-6" viewBox="0 0 20 20"
                                    stroke-width="1.5" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            @php
                                $accountsMenuRoutes = [
                                    [
                                        'label' => 'Chart of Accounts',
                                        'route' => 'admin.accounts.chart-of-accounts.index',
                                        'permission' => 'accounts.chart.list',
                                    ],
                                    [
                                        'label' => 'Banking',
                                        'route' => 'admin.accounts.banking.index',
                                        'permission' => 'accounts.chart.list',
                                    ],
                                    [
                                        'label' => 'Expenses',
                                        'route' => 'admin.accounts.expenses.index',
                                        'permission' => 'accounts.expense.list',
                                    ],
                                    [
                                        'label' => 'Banks',
                                        'route' => 'admin.accounts.banks.list',
                                        'permission' => 'accounts.chart.list',
                                    ],
                                    [
                                        'label' => 'Transactions',
                                        'route' => 'admin.accounts.transactions.index',
                                        'permission' => 'accounts.transaction.list',
                                    ],
                                    [
                                        'label' => 'Advance Refund',
                                        'route' => 'admin.accounts.advance-refund',
                                        'permission' => 'accounts.advance.refund',
                                    ],
                                    [
                                        'label' => 'TX Categories',
                                        'route' => 'admin.accounts.transaction-categories',
                                        'permission' => 'accounts.transaction.list',
                                    ],
                                ];

                                $accountsReportRoutes = [

                                    [
                                        'label' => 'Daily Statement',
                                        'route' => 'admin.accounts.reports.daily-statement',
                                        'permission' => 'accounts.report.view',
                                    ],
                                    [
                                        'label' => 'Balance Sheet',
                                        'route' => 'admin.accounts.reports.balance-sheet',
                                        'permission' => 'accounts.report.view',
                                    ]

                                ];
                            @endphp

                            <div x-cloak x-show="open" @click.outside="open=false"
                                :class="$store.sidebar.full ? expandedClass : shrinkedClass"
                                class="text-gray-400 space-y-3">
                                @foreach ($accountsMenuRoutes as $item)
                                    @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                        <a href="{{ route($item['route']) }}"
                                            class="hover:text-gray-200 block cursor-pointer text-xs">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach

                                @if (auth()->user()?->can('accounts.reports.statement.view') || auth()->user()?->can('accounts.report.view'))
                                    <div class="pt-1">
                                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Reports</p>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($accountsReportRoutes as $item)
                                                @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                                    <a href="{{ route($item['route']) }}"
                                                        class="hover:text-gray-200 block cursor-pointer pl-2 text-xs">
                                                        {{ $item['label'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endcan
                    @can('module.inventory.access')

                        <!-- inventory Management -->
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
                                    [
                                        'label' => 'Inventory Dashboard',
                                        'route' => 'admin.inventory.dashboard',
                                        'permission' => 'inventory.dashboard.view',
                                    ],
                                    [
                                        'label' => 'Product Categories',
                                        'route' => Route::has('admin.inventory.product-categories.index')
                                            ? 'admin.inventory.product-categories.index'
                                            : 'admin.materials.categories',
                                        'permission' => 'inventory.product.view',
                                    ],
                                    [
                                        'label' => 'Product Units',
                                        'route' => Route::has('admin.inventory.product-units.index')
                                            ? 'admin.inventory.product-units.index'
                                            : 'admin.materials.units',
                                        'permission' => 'inventory.product.view',
                                    ],
                                    [
                                        'label' => 'Products',
                                        'route' => Route::has('admin.inventory.products.index')
                                            ? 'admin.inventory.products.index'
                                            : 'admin.materials.products',
                                        'permission' => 'inventory.product.view',
                                    ],
                                    [
                                        'label' => 'Stores',
                                        'route' => 'admin.inventory.stores.index',
                                        'permission' => 'inventory.store.view',
                                    ],
                                    [
                                        'label' => 'Purchase Orders',
                                        'route' => 'admin.inventory.purchase-orders.index',
                                        'permission' => 'inventory.purchase_order.view',
                                    ],
                                    [
                                        'label' => 'Purchase Invoices',
                                        'route' => 'admin.inventory.purchase-invoices.index',
                                        'permission' => 'inventory.purchase_invoice.view',
                                    ],
                                    [
                                        'label' => 'Stock Receive',
                                        'route' => 'admin.inventory.stock-receives.index',
                                        'permission' => 'inventory.stock.receive.view',
                                    ],
                                    [
                                        'label' => 'Purchase Return',
                                        'route' => 'admin.inventory.purchase-returns.index',
                                        'permission' => 'inventory.purchase_return.view',
                                    ],
                                    [
                                        'label' => 'Stock Request',
                                        'route' => 'admin.inventory.stock-requests.index',
                                        'permission' => 'inventory.stock_request.view',
                                    ],
                                    [
                                        'label' => 'Stock Transfer',
                                        'route' => 'admin.inventory.stock-transfers.index',
                                        'permission' => 'inventory.stock.transfer.view',
                                    ],
                                    [
                                        'label' => 'Stock Adjustment',
                                        'route' => 'admin.inventory.stock-adjustments.index',
                                        'permission' => 'inventory.stock.adjustment.view',
                                    ],
                                    [
                                        'label' => 'Stock Consumption',
                                        'route' => 'admin.inventory.stock-consumptions.index',
                                        'permission' => 'inventory.stock.consumption.view',
                                    ],
                                    [
                                        'label' => 'Stock Request',
                                        'route' => 'admin.inventory.site_engineer.stock-requests.index',
                                        'permission' => 'inventory.site_engineer.stock_request.view',
                                    ],
                                ];

                                $ledgerReportRoutes = [
                                    'Product Ledger' => 'admin.inventory.reports.product-ledger',
                                    'Store Ledger' => 'admin.inventory.reports.store-ledger',
                                    'Project Ledger' => 'admin.inventory.reports.project-ledger',
                                    'Stock Movement Report' => 'admin.inventory.reports.stock-movement',
                                    'Supplier Purchase History' => 'admin.inventory.reports.supplier-purchase-history',
                                ];

                                $summaryReportRoutes = [
                                    'Total Stock Summary' => 'admin.inventory.reports.total-stock-summary',
                                    'Office Store Summary' => 'admin.inventory.reports.office-store-summary',
                                    'Project Store Summary' => 'admin.inventory.reports.project-store-summary',
                                    'Product Stock Summary' => 'admin.inventory.reports.product-stock-summary',
                                    'Product Wise Cost' => 'admin.inventory.reports.product-cost',
                                    'Low Stock Report' => 'admin.inventory.reports.low-stock',
                                    'Out Of Stock Report' => 'admin.inventory.reports.out-of-stock',
                                    'Store Stock Value Summary' => 'admin.inventory.reports.store-stock-value',
                                ];
                            @endphp

                            <div x-cloak x-show="open" @click.outside="open=false"
                                :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                                @foreach ($inventoryMenuRoutes as $item)
                                    @if (Route::has($item['route']) && auth()->user()?->can($item['permission']))
                                        <a href="{{ route($item['route']) }}"
                                            class="hover:text-gray-200 block cursor-pointer text-xs">
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach

                                @if (auth()->user()?->can('inventory.stock.ledger.view') || auth()->user()?->can('inventory.stock.report.view') || auth()->user()?->can('inventory.report.view'))
                                    <div class="pt-1">
                                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Reports</p>
                                        <div class="mt-2 space-y-2">
                                            @can('inventory.stock.ledger.view')
                                                @foreach ($ledgerReportRoutes as $label => $routeName)
                                                    @if (Route::has($routeName))
                                                        <a href="{{ route($routeName) }}"
                                                            class="hover:text-gray-200 cursor-pointer block text-xs pl-2">
                                                            {{ $label }}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            @endcan

                                            @if (auth()->user()?->can('inventory.stock.report.view') || auth()->user()?->can('inventory.report.view'))
                                                @foreach ($summaryReportRoutes as $label => $routeName)
                                                    @if (Route::has($routeName))
                                                        <a href="{{ route($routeName) }}"
                                                            class="hover:text-gray-200 cursor-pointer block text-xs pl-2">
                                                            {{ $label }}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>




                        </div>
                    @endcan

                    @can('module.users.access')
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
                    @endcan
                @endcan
                {{-- general section end --}}


                @can('section.settings.access')
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

                    <!-- SMS Gateway -->
                    @can('settings.sms_gateway.view')
                    <a href="{{ route('admin.settings.sms-gateway') }}" x-data="tooltip" x-on:mouseover="show = true"
                        x-on:mouseleave="show = false"
                        class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400 text-xs
                    {{ Route::is('admin.settings.sms-gateway') ? 'text-gray-200 bg-gray-800' : '' }}
                    ">

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>

                        <p x-cloak class="text-xs"
                            x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ?
                                'sm:hidden' : ''">
                            SMS Gateway</p>

                    </a>
                    @endcan
                @endcan
                @can('section.ui_components.access')
                    <!-- Ui elements -->
                    <div class="mt-4 mb-1">
                        <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                            x-transition>Ui Elements</h2>
                    </div>

                    @can('module.ui_components.access')
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
                                        x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full && !
                                            show ?
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
                    @endcan

                @endcan
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

                    @can('section.settings.access')
                        <a href="{{ route('admin.settings') }}"
                            class="block px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white
                                   {{ Route::is('admin.settings') ? 'bg-gray-700 text-white' : '' }}">
                            Account Settings
                        </a>
                    @endcan

                    <button type="button" @click="$refs.logoutForm.submit()"
                        class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-gray-700">
                        Logout
                         <form x-ref="logoutForm" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
                    </button>
                </div>
            </div>

            <!-- logout -->
            <div x-data="tooltip" @click="$refs.logoutForm.submit()" @mouseover="show = true"
                @mouseleave="show = false"
                class="relative flex justify-between items-center text-gray-400 hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer text-xs hidden"
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
