@php
    $input = 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3! text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15';
    $type  = $this->transactionType();
    $side  = $this->side();
@endphp

<div class="min-h-screen bg-slate-50 p-4 sm:p-6"
    x-data x-init="$store.pageName = { name: 'Journal Posting', slug: 'accounts' }">
<div class="mx-auto max-w-3xl">
    {{-- Header --}}
    <div class="mb-5">
        <nav class="mb-1 text-xs text-gray-400">
            <a href="{{ $this->backUrl() }}" class="hover:text-gray-600">{{ auth()->user()?->can('accounts.chart.list') ? 'Journal Posting' : 'Expenses' }}</a>
            <span class="mx-1">/</span>
            <span class="text-gray-600">{{ $item->label }}</span>
        </nav>
        <h1 class="text-xl font-bold text-gray-900">{{ $item->label }}</h1>
        @if ($item->description)
            <p class="text-sm text-gray-500">{{ $item->description }}</p>
        @endif
        <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-medium">
            <span class="rounded-md border px-2 py-0.5 {{ $type->badgeClass() }}">{{ $type->label() }}</span>
            <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-emerald-700">Dr: {{ $debitSourceLabel }}</span>
            <span class="rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-rose-700">Cr: {{ $creditSourceLabel }}</span>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        {{-- Double entry: Debit + Credit --}}
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([
                ['debit_account_id',  'Debit account',  'Dr', $labels['debit'],  $debitAccounts,  $debitSourceLabel,  'border-emerald-400', 'bg-emerald-50 text-emerald-700'],
                ['credit_account_id', 'Credit account', 'Cr', $labels['credit'], $creditAccounts, $creditSourceLabel, 'border-rose-400',    'bg-rose-50 text-rose-700'],
            ] as [$field, $title, $abbr, $hint, $options, $sourceLabel, $bar, $chip])
                <div class="rounded-lg border-l-4 {{ $bar }} bg-white pl-3">
                    <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-700">
                        <span class="rounded px-1.5 py-px text-[10px] font-bold {{ $chip }}">{{ $abbr }}</span>
                        {{ $title }} <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400">— {{ $hint }}</span>
                    </label>
                    <select wire:model.live="{{ $field }}" class="{{ $input }}">
                        <option value="">Select account</option>
                        @foreach ($options as $account)
                            <option value="{{ $account->id }}">{{ $account->code ? $account->code . ' — ' : '' }}{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">From: {{ $sourceLabel }}</p>
                    @error($field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        @php
            $drName = $debitAccounts->firstWhere('id', $debit_account_id);
            $crName = $creditAccounts->firstWhere('id', $credit_account_id);
        @endphp
        <p class="rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-gray-500">
            Posting:
            <b class="text-emerald-700">Dr</b> {{ $drName ? trim($drName->code . ' ' . $drName->name) : 'debit account' }}
            · <b class="text-rose-700">Cr</b> {{ $crName ? trim($crName->code . ' ' . $crName->name) : 'credit account' }}
            {{ $amount !== '' ? '· ' . number_format((float) $amount, 2) : '' }}
            — {{ $item->setting('requires_approval') ? 'posted to the ledger after approval in Payment Requests.' : 'posted to the ledger immediately (no approval step).' }}
            @if ($item->autoVoucher())
                <span class="ml-1 rounded bg-sky-50 px-1.5 py-px font-mono text-sky-700">Next voucher: {{ $item->previewVoucher() }}</span>
            @endif
        </p>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" wire:model="amount" placeholder="0.00" class="{{ $input }}">
                @php $min = $item->setting('min_amount'); $max = $item->setting('max_amount'); @endphp
                @if ($min !== null || $max !== null)
                    <p class="mt-1 text-[11px] text-gray-400">
                        {{ $min !== null ? 'Min ' . number_format((float) $min, 2) : '' }}{{ $min !== null && $max !== null ? ' · ' : '' }}{{ $max !== null ? 'Max ' . number_format((float) $max, 2) : '' }}
                    </p>
                @endif
                @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" wire:model="date" class="{{ $input }}">
                @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Method <span class="text-red-500">*</span></label>
                <select wire:model="payment_method" class="{{ $input }} disabled:bg-gray-50 disabled:text-gray-500" @disabled($item->setting('lock_method'))>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
            <input type="text" wire:model="title" maxlength="200" class="{{ $input }}">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        @php
            $star = fn ($key) => $item->requiresField($key) ? ' <span class="text-red-500">*</span>' : '';
            $grid = collect(['reference_no', 'voucher_no', 'party_name', 'party_phone'])->filter(fn ($k) => $item->showsField($k));
        @endphp

        @if ($item->showsField('profile'))
            @include('livewire.admin.profiles.partials.profile-picker', [
                'label'    => $labels['party'] . ' (profile)',
                'required' => $item->requiresField('profile'),
            ])
        @endif

        @if ($item->showsField('project'))
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">
                        Project{!! $star('project') !!}
                        @unless ($item->requiresField('project')) <span class="font-normal text-gray-400">(optional)</span> @endunless
                    </label>
                    <x-searchable-select field="project_id" :value="$project_id"
                        :options="$projectOptions"
                        placeholder="— Select project —"
                        search-placeholder="Search project…" />
                    @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Work phase <span class="font-normal text-gray-400">(optional)</span></label>
                    <select wire:model="project_work_phase" class="{{ $input }} disabled:bg-gray-50 disabled:text-gray-400" @disabled(! $project_id)>
                        <option value="">— Others —</option>
                        @foreach ($workPhases as $phase)
                            <option value="{{ $phase['value'] }}">{{ $phase['label'] }}</option>
                        @endforeach
                    </select>
                    @error('project_work_phase') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        @if ($grid->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($item->showsField('reference_no'))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Reference No{!! $star('reference_no') !!}</label>
                        <input type="text" wire:model="reference_no" maxlength="100" placeholder="e.g. INV-2026-001" class="{{ $input }}">
                        @error('reference_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($item->showsField('voucher_no'))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Voucher No{!! $star('voucher_no') !!}</label>
                        <input type="text" wire:model="voucher_no" maxlength="100" placeholder="e.g. VCH-2026-001" class="{{ $input }}">
                        @error('voucher_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($item->showsField('party_name'))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ $labels['party'] }}{!! $star('party_name') !!}</label>
                        <input type="text" wire:model="party_name" maxlength="200" placeholder="Name" class="{{ $input }}">
                        @error('party_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($item->showsField('party_phone'))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Phone{!! $star('party_phone') !!}</label>
                        <input type="tel" wire:model="party_phone" maxlength="20" placeholder="01XXXXXXXXX" class="{{ $input }}">
                        @error('party_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        @endif

        @if ($item->showsField('notes'))
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Notes{!! $star('notes') !!}</label>
                <textarea wire:model="notes" rows="3" maxlength="1000" placeholder="Additional details" class="w-full rounded-lg border border-gray-300 bg-white px-3! py-2! text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Custom fields defined on the item --}}
        @if ($item->customFieldDefs())
            <div class="rounded-lg border border-violet-100 bg-violet-50/30 p-4">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-violet-700">Additional details</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($item->customFieldDefs() as $field)
                        <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}" wire:key="cf-{{ $field['key'] }}">
                            <label class="mb-1 block text-xs font-medium text-gray-700">{{ $field['label'] }} @if (! empty($field['required']))<span class="text-red-500">*</span>@endif</label>
                            @switch($field['type'])
                                @case('select')
                                    <select wire:model="custom.{{ $field['key'] }}" class="{{ $input }}">
                                        <option value="">—</option>
                                        @foreach (array_filter(array_map('trim', explode(',', $field['options'] ?? ''))) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    @break
                                @case('textarea')
                                    <textarea wire:model="custom.{{ $field['key'] }}" rows="2" class="w-full rounded-lg border border-gray-300 bg-white px-3! py-2! text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15"></textarea>
                                    @break
                                @default
                                    <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}" @if ($field['type'] === 'number') step="any" @endif
                                        wire:model="custom.{{ $field['key'] }}" class="{{ $input }}">
                            @endswitch
                            @error('custom.' . $field['key']) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Attachments (receipts, bills, vouchers) --}}
        <div>
            <x-media-picker-field field="mediaIds" label="Attachments" :value="$mediaIds"
                placeholder="Attach receipts / bills" :multiple="true" type="any" />
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
            <a href="{{ $this->backUrl() }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ $item->setting('requires_approval') ? 'Submit for Approval' : 'Post Entry' }}</span>
                <span wire:loading wire:target="save">Submitting…</span>
            </button>
        </div>
    </form>
</div>
</div>
