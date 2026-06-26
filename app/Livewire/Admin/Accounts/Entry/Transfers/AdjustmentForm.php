<?php
namespace App\Livewire\Admin\Accounts\Entry\Transfers;
use App\Livewire\Admin\Accounts\Entry\BaseEntryForm;
use App\Models\Account;
use Illuminate\Contracts\View\View;
class AdjustmentForm extends BaseEntryForm {
    public function mount(): void { $this->date = now()->toDateString(); $this->method = 'journal'; }
    protected function entrySlug(): string { return 'adjustment'; }
    protected function extraRules(): array { return []; }
    protected function buildPayload(): array {
        return ['debit_account_id' => $this->debit_account_id, 'credit_account_id' => $this->credit_account_id, 'amount' => $this->amount, 'date' => $this->date, 'method' => 'journal', 'reference_no' => $this->reference_no, 'notes' => $this->notes, 'description' => 'Adjustment'];
    }
    public function render(): View {
        $accounts = Account::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);
        return view('livewire.admin.accounts.entry.transfers.adjustment-form', ['accounts' => $accounts]);
    }
}
