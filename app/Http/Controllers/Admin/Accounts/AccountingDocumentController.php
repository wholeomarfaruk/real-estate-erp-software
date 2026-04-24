<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountCollection;
use App\Models\Expense;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class AccountingDocumentController extends Controller
{
    public function paymentPrint(Payment $payment): View
    {
        $this->authorizePermission('accounts.payment.list');

        $payment->load($this->paymentRelations());

        return view('admin.accounts.documents.transaction-document', [
            'documentType' => 'payment',
            'documentTitle' => 'Payment Voucher',
            'document' => $payment,
            'transaction' => $payment->transaction,
            'isPdf' => false,
        ]);
    }

    public function paymentPdf(Payment $payment): Response
    {
        $this->authorizePermission('accounts.payment.list');

        $payment->load($this->paymentRelations());

        $pdf = Pdf::loadView('admin.accounts.documents.transaction-document', [
            'documentType' => 'payment',
            'documentTitle' => 'Payment Voucher',
            'document' => $payment,
            'transaction' => $payment->transaction,
            'isPdf' => true,
        ])->setPaper('a4');

        return $pdf->download('payment-voucher-'.($payment->payment_no ?: $payment->id).'.pdf');
    }

    public function collectionPrint(AccountCollection $collection): View
    {
        $this->authorizePermission('accounts.collection.list');

        $collection->load($this->collectionRelations());

        return view('admin.accounts.documents.transaction-document', [
            'documentType' => 'collection',
            'documentTitle' => 'Collection Receipt',
            'document' => $collection,
            'transaction' => $collection->transaction,
            'isPdf' => false,
        ]);
    }

    public function collectionPdf(AccountCollection $collection): Response
    {
        $this->authorizePermission('accounts.collection.list');

        $collection->load($this->collectionRelations());

        $pdf = Pdf::loadView('admin.accounts.documents.transaction-document', [
            'documentType' => 'collection',
            'documentTitle' => 'Collection Receipt',
            'document' => $collection,
            'transaction' => $collection->transaction,
            'isPdf' => true,
        ])->setPaper('a4');

        return $pdf->download('collection-receipt-'.($collection->collection_no ?: $collection->id).'.pdf');
    }

    public function expensePrint(Expense $expense): View
    {
        $this->authorizePermission('accounts.expense.list');

        $expense->load($this->expenseRelations());

        return view('admin.accounts.documents.transaction-document', [
            'documentType' => 'expense',
            'documentTitle' => 'Expense Voucher',
            'document' => $expense,
            'transaction' => $expense->transaction,
            'isPdf' => false,
        ]);
    }

    public function expensePdf(Expense $expense): Response
    {
        $this->authorizePermission('accounts.expense.list');

        $expense->load($this->expenseRelations());

        $pdf = Pdf::loadView('admin.accounts.documents.transaction-document', [
            'documentType' => 'expense',
            'documentTitle' => 'Expense Voucher',
            'document' => $expense,
            'transaction' => $expense->transaction,
            'isPdf' => true,
        ])->setPaper('a4');

        return $pdf->download('expense-voucher-'.($expense->expense_no ?: $expense->id).'.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }

    /**
     * @return array<int, string>
     */
    protected function paymentRelations(): array
    {
        return array_merge([
            'creator:id,name',
            'paymentAccount:id,name,code',
            'purposeAccount:id,name,code',
        ], $this->transactionRelations());
    }

    /**
     * @return array<int, string>
     */
    protected function collectionRelations(): array
    {
        return array_merge([
            'creator:id,name',
            'collectionAccount:id,name,code',
            'targetAccount:id,name,code',
        ], $this->transactionRelations());
    }

    /**
     * @return array<int, string>
     */
    protected function expenseRelations(): array
    {
        return array_merge([
            'creator:id,name',
            'expenseAccount:id,name,code',
            'paymentAccount:id,name,code',
        ], $this->transactionRelations());
    }

    /**
     * @return array<int, string>
     */
    protected function transactionRelations(): array
    {
        return [
            'transaction:id,date,type,reference_type,reference_id,notes,created_by',
            'transaction.lines:id,transaction_id,account_id,debit,credit,description',
            'transaction.lines.account:id,name,code',
            'transaction.attachments:id,transaction_id,file_id',
            'transaction.attachments.file:id,name,extension',
        ];
    }
}
