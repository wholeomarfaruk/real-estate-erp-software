<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supplier ▸ Invoices tab (module 2 of 4).
 * Holds the purchase-invoice table + the payment modal (record payment when a
 * balance is due, view payment history when settled).
 */
class Invoices extends Component
{
    use WithPagination;

    public Supplier $supplier;

    /* payment form (bound while the modal is open) */
    public ?int $payInvoiceId = null;
    public string $payAmount = '';
    public string $payDate = '';
    public string $payMethod = 'Bank transfer';
    public string $payRef = '';
    public string $payNote = '';

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->payDate  = now()->toDateString();
    }

    /**
     * Demo invoices so the UI renders before the purchase module exists.
     * Swap for: $this->supplier->purchaseInvoices()->withPayments()->latest()->paginate(15)
     */
    #[Computed]
    public function invoices()
    {
        return collect([
            ['id'=>118,'no'=>'INV-2026-0118','date'=>'2026-06-01','po'=>'PO-2026-0061','amt'=>842000,'paid'=>0,'due'=>842000,'status'=>'unpaid','payments'=>[]],
            ['id'=>114,'no'=>'INV-2026-0114','date'=>'2026-05-22','po'=>'PO-2026-0059','amt'=>1180000,'paid'=>1180000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0214','date'=>'2026-05-24','method'=>'Bank transfer','detail'=>'City Bank · TXN 88421','amt'=>1180000,'by'=>'Tanvir Ahmed']]],
            ['id'=>109,'no'=>'INV-2026-0109','date'=>'2026-05-08','po'=>'PO-2026-0057','amt'=>640000,'paid'=>544000,'due'=>96000,'status'=>'partial','payments'=>[['ref'=>'PMT-2026-0207','date'=>'2026-05-10','method'=>'Bank transfer','detail'=>'City Bank · TXN 87330','amt'=>400000,'by'=>'Tanvir Ahmed'],['ref'=>'PMT-2026-0211','date'=>'2026-05-19','method'=>'Cheque','detail'=>'Cheque #553120','amt'=>144000,'by'=>'Nasir Uddin']]],
            ['id'=>103,'no'=>'INV-2026-0103','date'=>'2026-04-26','po'=>'PO-2026-0054','amt'=>1320000,'paid'=>1320000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0198','date'=>'2026-04-28','method'=>'Bank transfer','detail'=>'City Bank · TXN 86012','amt'=>1320000,'by'=>'Tanvir Ahmed']]],
            ['id'=>98,'no'=>'INV-2026-0098','date'=>'2026-04-12','po'=>'PO-2026-0051','amt'=>910000,'paid'=>910000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0189','date'=>'2026-04-15','method'=>'Bank transfer','detail'=>'City Bank · TXN 85110','amt'=>910000,'by'=>'Tanvir Ahmed']]],
            ['id'=>91,'no'=>'INV-2026-0091','date'=>'2026-03-30','po'=>'PO-2026-0048','amt'=>1560000,'paid'=>1560000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0181','date'=>'2026-04-02','method'=>'Bank transfer','detail'=>'City Bank · TXN 84220','amt'=>1560000,'by'=>'Nasir Uddin']]],
            ['id'=>86,'no'=>'INV-2026-0086','date'=>'2026-03-15','po'=>'PO-2026-0045','amt'=>720000,'paid'=>720000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0174','date'=>'2026-03-18','method'=>'Cash','detail'=>'Cash voucher CV-0094','amt'=>720000,'by'=>'Tanvir Ahmed']]],
            ['id'=>79,'no'=>'INV-2026-0079','date'=>'2026-02-28','po'=>'PO-2026-0042','amt'=>1040000,'paid'=>1040000,'due'=>0,'status'=>'paid','payments'=>[['ref'=>'PMT-2026-0166','date'=>'2026-03-03','method'=>'Bank transfer','detail'=>'City Bank · TXN 83001','amt'=>1040000,'by'=>'Tanvir Ahmed']]],
        ]);
    }

    /** The invoice currently shown in the modal (or null). */
    #[Computed]
    public function activeInvoice()
    {
        return $this->payInvoiceId
            ? $this->invoices->firstWhere('id', $this->payInvoiceId)
            : null;
    }

    public function openPay(int $id): void
    {
        $this->payInvoiceId = $id;
        $inv = $this->invoices->firstWhere('id', $id);
        $this->payAmount = (string) ($inv['due'] ?? '');
        $this->payDate   = now()->toDateString();
        $this->payRef = $this->payNote = '';
        $this->payMethod = 'Bank transfer';
        $this->dispatch('pay-modal-open');
    }

    public function closePay(): void
    {
        $this->payInvoiceId = null;
        $this->dispatch('pay-modal-close');
    }

    /**
     * Record a payment. STUB — wire to your ledger:
     *   PurchasePayment::create([...]); recompute invoice paid/due/status.
     * Button stays regardless of backend readiness.
     */
    public function recordPayment(): void
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:1',
            'payDate'   => 'required|date',
        ]);

        // TODO: persist against $this->payInvoiceId
        $this->dispatch('toast', message: "Payment of ৳ {$this->payAmount} recorded.");
        $this->closePay();
    }

    public function downloadPdf(string $ref)
    {
        // TODO: return $this->supplier invoice PDF stream. Button stays meanwhile.
        $this->dispatch('toast', message: "Preparing {$ref}.pdf…");
    }

    public function render()
    {
        return view('livewire.suppliers.show.invoices');
    }
}
