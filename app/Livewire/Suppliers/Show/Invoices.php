<?php

namespace App\Livewire\Suppliers\Show;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use App\Models\PurchaseFund;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\Inventory\PurchaseInvoicePaymentService;
use App\Livewire\Traits\WithMediaPicker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;
    use WithMediaPicker;

    public Supplier $supplier;

    // Modal state
    public ?int $payInvoiceId    = null;
    public string $payAmount     = '';
    public string $payDate       = '';

    // Payment mode: 'request' = new cash/bank request (banking approval),
    //               'advance' = settle from an existing supplier advance.
    public string $payMode  = 'request';
    public ?int   $payFundId = null;   // selected PurchaseFund (advance) id
    public string $payMethod     = 'bank';
    public string $payReference  = '';
    public string $payNotes      = '';

    // Source = chart-of-accounts money account (cash/bank/mfs/wallet), picked by type.
    public string $payAccountType = '';   // cash | bank | mfs | wallet
    public ?int   $payAccountId   = null;  // accounts.id

    // Receiver — leave name & phone blank when paying the supplier directly.
    public string $payName  = '';
    public string $payPhone = '';

    /** Uploaded attachment file ids (cheque slip / voucher photo). */
    public array $payAttachmentIds = [];

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    #[Computed]
    public function invoices()
    {
        return $this->supplier->purchaseInvoices()
            ->with(['purchaseOrder:id,po_no', 'creator:id,name'])
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function activeInvoice(): ?PurchaseInvoice
    {
        return $this->payInvoiceId
            ? PurchaseInvoice::with([
                'purchaseOrder:id,po_no',
                'creator:id,name',
                'approver:id,name',
            ])->find($this->payInvoiceId)
            : null;
    }

    /**
     * Payment history for the active invoice.
     *
     * - In-flight requests (not yet completed) come from the BankingPaymentRequest
     *   table — they are only requests, no ledger entry exists yet.
     * - Completed payments come from the transactions table (the source of truth).
     *   This also includes advance applications, which never create a request.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function paymentHistory()
    {
        $invoiceId = $this->payInvoiceId;
        if (! $invoiceId) {
            return collect();
        }

        // Pending/approved/released/rejected requests (everything not yet completed).
        $requests = BankingPaymentRequest::query()
            ->where('sourceable_type', PurchaseInvoice::class)
            ->where('sourceable_id', $invoiceId)
            ->where('status', '!=', 'completed')
            ->with(['account:id,name,code', 'requestedBy:id,name'])
            ->latest()
            ->get()
            ->map(fn (BankingPaymentRequest $r) => [
                'kind'        => 'request',
                'status'      => $r->status,
                'amount'      => (float) $r->amount,
                'account'     => $r->account?->name,
                'date'        => $r->payment_date?->format('Y-m-d') ?? $r->created_at?->format('Y-m-d'),
                'method'      => $r->method,
                'reference'   => $r->reference_no,
                'by'          => $r->requestedBy?->name,
                'ref_no'      => $r->request_no,
                'rejection'   => $r->rejection_reason,
                'notes'       => $r->notes,
                'sort'        => $r->created_at?->timestamp ?? 0,
            ]);

        // Completed payments = posted supplier_payment transactions for this invoice
        // (covers both completed banking payments and advance applications).
        $transactions = Transaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoiceId)
            ->where('type', TransactionType::SUPPLIER_PAYMENT->value)
            ->with(['lines:id,transaction_id,debit,credit,account_id', 'lines.account:id,name,code', 'creator:id,name'])
            ->latest('datetime')
            ->latest('id')
            ->get()
            ->map(function (Transaction $t) {
                // Payment amount = credit side (money out / advance reduced).
                $amount = (float) $t->lines->sum('credit');
                // The credited account is the payment source (cash/bank/advance).
                $creditLine = $t->lines->firstWhere('credit', '>', 0);

                return [
                    'kind'      => 'transaction',
                    'status'    => 'completed',
                    'amount'    => $amount,
                    'account'   => $creditLine?->account?->name,
                    'date'      => $t->datetime?->format('Y-m-d'),
                    'method'    => $t->method,
                    'reference' => $t->reference_no,
                    'by'        => $t->creator?->name,
                    'ref_no'    => 'TXN-' . str_pad((string) $t->id, 5, '0', STR_PAD_LEFT),
                    'rejection' => null,
                    'notes'     => $t->notes,
                    'sort'      => $t->datetime?->timestamp ?? 0,
                ];
            });

        return $requests->concat($transactions)
            ->sortByDesc('sort')
            ->values();
    }

    #[Computed]
    public function stats(): array
    {
        $row = $this->supplier->purchaseInvoices()
            ->selectRaw('COUNT(*) as total, SUM(total_amount) as billed, SUM(paid_amount) as paid, SUM(due_amount) as due')
            ->first();

        return [
            'total'  => $row->total ?? 0,
            'billed' => $row->billed ?? 0,
            'paid'   => $row->paid ?? 0,
            'due'    => $row->due ?? 0,
        ];
    }

    /** Active money accounts of the chosen type (chart of accounts). */
    #[Computed]
    public function moneyAccounts()
    {
        if (! in_array($this->payAccountType, ['cash', 'bank', 'mfs', 'wallet'], true)) {
            return collect();
        }

        return Account::query()
            ->where('is_active', true)
            ->where('type', $this->payAccountType)
            ->with('bankAccount:id,account_id,bank_name,ac_number')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);
    }

    public function updatedPayAccountType(): void
    {
        $this->payAccountId = null;
    }

    #[Computed]
    public function accountTypes(): array
    {
        return ['cash' => 'Cash', 'bank' => 'Bank', 'mfs' => 'MFS', 'wallet' => 'Wallet'];
    }

    #[Computed]
    public function entryMethods(): array
    {
        return EntryMethod::cases();
    }

    /**
     * Completed supplier advances (PurchaseFunds) for this supplier's POs that
     * still have a remaining, unadjusted balance — eligible to settle invoices.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int, po_no:?string, remaining:float, release_date:?string}>
     */
    #[Computed]
    public function availableAdvances()
    {
        return PurchaseFund::query()
            ->where('status', 'completed')
            ->whereNotNull('transaction_id')
            ->whereHas('purchaseOrder', fn ($q) => $q->where('supplier_id', $this->supplier->id))
            ->with(['purchaseOrder:id,po_no', 'transaction:id,type', 'transaction.lines:id,transaction_id,debit,credit'])
            ->latest('id')
            ->get()
            ->filter(fn ($fund) => $fund->transaction && $fund->transaction->remainingAdvance() > 0)
            ->map(fn ($fund) => [
                'id'           => $fund->id,
                'po_no'        => $fund->purchaseOrder?->po_no,
                'remaining'    => round((float) $fund->transaction->remainingAdvance(), 2),
                'release_date' => $fund->release_date?->format('Y-m-d'),
            ])
            ->values();
    }

    /** Remaining balance of the currently selected advance (0 if none). */
    #[Computed]
    public function selectedAdvanceRemaining(): float
    {
        if (! $this->payFundId) {
            return 0.0;
        }

        $fund = $this->availableAdvances->firstWhere('id', $this->payFundId);

        return $fund ? (float) $fund['remaining'] : 0.0;
    }

    /** Amount that would actually be applied from the advance (capped at due). */
    #[Computed]
    public function advanceApplyAmount(): float
    {
        $due = (float) ($this->activeInvoice?->due_amount ?? 0);

        return round(min($this->selectedAdvanceRemaining, $due), 2);
    }

    public function setPayMode(string $mode): void
    {
        $this->payMode = in_array($mode, ['request', 'advance'], true) ? $mode : 'request';
        $this->resetErrorBag();
    }

    public function selectAdvance(int $fundId): void
    {
        $this->payFundId = $fundId;
    }

    public function openPay(int $id): void
    {
        $inv = PurchaseInvoice::find($id);

        if (! $inv) {
            return;
        }

        // Opening a fully-paid invoice just shows its details — the payment form is
        // hidden in the view; recordPayment() also re-checks the due amount.
        $this->payInvoiceId     = $id;
        $this->payAmount        = (string) ((float) $inv->due_amount);
        $this->payDate          = now()->toDateString();
        $this->payMethod        = 'bank';
        $this->payReference     = '';
        $this->payNotes         = '';
        $this->payAccountType   = '';
        $this->payAccountId     = null;
        $this->payName          = '';
        $this->payPhone         = '';
        $this->payAttachmentIds = [];
        $this->payMode          = 'request';
        $this->payFundId        = null;
        unset($this->activeInvoice, $this->availableAdvances, $this->paymentHistory);
        $this->dispatch('pay-modal-open');
    }

    public function closePay(): void
    {
        $this->payInvoiceId = null;
        $this->payMode      = 'request';
        $this->payFundId    = null;
        unset($this->activeInvoice, $this->availableAdvances, $this->paymentHistory);
        $this->dispatch('pay-modal-close');
    }

    public function fillFull(): void
    {
        $inv = PurchaseInvoice::find($this->payInvoiceId);
        $this->payAmount = (string) ((float) ($inv?->due_amount ?? 0));
    }

    public function fillHalf(): void
    {
        $inv = PurchaseInvoice::find($this->payInvoiceId);
        $this->payAmount = (string) round((float) ($inv?->due_amount ?? 0) / 2, 2);
    }

    public function recordPayment(): void
    {
        $this->validate([
            'payAmount'       => 'required|numeric|min:0.01',
            'payDate'         => 'required|date',
            'payAccountType'  => 'required|in:cash,bank,mfs,wallet',
            'payAccountId'    => [
                'required', 'integer',
                Rule::exists('accounts', 'id')->where('is_active', true)->where('type', $this->payAccountType),
            ],
            'payMethod'       => 'required|in:' . implode(',', array_column(EntryMethod::cases(), 'value')),
            'payName'         => 'nullable|string|max:150',
            'payPhone'        => 'nullable|string|max:30',
            'payReference'    => 'nullable|string|max:100',
            'payNotes'        => 'nullable|string|max:500',
        ], [
            'payAccountType.required' => 'Select the account type.',
            'payAccountId.required'   => 'Select the source account.',
        ]);

        $invoice = PurchaseInvoice::find($this->payInvoiceId);

        if (! $invoice) {
            $this->addError('payAmount', 'Invoice not found.');
            return;
        }

        if ((float) $invoice->due_amount <= 0) {
            $this->addError('payAmount', 'This invoice is already fully paid.');
            return;
        }

        try {
            app(PurchaseInvoicePaymentService::class)->requestPayment($invoice, [
                'amount'             => $this->payAmount,
                'payment_date'       => $this->payDate,
                'payment_account_id' => $this->payAccountId,
                'method'             => $this->payMethod,
                // Blank name & phone ⇒ paid directly to the supplier.
                'name'               => trim($this->payName) ?: null,
                'phone'              => trim($this->payPhone) ?: null,
                'reference'          => $this->payReference ?: null,
                'notes'              => $this->payNotes ?: null,
                'attachment_ids'     => $this->payAttachmentIds,
            ], (int) Auth::id());

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment request created. Awaiting banking approval.']);
            $this->closePay();
            unset($this->invoices, $this->stats);

        } catch (\DomainException $e) {
            $this->addError('payAmount', $e->getMessage());
        }
    }

    /**
     * Settle the invoice from a selected supplier advance (one advance, applied
     * in full up to the due amount). Posts immediately — no banking approval.
     */
    public function applyAdvancePayment(): void
    {
        if (! $this->payFundId) {
            $this->addError('payFundId', 'Select an advance to apply.');
            return;
        }

        $invoice = PurchaseInvoice::find($this->payInvoiceId);

        if (! $invoice) {
            $this->addError('payFundId', 'Invoice not found.');
            return;
        }

        if ((float) $invoice->due_amount <= 0) {
            $this->addError('payFundId', 'This invoice is already fully paid.');
            return;
        }

        try {
            app(PurchaseInvoicePaymentService::class)
                ->applyAdvance($invoice, (int) $this->payFundId, (int) Auth::id());

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Advance applied to the invoice.']);
            $this->closePay();
            unset($this->invoices, $this->stats);

        } catch (\DomainException $e) {
            $this->addError('payFundId', $e->getMessage());
        }
    }

    public function downloadPdf(int $id, string $ref): void
    {
        $this->dispatch('toast', ['type' => 'info', 'message' => "Preparing {$ref}.pdf…"]);
    }

    public function render()
    {
        return view('livewire.suppliers.show.invoices')->layout('layouts.admin.admin');
    }
}
