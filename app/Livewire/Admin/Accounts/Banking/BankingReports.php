<?php

namespace App\Livewire\Admin\Accounts\Banking;

use Livewire\Component;

class BankingReports extends Component
{
    public function render()
    {
        return view('livewire.admin.accounts.banking.banking-reports')
            ->layout('layouts.admin.admin');
    }
}
