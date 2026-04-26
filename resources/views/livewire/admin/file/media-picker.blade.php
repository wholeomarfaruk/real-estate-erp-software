   @push('styles')
       <style>
           /* FilePond grid layout */

           .filepond--root {
               max-width: 100%;
               height: 100% !important;
               overflow: auto;
           }

           .filepond--list {
               display: grid !important;
               grid-template-columns: repeat(2, 1fr);
               gap: 12px;
               position: relative;

           }

           .filepond--item {
               width: 100% !important;
               position: relative !important;
               transform: none !important;
           }

           .filepond--image-preview {
               width: 100px;
               height: 120px;
           }
       </style>
   @endpush
   <div>
       <div x-cloak x-data="{
           mediapickerModal: @entangle('mediapickerModal'),
           tab: 'library'
       }" x-show="mediapickerModal" x-transition
           class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3">

           <div class="w-full max-w-6xl h-[90vh]  rounded-lg bg-white p-6 shadow-lg flex flex-col  ">
               <!-- Header -->
               <div class="flex items-center justify-between border-b border-gray-300 pb-2 ">
                   <h2 class="text-xl font-bold">File Manager</h2>

                   <button type="button" wire:click="mediapickerModal=false" class="p-2 rounded hover:bg-gray-100 ">
                       ✕
                   </button>
               </div>


               <!-- Tabs -->
               <div class="mt-4 flex-1 flex flex-col overflow-y-auto">


                   <div class="flex  border-b border-gray-300 justify-between items-center">
                       <div class="flex">
                           <button type="button" @click="tab='library'"
                               :class="tab == 'library' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'"
                               class="px-6 py-3 font-medium">
                               Uploaded Files
                           </button>

                           <button type="button" @click="tab='upload'"
                               :class="tab == 'upload' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'"
                               class="px-6 py-3 font-medium">
                               Upload
                           </button>
                       </div>
                       <div x-show="tab=='library'" x-transition class="flex-1 grid grid-cols-2 gap-4 px-4 ">
                           <div>
                               <label for="Search">
                                   {{-- <span class="text-sm font-medium text-gray-700"> Search </span> --}}

                                   <div class="relative">
                                       <input type="text" wire:model.live.debounce="search" id="Search"
                                           placeholder="Search by Name"
                                           class="mt-0.5 w-full rounded border border-gray-300 px-2 py-2 shadow-sm sm:text-sm">

                                       <span class="absolute inset-y-0 right-2 grid w-8 place-content-center">
                                           <button type="button" aria-label="Submit"
                                               class="rounded-full p-1.5 text-gray-700 transition-colors hover:bg-gray-100">
                                               <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                   viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                   class="size-4">
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
                               <div class=" flex gap-4 sm:gap-6 justify-end items-end mt-2">
                                   <details class="group relative">
                                       <summary
                                           class="flex items-center gap-2 border-b border-gray-300 pb-1 text-gray-700 transition-colors hover:border-gray-400 cursor-pointer hover:text-gray-900 [&amp;::-webkit-details-marker]:hidden ">
                                           <span class="text-sm font-medium"> Filter </span>

                                           <span class="transition-transform group-open:-rotate-180">
                                               <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                   viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                   class="size-4">
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
                                                       <input type="checkbox"
                                                           class="size-5 rounded border-gray-300 shadow-sm"
                                                           id="Option1">

                                                       <span class="text-sm font-medium text-gray-700"> Option
                                                           1 </span>
                                                   </label>

                                                   <label for="Option2" class="inline-flex items-center gap-3">
                                                       <input type="checkbox"
                                                           class="size-5 rounded border-gray-300 shadow-sm"
                                                           id="Option2">

                                                       <span class="text-sm font-medium text-gray-700"> Option
                                                           2 </span>
                                                   </label>

                                                   <label for="Option3" class="inline-flex items-center gap-3">
                                                       <input type="checkbox"
                                                           class="size-5 rounded border-gray-300 shadow-sm"
                                                           id="Option3">

                                                       <span class="text-sm font-medium text-gray-700"> Option
                                                           3 </span>
                                                   </label>
                                               </div>
                                           </fieldset>
                                       </div>
                                   </details>
                                   {{-- <div class="group">
                           <button  wire:click="mediapickerModal=true" type="button"
                               class="flex items-center gap-2  pb-1 text-gray-700 transition-colors hover:border-gray-400 hover:text-gray-900 cursor-pointer rounded border border-gray-300 px-4 py-2">
                               <span class="text-sm font-medium"> Add User</span>
                       </div> --}}
                               </div>
                           </div>
                       </div>
                   </div>



                   <!-- Tab Content -->
                   <div class="mt-1 flex-1 overflow-y-auto">

                       <!-- FILE LIBRARY -->
                       <div x-show="tab=='library'" x-transition class="h-full  flex-1 flex flex-col overflow-y-auto">

                           <div class="flex-1 overflow-y-auto">

                               <div class="grid grid-cols-5 gap-4 overflow-y-auto">
                                   @foreach ($files as $file)
                                       <!-- example item -->

                                       <div wire:click="selectImage({{ $file->id }})"
                                           class="block relative rounded-lg p-2 shadow-sm border border-gray-200  hover:shadow-lg transition-shadow {{ in_array($file->id, $selected) ? 'bg-indigo-400' : '' }}">
                                           @if ($type == 'video')
                                               <video class="h-36 w-full rounded-md object-contain">
                                                   <source src="{{ file_path($file->id) }}" type="video/mp4">
                                               </video>
                                           @elseif ($type == 'image')
                                               <img alt="" src="{{ file_path($file->id) }}"
                                                   class="h-36 w-full rounded-md object-contain">
                                           @elseif ($type == 'document')
                                           <span>{{ $file->extension }}</span>
                                               
                                           @endif

                                           <div class="mt-1">
                                               <dl>

                                                   <div>
                                                       <dt class="sr-only">Name:</dt>

                                                       <dd class="text-sm text-gray-500">{{ $file->name }}</dd>
                                                   </div>
                                               </dl>

                                               {{-- <div class="mt-6 flex items-center gap-8 text-xs">
                                                    <div class="sm:inline-flex sm:shrink-0 sm:items-center sm:gap-2">


                                                        <div class="mt-1.5 sm:mt-0">
                                                            <p class="text-gray-500">Quantity</p>

                                                            <p class="font-medium">
                                                                file name
                                                        </div>
                                                    </div>



                                                    <div class="sm:inline-flex sm:shrink-0 sm:items-center sm:gap-2">


                                                        <div class="mt-1.5 sm:mt-0">
                                                            <p class="text-gray-500">Per Unit</p>
                                                            <p class="font-medium">
                                                                300kg
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="sm:inline-flex sm:shrink-0 sm:items-center sm:gap-2">


                                                        <div class="mt-1.5 sm:mt-0">
                                                            <p class="text-gray-500">Price Per kg</p>

                                                            <p class="font-medium">55 Tk</p>
                                                        </div>
                                                    </div>
                                                </div> --}}
                                           </div>
                                           {{-- <div
                                               class="absolute top-5 right-5 left-5 flex items-center gap-2 justify-between">
                                               <span
                                                   class="shadow text-gray-700 text-sm bg-red-50/50 rounded px-2">#{{ $file->id }}</span>
                                               <div class="flex gap-2">
                                                   <span alt="View Product"
                                                       class="cursor-pointer rounded-md shadow-lg bg-sky-100 px-1 py-1 text-sky-700 dark:bg-sky-700 dark:text-sky-100">

                                                       <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                           viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                           class="size-4">
                                                           <path stroke-linecap="round" stroke-linejoin="round"
                                                               d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                           <path stroke-linecap="round" stroke-linejoin="round"
                                                               d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                       </svg>



                                                   </span>
                                                   <span x-data
                                                       @click="
                                                Swal.fire({
                                                    title: 'Are you sure?',
                                                    text: 'This record will be permanently deleted!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    confirmButtonText: 'Yes, delete customer!'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.delete({{ $file->id }})
                                                    }
                                                })
                                            "
                                                       alt="Delete"
                                                       class="cursor-pointer rounded-md shadow-lg bg-red-100 px-1 py-1 text-red-700 dark:bg-red-700 dark:text-red-100">
                                                       <svg xmlns="http://www.w3.org/2000/svg" class="size-4"
                                                           fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                           stroke="currentColor">
                                                           <path stroke-linecap="round" stroke-linejoin="round"
                                                               d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                       </svg>


                                                   </span>

                                                   <span wire:click="editProduct()" alt="Edit"
                                                       class="cursor-pointer rounded-md shadow-lg bg-emerald-100 px-1 py-1 text-emerald-700 dark:bg-emerald-700 dark:text-emerald-100">
                                                       <svg xmlns="http://www.w3.org/2000/svg" class="size-4"
                                                           fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                           stroke="currentColor">
                                                           <path stroke-linecap="round" stroke-linejoin="round"
                                                               d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                       </svg>

                                                   </span>
                                               </div>
                                           </div> --}}
                                       </div>
                                   @endforeach

                               </div>
                               <div>
                                   {{ $files->links() }}
                               </div>
                           </div>
                           <div
                               class="modal-footer border-t border-gray-300 mt-2 h-20 flex justify-between items-center">

                               <div class="flex-1 flex items-center gap-2 p-1">
                                   @foreach ($selected as $select_item)
                                       <div class="relative">
                                           <img src="{{ file_path($select_item) }}" alt=""
                                               class="h-18 w-18 object-cover rounded-md {{ $type == 'video' ? 'replace-video-preview' : '' }}">
                                           <button type="button"
                                               class="absolute top-1 right-1 text-red-500 text-xl font-bold shadow-lg rounded-2xl h-2 w-2"
                                               wire:click="removeSelect('{{ $select_item }}')">
                                               ×
                                           </button>
                                       </div>
                                   @endforeach
                               </div>
                               <div>
                                   <button wire:click="save()" type="button"
                                       class="px-4 py-2 bg-indigo-500 hover:bg-indigo-700 text-white rounded">Save</button>

                               </div>
                           </div>
                       </div>



                       <!-- FILE UPLOAD -->
                       <div x-show="tab=='upload'" x-transition class="h-full  flex-1 flex flex-col">

                           <div wire:ignore
                               class="flex-1 border-2 border-dashed border-gray-300 rounded-lg p-1 text-center hover:border-indigo-500 transition ">

                               <input type="file" name="filepond" multiple class="filepond" id="fileUpload">


                           </div>

                       </div>

                   </div>

               </div>

           </div>
       </div>
   </div>
   @push('scripts')
       <script>
           document.addEventListener('DOMContentLoaded', function() {
               document.querySelectorAll('.filepond').forEach(input => {

                   FilePond.create(input, {
                       allowMultiple: true,
                       allowReorder: true,
                       imagePreviewMaxHeight: 150,


                       server: {
                           process: {
                               url: '/admin/upload',
                               method: 'POST',
                               headers: {
                                   'X-CSRF-TOKEN': "{{ csrf_token() }}"
                               }
                           },


                           revert: {
                               url: '/admin/upload/revert',
                               method: 'DELETE',
                               headers: {
                                   'X-CSRF-TOKEN': "{{ csrf_token() }}"
                               }
                           }
                       },
                       // 🔥 Trigger after successful upload
                       onprocessfile: (error, file) => {
                           if (!error) {
                               // Livewire v3
                               Livewire.dispatch('fileUploaded');
                               // OR Livewire v2
                               // Livewire.emit('fileUploaded');
                           }
                       }
                   });

               });
           });
       </script>
   @endpush
