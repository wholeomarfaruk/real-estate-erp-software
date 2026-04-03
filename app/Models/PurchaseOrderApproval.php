<?php

namespace App\Models;

use App\Enums\Inventory\ApprovalAction;
use App\Enums\Inventory\ApprovalStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'approval_stage',
        'user_id',
        'action',
        'remarks',
    ];

    protected $casts = [
        'approval_stage' => ApprovalStage::class,
        'action' => ApprovalAction::class,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
