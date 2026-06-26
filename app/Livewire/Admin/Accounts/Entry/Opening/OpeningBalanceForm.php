<?php

namespace App\Livewire\Admin\Accounts\Entry\Opening;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Services\Accounts\Entry\ConfigBasedEntryRegistry;
use App\Services\Accounts\Entry\EntrySubmissionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OpeningBalanceForm extends Component
{
    use InteractsWithAccountsAccess, WithMediaPicker;

    public string $date = '';
    public array $lines = [['account_id' => null, 'debit' => 0, 'credit' => 0]];
    public string $notes = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => null, 'debit' => 0, 'credit' => 0];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(): void
    {
        try {
            $this->authorizePermission('accounts.entry.opening.create');

            $rules = [
                'date' => 'required|date',
                'lines' => 'required|array|min:1',
                'lines.*.account_id' => 'required|integer|exists:accounts,id',
                'lines.*.debit' => 'required|numeric|gte:0',
                'lines.*.credit' => 'required|numeric|gte:0',
                'notes' => 'nullable|string|max:1000',
            ];

            $this->validate($rules);

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($this->lines as $line) {
                $totalDebit += (float) $line['debit'];
                $totalCredit += (float) $line['credit'];
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $this->dispatch('toast', type: 'error', message: 'Total debits must equal total credits. Debits: ' . number_format($totalDebit, 2) . ', Credits: ' . number_format($totalCredit, 2));
                return;
            }

            $registry = app(ConfigBasedEntryRegistry::class);
            $def = $registry->find('opening-balance');

            $payload = [
                'lines' => $this->lines,
                'date' => $this->date,
                'method' => 'journal',
                'notes' => $this->notes,
                'description' => 'Opening Balance',
            ];

            app(EntrySubmissionService::class)->submit($def, $payload);

            $this->dispatch('toast', type: 'success', message: 'Opening balance entry submitted successfully.');
            $this->redirectRoute('admin.account-entries.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render(): View
    {
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return view('livewire.admin.accounts.entry.opening.opening-balance-form', [
            'accounts' => $accounts,
        ]);
    }
}
