<?php

namespace App\Enums\Accounts;

enum TransactionStatus:string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
