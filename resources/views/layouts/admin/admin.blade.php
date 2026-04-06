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
        @include('layouts.admin.partials.sidebar')
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
