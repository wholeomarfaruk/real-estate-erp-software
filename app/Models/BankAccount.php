<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'bank_name',
        'code',
        'ac_number',
        'branch',
        'holder_name',
        'route_code',
        'swift_code',
        'address',
        'note',
        'status',
        'account_id',
        'phone',
        'email',
    ];
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
