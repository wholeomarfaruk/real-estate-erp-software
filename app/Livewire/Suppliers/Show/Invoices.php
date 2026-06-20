<?php

namespace App\Livewire\Suppliers\Show;

use App\Enums\Accounts\EntryMethod;
use App\Models\Account;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
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
                'bankingPaymentRequests' => fn ($q) => $q
                    ->with(['bankAccount:id,bank_name,type', 'account:id,name,code', 'requestedBy:id,name', 'completedBy:id,name'])
                    ->latest(),
            ])->find($this->payInvoiceId)
            : null;
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
        unset($this->activeInvoice);
        $this->dispatch('pay-modal-open');
    }

    public function closePay(): void
    {
        $this->payInvoiceId = null;
        unset($this->activeInvoice);
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

    public function downloadPdf(int $id, string $ref): void
    {
        $this->dispatch('toast', ['type' => 'info', 'message' => "Preparing {$ref}.pdf…"]);
    }

    public function render()
    {
        return view('livewire.suppliers.show.invoices')->layout('layouts.admin.admin');
    }
}
