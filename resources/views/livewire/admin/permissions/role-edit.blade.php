   {{-- ======================== Page Layout Start From Here ======================== --}}
   <div x-data x-init="$store.pageName = { name: 'Update Role & Permissions', slug: 'role-edit' }">
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
                               fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                               class="size-6">
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
           <div
               class="min-h-screen rounded-2xl border border-gray-200 bg-white px-5 py-7 xl:px-10 xl:py-12 dark:border-gray-800 dark:bg-white/[0.03]">

               <div>

                   <form wire:submit.prevent="save" method="POST">
                       @csrf

                       <div class="flex gap-4 mt-4 items-center">
                           <div class="w-8/12">

                               <input wire:model="name" name="name" type="name" required placeholder="Enter Role Name"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                               @error('name')
                                   <span class="text-red-500 text-sm">{{ $message }}</span>
                               @enderror
                           </div>
                           <div class="w-4/12">
                               <button type="submit"
                                   class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                   Update Role
                               </button>
                           </div>
                       </div>

                       <div class="mb-4 mt-2">
                           <label>Permissions</label>
                           <br>
                           <label class="inline-flex items-center">
                               <input type="checkbox" wire:model.live="selectAll" x-data
                                   x-on:change="
                                document.querySelectorAll('input[type=\'checkbox\'][name=\'permissions[]\']').forEach(cb => cb.checked = $event.target.checked)
                            "
                                   class="form-checkbox h-5 w-5 text-green-600 transition duration-150 ease-in-out">
                               <span class="ml-2 text-gray-700 dark:text-gray-300">Select All</span>
                           </label>
                       </div>
                       <div class="grid grid-cols-2 sm:grid-cols-4  gap-2">
                       @foreach ($permissionsGrouped as $module => $permissions)
                       <div class="">
                           <h2 class="text-lg font-bold">{{ ucfirst($module) }}</h2>
                           @foreach ($permissions as $permission)
                               <div class="mb-4">
                                   <label class="inline-flex items-center">
                                       <input type="checkbox" wire:model="permissions" name="permissions[]" value="{{ $permission->name }}"
                                           class="form-checkbox h-5 w-5 text-blue-600 transition duration-150 ease-in-out" />

                                       <span
                                           class="ml-2 text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                   </label>
                               </div>
                           @endforeach
                           </div>
                       @endforeach
</div>
                   </form>

               </div>
           </div>
           {{-- =========================== Content End Here ============================ --}}
       </div>
   </div>
   {{-- =========================== Page Layout End Here ============================ --}}
