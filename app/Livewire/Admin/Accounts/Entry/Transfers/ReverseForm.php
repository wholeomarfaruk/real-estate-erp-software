<?php
namespace App\Livewire\Admin\Accounts\Entry\Transfers;
use App\Livewire\Admin\Accounts\Entry\BaseEntryForm;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
class ReverseForm extends BaseEntryForm {
    public ?int $transaction_id = null;
    public ?Transaction $selectedTransaction = null;

    public function mount(): void { $this->date = now()->toDateString(); $this->method = 'journal'; }
    protected function entrySlug(): string { return 'reverse'; }
    protected function extraRules(): array { return ['transaction_id' => 'required|integer|exists:transactions,id']; }
    protected function buildPayload(): array {
        if (!$this->selectedTransaction) { $this->selectedTransaction = Transaction::find($this->transaction_id); }
        $lines = []; foreach ($this->selectedTransaction->transactionLines as $line) { $lines[] = ['account_id' => $line->account_id, 'debit' => $line->credit, 'credit' => $line->debit, 'notes' => 'Reversal of TXN-' . $this->selectedTransaction->id]; }
        return ['lines' => $lines, 'date' => $this->date, 'method' => 'journal', 'reference_no' => 'REV-' . $this->transaction_id, 'notes' => $this->notes, 'description' => 'Reverse Entry'];
    }
    public function render(): View {
        $transactions = Transaction::orderBy('created_at', 'desc')->limit(50)->get(['id', 'date', 'reference_no', 'type']);
        return view('livewire.admin.accounts.entry.transfers.reverse-form', ['transactions' => $transactions, 'selectedTransaction' => $this->selectedTransaction]);
    }
}
