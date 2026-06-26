<?php

namespace App\Enums\Accounts;

enum EntryWorkflow: string
{
    case BANKING_REQUEST = 'banking_request';
    case DIRECT_LEDGER = 'direct_ledger';
}
