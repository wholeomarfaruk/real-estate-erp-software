   {{-- ======================== Page Layout Start From Here ======================== --}}
   <div x-data x-init="$store.pageName = { name: 'Manage Users', slug: 'users' }">
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
           <div class="grid grid-cols-2 gap-4 px-4 py-4 ">
               <div>
                   <label for="Search">
                       {{-- <span class="text-sm font-medium text-gray-700"> Search </span> --}}

                       <div class="relative">
                           <input type="text" wire:model.live.debounce="search"  id="Search" placeholder="Search by Name, Email"
                               class="mt-0.5 w-full rounded border-gray-300 px-2 py-2 shadow-sm sm:text-sm">

                           <span class="absolute inset-y-0 right-2 grid w-8 place-content-center">
                               <button type="button" aria-label="Submit"
                                   class="rounded-full p-1.5 text-gray-700 transition-colors hover:bg-gray-100">
                                   <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                       stroke-width="1.5" stroke="currentColor" class="size-4">
                                       <path stroke-linecap="round" stroke-linejoin="round"
                                           d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                                       </path>
                                   </svg>
                               </button>
                           </span>
                       </div>
                   </label>
               </div>
               <div>
                   <div class="flex gap-4 sm:gap-6 justify-end items-end mt-2">
                       <details class="group relative">
                           <summary
                               class="flex items-center gap-2 border-b border-gray-300 pb-1 text-gray-700 transition-colors hover:border-gray-400 cursor-pointer hover:text-gray-900 [&amp;::-webkit-details-marker]:hidden ">
                               <span class="text-sm font-medium"> Filter </span>

                               <span class="transition-transform group-open:-rotate-180">
                                   <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                       stroke-width="1.5" stroke="currentColor" class="size-4">
                                       <path stroke-linecap="round" stroke-linejoin="round"
                                           d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                                   </svg>
                               </span>
                           </summary>

                           <div
                               class="z-auto w-64 divide-y divide-gray-300 rounded border border-gray-300 bg-white shadow-sm group-open:absolute group-open:end-0 group-open:top-8">
                               <div class="flex items-center justify-between px-3 py-2">
                                   <span class="text-sm text-gray-700"> 0 Selected </span>

                                   <button type="button"
                                       class="text-sm text-gray-700 underline transition-colors hover:text-gray-900">
                                       Reset
                                   </button>
                               </div>

                               <fieldset class="">
                                   <legend class="sr-only">Checkboxes</legend>

                                   <div class="flex flex-col items-start gap-3">
                                       <label for="Option1" class="inline-flex items-center gap-3">
                                           <input type="checkbox" class="size-5 rounded border-gray-300 shadow-sm"
                                               id="Option1">

                                           <span class="text-sm font-medium text-gray-700"> Option 1 </span>
                                       </label>

                                       <label for="Option2" class="inline-flex items-center gap-3">
                                           <input type="checkbox" class="size-5 rounded border-gray-300 shadow-sm"
                                               id="Option2">

                                           <span class="text-sm font-medium text-gray-700"> Option 2 </span>
                                       </label>

                                       <label for="Option3" class="inline-flex items-center gap-3">
                                           <input type="checkbox" class="size-5 rounded border-gray-300 shadow-sm"
                                               id="Option3">

                                           <span class="text-sm font-medium text-gray-700"> Option 3 </span>
                                       </label>
                                   </div>
                               </fieldset>
                           </div>
                       </details>
                       <div class="group">
                           <button wire:click="UserModal=true" type="button"
                               class="flex items-center gap-2  pb-1 text-gray-700 transition-colors hover:border-gray-400 hover:text-gray-900 cursor-pointer rounded border border-gray-300 px-4 py-2">
                               <span class="text-sm font-medium"> Add User</span>
                       </div>
                   </div>
               </div>
           </div>

           <div class="overflow-x-auto rounded border border-gray-300 shadow-sm mx-4">
               <table class="min-w-full divide-y-2 divide-gray-200">
                   <thead class="ltr:text-left rtl:text-right">
                       <tr class="*:font-medium *:text-gray-900">
                           <th class="px-3 py-2 whitespace-nowrap">Name</th>

                           <th class="px-3 py-2 whitespace-nowrap">Role</th>
                           <th class="px-3 py-2 whitespace-nowrap">Created At</th>
                           <th class="px-3 py-2 whitespace-nowrap text-center">Action</th>
                       </tr>
                   </thead>

                   <tbody class="divide-y divide-gray-200">
                    @if ($users->count() >= 1)


                       @foreach ($users as $userItem)
                           <tr class="*:text-gray-900 *:first:font-medium">
                               <td class="px-3 py-2 whitespace-nowrap flex justify-start gap-2 items-center">
                                   <div class=" sm:shrink-0">
                                       <img alt=""
                                           src="https://images.unsplash.com/photo-1633332755192-727a05c4013d?auto=format&amp;fit=crop&amp;q=80&amp;w=1160"
                                           class="size-12! rounded-full object-cover sm:size-[52px]">
                                   </div>
                                   <div>
                                       <p class="font-bold mb-0 text-sm/2">{{ $userItem->name }}</p>
                                       <span class="text-xs text-gray-400">{{ $userItem->email }}</span>
                                   </div>
                               </td>


                               <td class="px-3 py-2 whitespace-nowrap">{{ $userItem?->roles?->first()?->name ?? 'No Role' }}
                               </td>
                               <td class="px-3 py-2 whitespace-nowrap">
                                   {{ $userItem->created_at ? $userItem->created_at->format('d-m-Y') : '' }}</td>
                               <td class="px-3 py-2 whitespace-nowrap">
                                   <div class="flex items-center justify-center gap-2">
                                       <div class="inline-flex">
                                           @can('user.view')
                                               <button wire:click="viewUser({{ $userItem->id }})"
                                                   class="rounded-l-sm border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 cursor-pointer">
                                                   View
                                               </button>
                                           @endcan
                                           {{-- @can('user.edit')
                                               <a href="{{ route('admin.roles.edit', $user->id) }}"
                                                   class="-ml-px border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 curson-pointer">
                                                   Edit
                                               </a>
                                           @endcan --}}
                                           @can('user.delete')
                                               <button
                                               x-data
    @click="
        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete user!'
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.deleteUser({{ $userItem->id }})
            }
        })
    "

                                                   class="-ml-px rounded-r-sm border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 cursor-pointer">
                                                   Delete
                                               </button>
                                           @endcan
                                       </div>
                                   </div>
                               </td>
                           </tr>
                       @endforeach
                           @else
                           <tr>
                               <td colspan="4" class="px-3 py-2 text-center">
                                   No Data Found
                               </td>
                           </tr>
                       @endif
                   </tbody>
               </table>
           </div>


           {{-- =========================== Content End Here ============================ --}}
       </div>
       <div x-cloak x-data="{ modalOpen: @entangle('viewModal') }" x-show="modalOpen" x-transition
           class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
           aria-labelledby="modalTitle">
           <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
               <div class="flex items-start justify-between">
                   <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">User Details</h2>

                   <button @click="modalOpen = false" type="button"
                       class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                       aria-label="Close">
                       <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                           stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M6 18L18 6M6 6l12 12"></path>
                       </svg>
                   </button>
               </div>

               <div class="mt-4">
                   <div class="flow-root">
                       <dl class="-my-3 divide-y divide-gray-200 text-sm">


                           <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Name</dt>

                               <dd class="text-gray-700 sm:col-span-2">{{ $user?->name ?? '' }}</dd>
                           </div>

                           <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Email</dt>

                               <dd class="text-gray-700 sm:col-span-2">{{ $user?->email ?? '' }}</dd>
                           </div>
                           <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Assigned Role</dt>

                               <dd class="text-gray-700 sm:col-span-2">
                                   <label for="role_name">
                                       <select wire:model.live="role_name" name="role_name"
                                           class="mt-0.5 w-full rounded border-gray-300 shadow-sm sm:text-sm">
                                           <option value="">Please select</option>

                                           @foreach ($roles as $item)
                                               <option value="{{ $item->name }}">
                                                   {{ $item->name }}
                                               </option>
                                           @endforeach
                                       </select>
                                   </label>
                               </dd>
                           </div>
                           <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Panels</dt>

                               <dd class="text-gray-700 sm:col-span-2">
                                   <label for="role_name">
                                       <select wire:model.live="panelId" name="role_name" @if($role_name == 'superadmin') disabled @endif
                                           class="mt-0.5 w-full rounded border-gray-300 shadow-sm sm:text-sm disabled:bg-gray-200">
                                           <option value="">Please select</option>

                                           @foreach ($panels as $panel_item)
                                               <option value="{{ $panel_item->id }}" {{ $user?->hasPanel($panel_item->name) ? 'selected' : '' }}>
                                                   {{ $panel_item->name }}
                                               </option>
                                           @endforeach
                                       </select>
                                   </label>
                               </dd>
                           </div>
                           <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Created at</dt>
                               <dd class="text-gray-700 sm:col-span-2">{{ $user?->created_at?->format('d-m-Y') ?? '' }}</dd>
                           </div>



                           {{-- <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                               <dt class="font-medium text-gray-900">Bio</dt>

                               <dd class="text-gray-700 sm:col-span-2">
                                   Lorem ipsum dolor, sit amet consectetur adipisicing elit. Et facilis debitis
                                   explicabo
                                   doloremque impedit nesciunt dolorem facere, dolor quasi veritatis quia fugit aperiam
                                   aspernatur neque molestiae labore aliquam soluta architecto?
                               </dd>
                           </div> --}}
                       </dl>
                   </div>
               </div>
           </div>
       </div>
       <div x-cloak x-data="{ addUserModalOpen: @entangle('UserModal') }" x-show="addUserModalOpen" x-transition
           class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
           aria-labelledby="modalTitle">
           <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
               <div class="flex items-start justify-between">
                   <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Add User</h2>

                   <button wire:click="UserModal=false" type="button"
                       class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                       aria-label="Close">
                       <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                           stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M6 18L18 6M6 6l12 12"></path>
                       </svg>
                   </button>
               </div>

               <div class="mt-4">

                   <form action="#" class="space-y-4" wire:submit.prevent="registerUser">
                       <div class="grid grid-cols-1 gap-1">
                           <label class="block text-sm font-medium text-gray-900" for="name">Name</label>
                           <input wire:model="newUserName"
                               class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                               id="name" type="text" placeholder="Enter Name" />
                       </div>

                       <div class="grid grid-cols-1 gap-1">
                           <label class="block text-sm font-medium text-gray-900" for="email">Email</label>
                           <input wire:model="newUserEmail"
                               class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                               id="email" type="email" placeholder="Enter Email" />
                       </div>
                       <div class="grid grid-cols-1 gap-1">
                           <label class="block text-sm font-medium text-gray-900" for="password">Password</label>
                           <input wire:model="newUserPassword"
                               class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                               id="password" type="password" placeholder="Enter Password" />
                       </div>



                       <button type="submit"
                           class="block w-full rounded-lg border border-indigo-600 bg-white px-12 py-3 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-500 hover:text-white cursor-pointer">
                           Submit
                       </button>
                   </form>

               </div>
           </div>
       </div>
   </div>
   {{-- =========================== Page Layout End Here ============================ --}}
