<div x-data x-init="$store.pageName = { name: 'Statement Sheet', slug: 'accounts-reports' }" class="print:bg-white">
    <style>
        .statement-table th,
        .statement-table td {
            white-space: nowrap;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            .no-print {
                display: none !important;
            }

            .statement-sheet-card {
                break-inside: avoid;
                box-shadow: none !important;
            }
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-4 no-print">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Statement Sheet</h1>
            <p class="text-sm text-gray-500">Daily, monthly, yearly, and custom accounts statement based on the existing ledger.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Accounts</li>
                <li>/</li>
                <li>Reports</li>
                <li>/</li>
                <li class="text-gray-700">Statement Sheet</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 space-y-4 no-print">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="applyPreset('today')"
                    class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium transition {{ $preset === 'today' ? 'bg-gray-900 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    Today
                </button>
                <button
                    type="button"
                    wire:click="applyPreset('month')"
                    class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium transition {{ $preset === 'month' ? 'bg-gray-900 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    This Month
                </button>
                <button
                    type="button"
                    wire:click="applyPreset('year')"
                    class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium transition {{ $preset === 'year' ? 'bg-gray-900 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    This Year
                </button>
                <button
                    type="button"
                    wire:click="applyPreset('custom')"
                    class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium transition {{ $preset === 'custom' ? 'bg-gray-900 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    Custom Range
                </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-2">
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">From Date</label>
                    <input type="date" wire:model.live="fromDate" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">To Date</label>
                    <input type="date" wire:model.live="toDate" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-3">
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Bank Account</label>
                    <select wire:model.live="bankAccountId" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Bank Accounts</option>
                        @foreach ($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}">{{ $bankAccount->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($supportsProjectFilter)
                    <div class="lg:col-span-2">
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Project</label>
                        <select wire:model.live="projectId" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($supportsPropertyFilter)
                    <div class="lg:col-span-2">
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Property</label>
                        <select wire:model.live="propertyId" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                            <option value="">All Properties</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="lg:col-span-3 flex items-end justify-start gap-2">
                    <button type="button" wire:click="resetFilters" class="inline-flex h-11 items-center rounded-xl border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Reset
                    </button>

                    @can('accounts.reports.statement.print')
                        <a href="{{ $printUrl }}" target="_blank" class="inline-flex h-11 items-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Print
                        </a>
                    @endcan

                    @can('accounts.reports.statement.export')
                        @if ($supportsPdfExport)
                        <a href="{{ $exportUrl }}" class="inline-flex h-11 items-center rounded-xl bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                            Download PDF
                        </a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @include('admin.accounts.reports.partials.statement-sheet', ['report' => $report])
</div>
