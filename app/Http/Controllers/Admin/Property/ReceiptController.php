<?php

namespace App\Http\Controllers\Admin\Property;

use App\Http\Controllers\Controller;
use App\Models\Transaction;

class ReceiptController extends Controller
{
    public function show(Transaction $transaction)
    {
        $transaction->load([
            'reference.propertySale.customer',
            'reference.propertySale.property',
            'reference.propertySale.propertyUnit',
            'account.bankAccount',
        ]);

        $schedule = $transaction->reference;      // PaymentSchedule
        $sale     = $schedule->propertySale;      // PropertySale
        $customer = $sale->customer;
        $property = $sale->property;              // Property (project)
        $unit     = $sale->propertyUnit;          // PropertyUnit (specific unit)

        // Resolve bank details from the transaction's linked account
        $bankAccount  = $transaction->account?->bankAccount;
        $isBankMethod = in_array($transaction->method, ['bank_transfer', 'cheque']);
        $receivedVia  = match($transaction->method) {
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'cash'          => 'Cash',
            default         => ucwords(str_replace('_', ' ', $transaction->method ?? 'Cash')),
        };

        // All transactions against this exact schedule entry
        $installmentHistory = Transaction::query()
            ->where('reference_type', get_class($schedule))
            ->where('reference_id', $schedule->id)
            ->orderBy('datetime')
            ->get()
            ->map(fn($t) => [
                'date'    => $t->datetime->format('d M Y'),
                'method'  => $t->method ?? '—',
                'amount'  => (float) $t->debit,
                'current' => $t->id === $transaction->id,
            ]);

        $paidTotal     = $installmentHistory->sum('amount');
        $due           = max(0, (float) $schedule->amount - $paidTotal);
        $scheduleLabel = $schedule->label();

        return view('pdf.property-sale.transaction', [
            'receipt' => [
                'no'                => 'REC-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                'txn_id'            => 'TXN-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                'issue_date'        => $transaction->created_at->format('d M Y'),
                'reference_code'    => $sale->sale_number,
                'txn_date'          => $transaction->datetime->format('d M Y'),
                'payment_method'    => $transaction->method ?? '—',
                'reference_no'      => $transaction->reference_no,
                'amount'            => (float) $transaction->debit,
                'status'            => 'Received',
                'narration'         => $transaction->notes ?? sprintf(
                    'Payment received against %s for Unit %s — %s.',
                    $scheduleLabel,
                    $unit->code ?? $unit->unit_number ?? '—',
                    $property->name
                ),
                'amount_words'      => $this->amountInWords((float) $transaction->debit),
                'attachments_count' => count($transaction->attachments ?? []),
                'generated_at'      => now()->format('d M Y, H:i'),
            ],

            'company' => [
                'name'         => config('company.name', 'Star Unity Development Ltd.'),
                'tag'          => config('company.tagline'),
                'address'      => config('company.address'),
                'phone'        => config('company.phone'),
                'email'        => config('company.email'),
                'website'      => config('company.website'),
                'logo'         => config('company.logo'),
                'logo_initial' => config('company.logo_initial', 'S'),
                // dynamic bank details from the transaction's account
                'received_via'   => $receivedVia,
                'bank_account'   => $isBankMethod && $bankAccount ? $bankAccount->bank_name . ' — ' . $bankAccount->branch : null,
                'bank_name'      => $isBankMethod && $bankAccount ? $bankAccount->bank_name : null,
                'bank_ac_no'     => $isBankMethod && $bankAccount ? $bankAccount->ac_number : null,
                'bank_holder'    => $isBankMethod && $bankAccount ? $bankAccount->holder_name : null,
                'account_name'   => $transaction->account?->name,
            ],

            'customer' => [
                'name'    => $customer->name,
                'id'      => $customer->customer_id,
                'phone'   => $customer->phone,
                'address' => $customer->address,
            ],

            'payer' => [
                'is_customer' => !$transaction->name,
                'name'        => $transaction->name ?: $customer->name,
                'phone'       => $transaction->phone ?: $customer->phone,
            ],

            'property' => [
                'name'       => $property->name,
                'address'    => $property->address,
                'type'       => $unit->type ?? $unit->unit_type ?? '—',
                'floor_unit' => ($unit->floor?->label.'/'.$unit->code) ?? '—',
                'size'       => $unit->area
                    ? number_format((float) $unit->area, 0) . ' sft'
                    : ($unit->size_sqft ? number_format((float) $unit->size_sqft, 0) . ' sft' : '—'),
            ],

            'installment' => [
                'label'      => $scheduleLabel,
                'current'    => $schedule->sequence_no,
                'total'      => $sale->schedule_count,
                'amount'     => (float) $schedule->amount,
                'paid_total' => $paidTotal,
                'due'        => $due,
                'history'    => $installmentHistory->toArray(),
            ],
        ]);
    }

    protected function amountInWords(float $amount): string
    {
        return 'Bangladeshi Taka ' . ucwords(strtolower(
            (new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($amount)
        )) . ' Only';
    }
}
