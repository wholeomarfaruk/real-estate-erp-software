<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Contracts\View\View;

class PayrollDocumentController extends Controller
{
    public function payslipPrint(Payroll $payroll): View
    {
        abort_unless(auth()->user()?->can('hrm.payrolls.print'), 403, 'Unauthorized action.');

        $payroll->load([
            'employee.department:id,name',
            'employee.designation:id,name',
            'items:id,payroll_id,type,label,amount,sort_order',
            'payments:id,payroll_id,payment_date,amount,payment_method,reference_no',
        ]);

        return view('admin.hrm.payrolls.payslip', [
            'payroll' => $payroll,
            'itemsByType' => $payroll->items->groupBy('type'),
            'paidAmount' => round((float) $payroll->payments->sum('amount'), 2),
        ]);
    }
}

