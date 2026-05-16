<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\ApprovalAction;
use App\Enums\Inventory\ApprovalStage;
use App\Enums\Inventory\PurchaseFundReleaseType;
use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockReceiveStatus;
use App\Models\Account;
use App\Models\PurchaseFund;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseSettlement;
use App\Models\StockReceiveItem;
use App\Services\Accounts\AccountingEntryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function generatePoNo(): string
    {
        $lastId = (int) PurchaseOrder::query()->max('id');

        return 'PO-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function submitForEngineerApproval(PurchaseOrder $purchaseOrder, ?int $userId = null): PurchaseOrder
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($purchaseOrder, $actorId): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::DRAFT) {
                throw new \DomainException('Only draft purchase order can be submitted.');
            }

            if ($lockedOrder->items->isEmpty()) {
                throw new \DomainException('At least one item is required before submission.');
            }

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::PENDING_ENGINEER->value,
            ]);

            return $lockedOrder->refresh();
        });
    }
    public function updateItemApprovals(PurchaseOrder $purchaseOrder, array $itemApprovals, string $updateBy): void
    {
        if (empty($itemApprovals)) {
            return;
        }
        if ($purchaseOrder->items->isEmpty()) {
            return;
        }
        if ($updateBy == 'chief_engineer') {

            foreach ($purchaseOrder->items as $item) {
                $input = $itemApprovals[$item->id] ?? null;
                if (!is_array($input)) {
                    continue;
                }


                $qty = (float) ($input['approved_quantity'] ?? 0);
                $unit = (float) ($input['approved_unit_price'] ?? 0);

                $item->update([
                    'eng_approved_quantity' => $qty,
                    'eng_approved_unit_price' => $unit,
                    'eng_approved_total_price' => round($qty * $unit, 2),
                ]);
            }
        } elseif ($updateBy == 'approval') {

            foreach ($purchaseOrder->items as $item) {
                $input = $itemApprovals[$item->id] ?? null;
                if (!is_array($input)) {
                    continue;
                }


                $qty = (float) ($input['approved_quantity'] ?? 0);
                $unit = (float) ($input['approved_unit_price'] ?? 0);

                $item->update([
                    'approved_quantity' => $qty,
                    'approved_unit_price' => $unit,
                    'approved_total_price' => round($qty * $unit, 2),
                ]);
            }
        }
    }
    public function updateStatus(PurchaseOrder $purchaseOrder, PurchaseOrderStatus $status): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $status): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            $lockedOrder->update([
                'status' => $status->value,
            ]);

            return $lockedOrder->refresh();
        });
    }

    public function engineerApprove(PurchaseOrder $purchaseOrder, ?int $userId = null, ?string $remarks = null): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $userId, $remarks): PurchaseOrder {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::PENDING_ENGINEER) {
                throw new \DomainException('Purchase order is not pending engineer approval.');
            }
            $lockedOrder->update([
                'status' => PurchaseOrderStatus::PENDING_CHAIRMAN->value,
                'engieer_approved_by' => $actorId,
                'engineer_approved_at' => now(),
            ]);


            $this->createApprovalHistory(
                purchaseOrderId: (int) $lockedOrder->id,
                stage: ApprovalStage::ENGINEER,
                userId: $actorId,
                action: ApprovalAction::APPROVED,
                remarks: $remarks
            );

            return $lockedOrder->refresh();
        });
    }

    public function chairmanApprove(PurchaseOrder $purchaseOrder, ?int $userId = null, ?string $remarks = null): PurchaseOrder
    {

        return DB::transaction(function () use ($purchaseOrder, $userId, $remarks): PurchaseOrder {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::PENDING_CHAIRMAN) {
                throw new \DomainException('Purchase order is not pending chairman approval.');
            }

            $requestedAmount = round((float) $lockedOrder->fund_request_amount, 2);
            $approvedTotal = round(
                $lockedOrder->items->sum('approved_total_price'),
                2
            );

            $approvedAmount = round((float) $requestedAmount, 2);
            // $this->validateApprovedAmount($approvedTotal, $requestedAmount);

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::PENDING_ACCOUNTS->value,
                'chairman_approved_by' => $actorId,
                'chairman_approved_at' => now(),
                'approved_amount' => $approvedTotal,
            ]);

            $this->createApprovalHistory(
                purchaseOrderId: (int) $lockedOrder->id,
                stage: ApprovalStage::CHAIRMAN,
                userId: $actorId,
                action: ApprovalAction::APPROVED,
                remarks: $remarks
            );

            return $lockedOrder->refresh();
        });
    }

    public function chairmanApproveWithAmount(
        PurchaseOrder $purchaseOrder,
        float|int|string $approvedAmount,
        ?int $userId = null,
        ?string $remarks = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($purchaseOrder, $approvedAmount, $userId, $remarks): PurchaseOrder {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::PENDING_CHAIRMAN) {
                throw new \DomainException('Purchase order is not pending chairman approval.');
            }

            $requestedAmount = round((float) $lockedOrder->fund_request_amount, 2);
            $finalApprovedAmount = round((float) $approvedAmount, 2);
            $this->validateApprovedAmount($finalApprovedAmount, $requestedAmount);

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::PENDING_ACCOUNTS->value,
                'chairman_approved_by' => $actorId,
                'chairman_approved_at' => now(),
                'approved_amount' => $finalApprovedAmount,
            ]);

            $this->createApprovalHistory(
                purchaseOrderId: (int) $lockedOrder->id,
                stage: ApprovalStage::CHAIRMAN,
                userId: $actorId,
                action: ApprovalAction::APPROVED,
                remarks: $remarks
            );

            return $lockedOrder->refresh();
        });
    }

    public function accountsApprove(
        PurchaseOrder $purchaseOrder,
        ?int $userId = null,
        ?float $approvedAmount = null,
        ?string $remarks = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($purchaseOrder, $userId, $approvedAmount, $remarks): PurchaseOrder {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::PENDING_ACCOUNTS) {
                throw new \DomainException('Purchase order is not pending accounts approval.');
            }

            $requestedAmount = round((float) $lockedOrder->fund_request_amount, 2);
            $finalApprovedAmount = round((float) ($lockedOrder->approved_amount ?: ($approvedAmount ?? $requestedAmount)), 2);
            // $this->validateApprovedAmount($finalApprovedAmount, $requestedAmount);

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::APPROVED->value,
                'accounts_approved_by' => $actorId,
                'accounts_approved_at' => now(),
                'approved_amount' => $finalApprovedAmount,
            ]);

            $this->createApprovalHistory(
                purchaseOrderId: (int) $lockedOrder->id,
                stage: ApprovalStage::ACCOUNTS,
                userId: $actorId,
                action: ApprovalAction::APPROVED,
                remarks: $remarks
            );

            return $lockedOrder->refresh();
        });
    }

    public function reject(
        PurchaseOrder $purchaseOrder,
        ApprovalStage|string $stage,
        ?int $userId = null,
        ApprovalAction|string $action = ApprovalAction::REJECTED,
        ?string $remarks = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($purchaseOrder, $stage, $userId, $action, $remarks): PurchaseOrder {
            $actorId = $this->resolveActorId($userId);
            $approvalStage = is_string($stage) ? ApprovalStage::from($stage) : $stage;
            $approvalAction = is_string($action) ? ApprovalAction::from($action) : $action;

            if (!in_array($approvalAction, [ApprovalAction::REJECTED, ApprovalAction::RETURNED], true)) {
                throw new \DomainException('Invalid approval action for reject workflow.');
            }

            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);

            $expectedStatus = match ($approvalStage) {
                ApprovalStage::ENGINEER => PurchaseOrderStatus::PENDING_ENGINEER,
                ApprovalStage::CHAIRMAN => PurchaseOrderStatus::PENDING_CHAIRMAN,
                ApprovalStage::ACCOUNTS => PurchaseOrderStatus::PENDING_ACCOUNTS,
            };

            if ($lockedOrder->status !== $expectedStatus) {
                throw new \DomainException('Purchase order is not at the requested approval stage.');
            }

            $nextStatus = $approvalAction === ApprovalAction::RETURNED
                ? PurchaseOrderStatus::DRAFT
                : PurchaseOrderStatus::REJECTED;

            $updateData = [
                'status' => $nextStatus->value,
            ];

            match ($approvalStage) {
                ApprovalStage::ENGINEER => $updateData['engineer_approved_by'] = $actorId,
                ApprovalStage::CHAIRMAN => $updateData['chairman_approved_by'] = $actorId,
                ApprovalStage::ACCOUNTS => $updateData['accounts_approved_by'] = $actorId,
            };

            match ($approvalStage) {
                ApprovalStage::ENGINEER => $updateData['engineer_approved_at'] = now(),
                ApprovalStage::CHAIRMAN => $updateData['chairman_approved_at'] = now(),
                ApprovalStage::ACCOUNTS => $updateData['accounts_approved_at'] = now(),
            };

            $lockedOrder->update($updateData);

            $this->createApprovalHistory(
                purchaseOrderId: (int) $lockedOrder->id,
                stage: $approvalStage,
                userId: $actorId,
                action: $approvalAction,
                remarks: $remarks
            );

            return $lockedOrder->refresh();
        });
    }

    /**
     * @param  array{
     *   payment_method:string,
     *   amount:float|int|string,
     *   received_by?:int|string|null,
     *   release_date:string,
     *   remarks?:string|null
     * }  $payload
     */
    public function releaseFund(PurchaseOrder $purchaseOrder, array $payload, ?int $userId = null): PurchaseFund
    {


        return DB::transaction(function () use ($purchaseOrder, $payload, $userId): PurchaseFund {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            $lockedAccount= Account::query()->lockForUpdate()->findOrFail($payload['payer_account_id']);
            $balance = $lockedAccount->balance;
            //  if ($balance < $payload['amount']) {
            //     throw new \DomainException('Insufficient balance in your payer account. your account balance is '.$balance.' and you are trying to release '.$payload['amount']);
            // }
          
            if (
                !in_array($lockedOrder->status, [
                    PurchaseOrderStatus::APPROVED,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED,
                    PurchaseOrderStatus::RECEIVED,
                ], true)
            ) {
                throw new \DomainException('Fund can be released only for approved or receiving purchase orders.');
            }


            $paymentMethod = PurchaseFundReleaseType::from((string) $payload['payment_method']);
            $amount = round((float) $payload['amount'], 2);
            if ($amount <= 0) {
                throw new \DomainException('Released amount must be greater than zero.');
            }

            $approvedAmount = round((float) $lockedOrder->approved_amount, 2);
            $alreadyReleased = round((float) $lockedOrder->funds()->sum('amount'), 2);
            if ($approvedAmount > 0 && ($alreadyReleased + $amount) > $approvedAmount) {
                throw new \DomainException('Released amount exceeds approved amount limit.');
            }

            return $lockedOrder->funds()->create([
                'release_type' => $paymentMethod->value,
                'amount' => $amount,
                'released_by' => $actorId,
                'release_date' => $payload['release_date'],
                'remarks' => $payload['remarks'] ?? null,
                'payto' => $payload['payee_type'] ?? null,
                'receiver_type' => $payload['receiver_type'] ?? null,
                'receiver_id' => $payload['receiver_id'] ?? null,
            ]);
        });
    }
  

    public function recalculateReceiveStatus(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if (
                !in_array($lockedOrder->status, [
                    PurchaseOrderStatus::APPROVED,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED,
                    PurchaseOrderStatus::RECEIVED,
                    PurchaseOrderStatus::COMPLETED,
                ], true)
            ) {
                return $lockedOrder->refresh();
            }

            $totalRequiredQty = 0.0;
            $totalReceivedQty = 0.0;

            foreach ($lockedOrder->items as $item) {
                $requiredQty = (float) ($item->approved_quantity ?: $item->quantity);
                if ($requiredQty <= 0) {
                    continue;
                }

                $receivedQty = (float) StockReceiveItem::query()
                    ->where('purchase_order_item_id', $item->id)
                    ->whereHas('stockReceive', fn($query) => $query->where('status', StockReceiveStatus::POSTED->value))
                    ->sum('quantity');

                $totalRequiredQty += $requiredQty;
                $totalReceivedQty += min($receivedQty, $requiredQty);
            }

            $nextStatus = $lockedOrder->status;
            if ($lockedOrder->status !== PurchaseOrderStatus::COMPLETED) {
                if ($totalReceivedQty <= 0) {
                    $nextStatus = PurchaseOrderStatus::APPROVED;
                } elseif ($totalReceivedQty + 0.0001 < $totalRequiredQty) {
                    $nextStatus = PurchaseOrderStatus::PARTIALLY_RECEIVED;
                } else {
                    $nextStatus = PurchaseOrderStatus::RECEIVED;
                }
            }

            $actualPurchaseAmount = (float) StockReceiveItem::query()
                ->whereHas('stockReceive', function ($query) use ($lockedOrder): void {
                    $query->where('purchase_order_id', $lockedOrder->id)
                        ->where('status', StockReceiveStatus::POSTED->value);
                })
                ->sum('total_price');

            $lockedOrder->update([
                'status' => $nextStatus->value,
                'actual_purchase_amount' => round($actualPurchaseAmount, 2),
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * @param  array{
     *   actual_purchase_amount:float|int|string,
     *   returned_cash_amount?:float|int|string|null,
     *   remarks?:string|null
     * }  $payload
     */
    public function settlePurchaseOrder(PurchaseOrder $purchaseOrder, array $payload, ?int $userId = null): PurchaseSettlement
    {
        return DB::transaction(function () use ($purchaseOrder, $payload, $userId): PurchaseSettlement {
            $actorId = $this->resolveActorId($userId);

            $lockedOrder = PurchaseOrder::query()
                ->with('funds')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if (
                !in_array($lockedOrder->status, [
                    PurchaseOrderStatus::APPROVED,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED,
                    PurchaseOrderStatus::RECEIVED,
                ], true)
            ) {
                throw new \DomainException('Settlement is allowed only for approved or received purchase orders.');
            }

            $totalFundReleased = round((float) $lockedOrder->funds->sum('amount'), 2);
            $actualPurchaseAmount = round((float) ($payload['actual_purchase_amount'] ?? 0), 2);
            $returnedCash = round((float) ($payload['returned_cash_amount'] ?? 0), 2);
            $approvedAmount = round((float) $lockedOrder->approved_amount, 2);

            if ($actualPurchaseAmount < 0 || $returnedCash < 0) {
                throw new \DomainException('Settlement amounts cannot be negative.');
            }

            if ($approvedAmount > 0 && $actualPurchaseAmount > $approvedAmount) {
                throw new \DomainException('Actual purchase amount cannot exceed approved amount.');
            }

            $calculatedDue = round(max(0, $actualPurchaseAmount - $totalFundReleased), 2);
            $calculatedExcess = round(max(0, $totalFundReleased - $actualPurchaseAmount), 2);
            if ($returnedCash <= 0 && $calculatedExcess > 0) {
                $returnedCash = $calculatedExcess;
            }

            $settlement = PurchaseSettlement::query()->updateOrCreate(
                ['purchase_order_id' => $lockedOrder->id],
                [
                    'total_fund_released' => $totalFundReleased,
                    'actual_purchase_amount' => $actualPurchaseAmount,
                    'returned_cash_amount' => $returnedCash,
                    'due_amount' => $calculatedDue,
                    'settled_by' => $actorId,
                    'settled_at' => now(),
                    'remarks' => $payload['remarks'] ?? null,
                ]
            );

            $lockedOrder->update([
                'actual_purchase_amount' => $actualPurchaseAmount,
                'returned_amount' => $returnedCash,
                'due_amount' => $calculatedDue,
            ]);

            return $settlement->refresh();
        });
    }

    public function completePurchaseOrder(PurchaseOrder $purchaseOrder, ?int $userId = null, ?string $remarks = null): PurchaseOrder
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($purchaseOrder, $remarks): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->with('settlement')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($lockedOrder->status !== PurchaseOrderStatus::RECEIVED) {
                throw new \DomainException('Only received purchase order can be completed.');
            }

            if (!$lockedOrder->settlement) {
                throw new \DomainException('Purchase order settlement is required before completion.');
            }

            if ($lockedOrder->purchase_mode === PurchaseMode::CASH && (float) $lockedOrder->settlement->due_amount > 0) {
                throw new \DomainException('Cash purchase order cannot be completed with due amount.');
            }

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::COMPLETED->value,
                'remarks' => $remarks ?: $lockedOrder->remarks,
            ]);

            return $lockedOrder->refresh();
        });
    }

    public function cancelPurchaseOrder(PurchaseOrder $purchaseOrder, ?int $userId = null): PurchaseOrder
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);

            if (
                !in_array($lockedOrder->status, [
                    PurchaseOrderStatus::DRAFT,
                    PurchaseOrderStatus::PENDING_ENGINEER,
                    PurchaseOrderStatus::PENDING_CHAIRMAN,
                    PurchaseOrderStatus::PENDING_ACCOUNTS,
                ], true)
            ) {
                throw new \DomainException('This purchase order cannot be cancelled.');
            }

            $lockedOrder->update([
                'status' => PurchaseOrderStatus::CANCELLED->value,
            ]);

            return $lockedOrder->refresh();
        });
    }

    protected function createApprovalHistory(
        int $purchaseOrderId,
        ApprovalStage $stage,
        int $userId,
        ApprovalAction $action,
        ?string $remarks = null
    ): PurchaseOrderApproval {
        return PurchaseOrderApproval::query()->create([
            'purchase_order_id' => $purchaseOrderId,
            'approval_stage' => $stage->value,
            'user_id' => $userId,
            'action' => $action->value,
            'remarks' => $remarks,
        ]);
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required for this action.');
        }

        return $actorId;
    }

    protected function validateApprovedAmount(float $approvedAmount, float $requestedAmount): void
    {
        if ($approvedAmount <= 0) {
            throw new \DomainException('Approved amount must be greater than zero.');
        }

        if ($requestedAmount > 0 && $approvedAmount > $requestedAmount) {
            throw new \DomainException('Approved amount cannot be more than requested amount.');
        }
    }
}
