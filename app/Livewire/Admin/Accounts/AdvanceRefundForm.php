<?php

namespace App\Livewire\Admin\Accounts;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AdvanceAdjustment;
use App\Models\PurchaseFund;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Handles returning unused advance cash back to the office.
 *
 * Flow:
 *   Employee received advance → did not spend all → returns remainder.
 *   TXN-CASH-RETURN : DR cash_account   (money back in office)
 *   TXN-ADV-REDUCE  : CR advance_account (advance balance clears)
 *   advance_adjustments row links TXN-ADV-REDUCE → original advance TXN
 */
class AdvanceRefundForm extends Component
{
    public string $fund_id       = '';
    public float  $refund_amount = 0;
    public string $cash_account_id = '';
    public string $refund_date   = '';
    public string $method        = '';
    public string $remarks       = '';

    public function mount(): void
    {
        $this->refund_date = now()->toDateString();
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $fund = PurchaseFund::query()
            ->where('id', (int) $this->fund_id)
            ->where('status', 'completed')
            ->whereNotNull('transaction_id')
            ->firstOrFail();

        $advanceTxn = Transaction::query()->findOrFail($fund->transaction_id);
        $remaining  = $advanceTxn->remainingAdvance();

        if ($this->refund_amount > round($remaining, 3)) {
            $this->addError('refund_amount', sprintf(
                'Refund amount exceeds available advance of %s.',
                number_format($remaining, 2)
            ));
            return;
        }

        try {
            DB::transaction(function (): void {
                $fund       = PurchaseFund::query()->findOrFail((int) $this->fund_id);
                $advanceTxn = Transaction::query()->findOrFail($fund->transaction_id);

                $advanceAccount = Account::query()
                    ->whereRaw('LOWER(name) = ?', ['advance'])
                    ->firstOrFail();

                $datetime = $this->refund_date . ' 00:00:00';
                $actorId  = (int) Auth::id();

                // TXN-CASH-RETURN: DR cash/bank (money comes back in)
                $txnCash = Transaction::query()->create([
                    'account_id'     => (int) $this->cash_account_id,
                    'datetime'       => $datetime,
                    'type'           => TransactionType::ADVANCE->value,
                    'reference_type' => 'purchase_fund',
                    'reference_id'   => $fund->id,
                    'debit'          => $this->refund_amount,
                    'credit'         => 0,
                    'method'         => $this->method,
                    'notes'          => $this->remarks ?: ('Advance refund – Fund #' . $fund->id),
                    'created_by'     => $actorId,
                ]);

                // TXN-ADV-REDUCE: CR advance account (advance balance reduces)
                $txnAdvReduce = Transaction::query()->create([
                    'account_id'             => $advanceAccount->id,
                    'datetime'               => $datetime,
                    'type'                   => TransactionType::ADVANCE->value,
                    'reference_type'         => 'purchase_fund',
                    'reference_id'           => $fund->id,
                    'debit'                  => 0,
                    'credit'                 => $this->refund_amount,
                    'method'                 => $this->method,
                    'notes'                  => $this->remarks ?: ('Advance refund – Fund #' . $fund->id),
                    'related_transaction_id' => $txnCash->id,
                    'relation_type'          => TransactionRelationType::REFUND->value,
                    'created_by'             => $actorId,
                ]);

                // Record in advance_adjustments so remainingAdvance() is updated
                AdvanceAdjustment::query()->create([
                    'advance_transaction_id' => $advanceTxn->id,
                    'adjust_transaction_id'  => $txnAdvReduce->id,
                    'amount'                 => $this->refund_amount,
                    'notes'                  => 'Refund – ' . ($this->remarks ?: 'Fund #' . $fund->id),
                    'created_by'             => $actorId,
                ]);
            });

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Advance refund recorded successfully.']);
            $this->reset(['fund_id', 'refund_amount', 'remarks']);
            $this->refund_date = now()->toDateString();
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render(): View
    {
        // Funds with remaining advance > 0
        $availableFunds = PurchaseFund::query()
            ->where('status', 'completed')
            ->whereNotNull('transaction_id')
            ->with([
                'purchaseOrder:id,po_no',
                'transactionCategory:id,name',
                'transaction:id,debit',
                'receiver',
            ])
            ->get()
            ->filter(fn ($fund) => $fund->transaction && $fund->transaction->remainingAdvance() > 0)
            ->map(fn ($fund) => [
                'id'        => $fund->id,
                'label'     => sprintf(
                    '#%d – %s – %s (Remaining: %s)',
                    $fund->id,
                    $fund->purchaseOrder?->po_no ?? 'No PO',
                    $fund->transactionCategory?->name ?? '—',
                    number_format($fund->transaction->remainingAdvance(), 2)
                ),
                'remaining' => $fund->transaction->remainingAdvance(),
            ]);

        $selectedFundRemaining = 0.0;
        if ($this->fund_id) {
            $fund = $availableFunds->firstWhere('id', (int) $this->fund_id);
            $selectedFundRemaining = $fund ? $fund['remaining'] : 0.0;
        }

        $cashBankAccounts = Account::query()
            ->where('is_active', true)
            ->whereIn('type', [AccountType::CASH->value, AccountType::BANK->value, AccountType::MFS->value])
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('livewire.admin.accounts.advance-refund-form', [
            'availableFunds'        => $availableFunds,
            'cashBankAccounts'      => $cashBankAccounts,
            'paymentMethods'        => EntryMethod::cases(),
            'selectedFundRemaining' => round($selectedFundRemaining, 2),
        ])->layout('layouts.admin.admin');
    }

    private function rules(): array
    {
        return [
            'fund_id'        => ['required', 'integer', 'exists:purchase_funds,id'],
            'refund_amount'  => ['required', 'numeric', 'min:0.01'],
            'cash_account_id'=> ['required', 'integer', 'exists:accounts,id'],
            'refund_date'    => ['required', 'date'],
            'method'         => ['required', 'string'],
            'remarks'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
