<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'type',
        'name',
        'father_name',
        'mother_name',
        'date_of_birth',
        'gender',
        'phone',
        'phone_alt',
        'email',
        'address',
        'district',
        'division',
        'postal_code',
        'company_name',
        'company_registration_no',
        'company_tax_id',
        'doc_type',
        'doc_no',
        'doc_issue_date',
        'doc_expiry_date',
        'doc_file_id',
        'profile_image_id',
        'kyc_status',
        'kyc_date',
        'status',
        'source',
        'notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth'   => 'date',
        'doc_issue_date'  => 'date',
        'doc_expiry_date' => 'date',
        'kyc_date'        => 'date',
        'attachments'     => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Customer $customer) {
            $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
            $customer->customer_id = 'CUST-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);
        });
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = array_slice(
            array_filter(array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), $words)),
            0,
            2
        );

        return implode('', $initials);
    }

    public function profileImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'profile_image_id');
    }

    public function docFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'doc_file_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
