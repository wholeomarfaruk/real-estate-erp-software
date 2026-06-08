<?php

namespace App\Livewire\Suppliers\Show;

use App\Enums\Accounts\EntryMethod;
use App\Models\BankAccount;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\TransactionCategory;
use App\Services\Inventory\PurchaseInvoicePaymentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public Supplier $supplier;

    // Modal state
    public ?int $payInvoiceId    = null;
    public string $payAmount     = '';
    public string $payDate       = '';
    public string $payMethod     = 'bank';
    public string $payReference  = '';
    public ?int $payBankId       = null;
    public ?int $payCategoryId   = null;
    public string $payNotes      = '';

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
                    ->with(['bankAccount:id,bank_name,type', 'requestedBy:id,name', 'completedBy:id,name', 'transactionCategory:id,name'])
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

    #[Computed]
    public function bankAccounts()
    {
        return BankAccount::where('status', 'active')
            ->orderBy('type')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'type', 'ac_number']);
    }

    #[Computed]
    public function entryMethods(): array
    {
        return EntryMethod::cases();
    }

    #[Computed]
    public function expenseCategories()
    {
        $supplierBill = TransactionCategory::where('slug', 'supplier-bill')->first();
        if (! $supplierBill) {
            return collect();
        }
        return TransactionCategory::where('parent_id', $supplierBill->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function openPay(int $id): void
    {
        $this->payInvoiceId  = $id;
        $inv = PurchaseInvoice::find($id);
        $this->payAmount     = (string) ((float) ($inv?->due_amount ?? 0));
        $this->payDate       = now()->toDateString();
        $this->payMethod     = 'bank';
        $this->payReference  = '';
        $this->payNotes      = '';
        $this->payBankId     = null;
        $this->payCategoryId = null;
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
            'payAmount'      => 'required|numeric|min:0.01',
            'payDate'        => 'required|date',
            'payBankId'      => 'required|exists:bank_accounts,id',
            'payCategoryId'  => 'nullable|exists:transaction_categories,id',
            'payMethod'      => 'required|in:' . implode(',', array_column(EntryMethod::cases(), 'value')),
            'payReference'   => 'nullable|string|max:100',
            'payNotes'       => 'nullable|string|max:500',
        ]);

        $invoice = PurchaseInvoice::find($this->payInvoiceId);

        if (! $invoice) {
            $this->addError('payAmount', 'Invoice not found.');
            return;
        }

        try {
            app(PurchaseInvoicePaymentService::class)->requestPayment($invoice, [
                'amount'                  => $this->payAmount,
                'payment_date'            => $this->payDate,
                'bank_account_id'         => $this->payBankId,
                'transaction_category_id' => $this->payCategoryId ?: null,
                'method'                  => $this->payMethod,
                'reference'               => $this->payReference ?: null,
                'notes'                   => $this->payNotes ?: null,
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
