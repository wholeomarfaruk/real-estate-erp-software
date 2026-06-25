<?php

namespace App\Services\Accounts;

use App\Models\BankingPaymentRequest;
use App\Models\Project;

/**
 * Handles creation of banking payment requests for expenses.
 * Prepares data for later posting to ledger via PostingEngine.
 */
class RequestEngine
{
    /**
     * Create a project expense payment request.
     */
    public function createProjectExpenseRequest(
        int $projectId,
        int $expenseAccountId,
        int $paymentAccountId,
        string $paymentMethod,
        float $amount,
        string $title,
        string $date,
        ?string $referenceNo = null,
        ?string $paidToName = null,
        ?string $paidToPhone = null,
        ?string $workPhase = null,
        ?string $transactionType = null,
        ?array $attachmentIds = null,
        int $userId = null
    ): BankingPaymentRequest {
        $userId = $userId ?: auth()->id();

        $externalData = [
            'expense_account_id' => $expenseAccountId,
            'payment_account_id' => $paymentAccountId,
            'payment_method'     => $paymentMethod,
            'transaction_type'   => $transactionType ?: 'expense',
            'reference_no'       => $referenceNo ?: null,
            'paid_to_name'       => $paidToName ?: null,
            'paid_to_phone'      => $paidToPhone ?: null,
            'project_work_phase' => $workPhase ?: null,
            'posting_required'   => true,
        ];

        if (!empty($attachmentIds)) {
            $externalData['attachments'] = $attachmentIds;
        }

        $roundedAmount = round($amount, 2);

        return BankingPaymentRequest::create([
            'request_no'        => BankingPaymentRequest::generateRequestNo(),
            'source_type'       => $transactionType ?: 'expense',
            'sourceable_type'   => Project::class,
            'sourceable_id'     => $projectId,
            'amount'            => $roundedAmount,
            'description'       => $title,
            'method'            => $paymentMethod,
            'reference_no'      => $referenceNo ?: null,
            'name'              => $paidToName ?: null,
            'phone'             => $paidToPhone ?: null,
            'requested_by'      => $userId,
            'external_data'     => $externalData,
            'debit_account_id'  => $expenseAccountId,
            'debit_amount'      => $roundedAmount,
            'credit_account_id' => $paymentAccountId,
            'credit_amount'     => $roundedAmount,
        ]);
    }

    /**
     * Create a generic expense payment request (office, marketing, etc).
     */
    public function createExpenseRequest(
        string $expenseType,
        int $expenseAccountId,
        int $paymentAccountId,
        string $paymentMethod,
        float $amount,
        string $title,
        ?string $referenceNo = null,
        ?string $paidToName = null,
        ?string $paidToPhone = null,
        ?array $attachmentIds = null,
        ?string $sourceableType = null,
        ?int $sourceableId = null,
        int $userId = null
    ): BankingPaymentRequest {
        $userId = $userId ?: auth()->id();

        $externalData = [
            'expense_type'      => $expenseType,
            'expense_account_id' => $expenseAccountId,
            'payment_account_id' => $paymentAccountId,
            'payment_method'     => $paymentMethod,
            'reference_no'       => $referenceNo ?: null,
            'paid_to_name'       => $paidToName ?: null,
            'paid_to_phone'      => $paidToPhone ?: null,
            'posting_required'   => true,
        ];

        if (!empty($attachmentIds)) {
            $externalData['attachments'] = $attachmentIds;
        }

        $roundedAmount = round($amount, 2);

        $data = [
            'request_no'        => BankingPaymentRequest::generateRequestNo(),
            'source_type'       => $expenseType,
            'amount'            => $roundedAmount,
            'description'       => $title,
            'method'            => $paymentMethod,
            'reference_no'      => $referenceNo ?: null,
            'name'              => $paidToName ?: null,
            'phone'             => $paidToPhone ?: null,
            'requested_by'      => $userId,
            'external_data'     => $externalData,
            'debit_account_id'  => $expenseAccountId,
            'debit_amount'      => $roundedAmount,
            'credit_account_id' => $paymentAccountId,
            'credit_amount'     => $roundedAmount,
        ];

        // Add sourceable relationship if provided (for polymorphic associations)
        if ($sourceableType && $sourceableId) {
            $data['sourceable_type'] = $sourceableType;
            $data['sourceable_id'] = $sourceableId;
        }

        return BankingPaymentRequest::create($data);
    }
}
