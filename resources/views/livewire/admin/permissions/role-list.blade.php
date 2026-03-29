{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Role & Permissions Manage', slug: 'role' }">
    {{-- ======================== Page Header Start From Here ======================== --}}
    <div class="flex flex-wrap justify-between gap-6 ">
        {{-- Page Name  --}}
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''">
        </h1>
        {{-- Breadcrumb  --}}
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>

                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>
    {{-- ======================== Page Header End Here ======================== --}}

    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh]">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <div class="flex items-center justify-between">
                    <div>

                        <!-- Modal -->
                        <a href="{{ route('admin.roles.create') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                            Create New Role
                        </a>


                        <!-- End Modal -->
                    </div>
                    <div>
                        <input type="text" wire:model.live="search" placeholder="Search by role or permission"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <!-- ====== Table Six Start -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="max-w-full overflow-x-auto">
                        <table class="min-w-full">
                            <!-- table header start -->
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">

                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Role
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">

                                                Permissions
                                            </p>
                                        </div>
                                    </th>

                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Action
                                            </p>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- table header end -->
                            <!-- table body start -->
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($roles as $role)
                                    <tr>

                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center">
                                                <div class="flex items-center gap-3">
                                                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                                                        {{ $role->name ?? 'No Role' }}
                                                    </p>

                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="">
                                                @foreach ($role->permissions as $permission)
                                                    <span
                                                        class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded mr-1 mb-1 dark:bg-gray-700 dark:text-gray-400">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>


                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="inline-flex">
                                                    <button wire:click="openViewModal({{ $role->id }})"
                                                        class="rounded-l-sm border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 cursor-pointer">
                                                        View
                                                    </button>
                                                    @can('role.edit')
                                                        <a href="{{ route('admin.roles.edit', $role->id) }}"
                                                            class="-ml-px border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 curson-pointer">
                                                            Edit
                                                        </a>
                                                    @endcan
                                                    @can('role.delete')
                                                        <button wire:click="deleteRole({{ $role->id }})"
                                                            class="-ml-px rounded-r-sm border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 cursor-pointer">
                                                            Delete
                                                        </button>
                                                    @endcan
                                                </div>


                                            </div>

                                        </td>
                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- ====== Table Six End -->
            </div>
        </div>

        {{-- =========================== Content End Here ============================ --}}
    </div>

    <!-- Edit Modal -->

    <div x-cloak x-data="{ open: @entangle('viewModal') }" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 max-w-md mx-auto min-h-screen" x-transition>

        <div @click.away="open = false" class="bg-white w-full max-w-md p-6 rounded-lg shadow-lg transition dark:bg-gray-700"
            x-show="open" x-transition
            role="dialog" aria-modal="true" aria-labelledby="View Modal">

            <h2 class="text-lg font-semibold mb-4">View Role</h2>

            <div>
                <p class="text-gray-800 dark:text-white/90">
                    <strong>Role:</strong>
                    {{ $view['name'] ?? 'No Role' }}
                </p>
                <p class="text-gray-800 dark:text-white/90">
                    <strong>Permissions:</strong>
                    @if (is_null($view))
                        No permissions assigned.
                    @elseif ($view)
                        {{-- @dd($view) --}}
                        @foreach ($view['permissions'] as $permission)
                            <span class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded mr-1 mb-1">
                                {{ $permission['name'] }}
                            </span>
                        @endforeach
                    @endif
                </p>

            </div>


            <!-- Modal footer -->
            <div class="mt-4 flex justify-end">
                <button wire:click="closeViewModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white">Close</button>
            </div>
        </div>
    </div>


</div>
{{-- =========================== Page Layout End Here ============================ --}}


@push('scripts')

    @if (session()->has('success'))
        <script>
            setTimeout(() => {
                $toaster.fire({
                    icon: 'success',
                    title: '{{ session('success') }}'
                });
            }, 2000);
        </script>
    @endif
    @if (session()->has('error'))
        <script>
            setTimeout(() => {

                $toaster.fire({
                    icon: 'error',
                    title: '{{ session('error') }}'
                });

            }, 2000);
        </script>
    @endif


@endpush
