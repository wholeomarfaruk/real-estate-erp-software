<div x-data x-init="$store.pageName = { name: '{{ $report['title'] }}', slug: 'accounts-reports' }">
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ $report['meta']['company_name'] }}</p>
                <h1 class="mt-1 text-xl font-bold text-gray-900">{{ $report['title'] }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $report['meta']['period_label'] }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $excelUrl }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Export Excel
                </a>

                @if ($supportsPdfExport)
                    <a href="{{ $pdfUrl }}" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                        Export PDF
                    </a>
                @endif
            </div>
        </div>

        <form wire:submit.prevent="applyFilters" class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">From Date</label>
                    <input type="date" wire:model.defer="from_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                </div>

                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">To Date</label>
                    <input type="date" wire:model.defer="to_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                </div>

                @if ($definition['filters']['account'] ?? false)
                    <div>
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Account</label>
                        <select wire:model.defer="account_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                            <option value="">All Accounts</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ trim(($account->code ? $account->code.' - ' : '').$account->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($definition['filters']['project'] ?? false)
                    <div>
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Project</label>
                        <select wire:model.defer="project_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($definition['filters']['supplier'] ?? false)
                    <div>
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</label>
                        <select wire:model.defer="supplier_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                            <option value="">All Suppliers</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($definition['filters']['customer_name'] ?? false)
                    <div>
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Customer</label>
                        <select wire:model.defer="customer_name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                            <option value="">All Customers</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer }}">{{ $customer }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                    Filter
                </button>

                <button type="button" wire:click="resetFilters" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 {{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-gray-100 {{ $row['__row_class'] ?? '' }}">
                                @foreach ($report['columns'] as $column)
                                    <td class="px-4 py-3 text-sm text-gray-700 {{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                                        {{ $row[$column['key']] ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No report data found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if (! empty($report['footer']))
                        <tfoot class="bg-gray-50">
                            <tr>
                                @foreach ($report['columns'] as $column)
                                    <td class="border-t border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 {{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                                        {{ $report['footer'][$column['key']] ?? '' }}
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
