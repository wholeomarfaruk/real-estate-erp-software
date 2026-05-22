<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Expense;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user  = Auth::user();
        $roles = $user?->getRoleNames()->toArray() ?? [];

        $isAdmin     = $this->hasRole($roles, ['admin', 'superadmin']);
        $isAccounts  = $this->hasRole($roles, ['accounts']);
        $isMD        = $this->hasRole($roles, ['md', 'chairman']);
        $isStore     = $this->hasRole($roles, ['storemanager']);
        $isEngineer  = $this->hasRole($roles, ['chiefengineer', 'engineer']);
        $isFinance   = $isAdmin || $isAccounts || $isMD;
        $isPurchase  = $isAdmin || $isStore || $isEngineer || $isMD;

        // ── KPI cards ──────────────────────────────────────────────────────
        $kpis = [];

        if ($isFinance) {
            $bankBalance = (float) DB::table('transactions')
                ->whereIn('account_id',
                    BankAccount::where('status', 'active')->whereNotNull('account_id')->pluck('account_id')
                )
                ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                ->value('balance');

            $kpis[] = [
                'label'    => 'Total Bank Balance',
                'value'    => number_format($bankBalance, 2),
                'sub'      => BankAccount::where('status', 'active')->count() . ' active accounts',
                'color'    => 'indigo',
                'icon'     => 'bank',
            ];
        }

        if ($isAdmin || $isAccounts) {
            $thisMonthIncome = (float) Transaction::whereIn('account_id',
                    BankAccount::whereNotNull('account_id')->pluck('account_id')
                )
                ->whereMonth('datetime', now()->month)
                ->whereYear('datetime', now()->year)
                ->sum('debit');

            $kpis[] = [
                'label' => 'Income This Month',
                'value' => number_format($thisMonthIncome, 2),
                'sub'   => now()->format('F Y'),
                'color' => 'emerald',
                'icon'  => 'income',
            ];

            $thisMonthExpense = (float) Expense::where('status', 'posted')
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount');

            $kpis[] = [
                'label' => 'Expenses This Month',
                'value' => number_format($thisMonthExpense, 2),
                'sub'   => Expense::where('status', 'posted')->whereMonth('date', now()->month)->count() . ' entries',
                'color' => 'rose',
                'icon'  => 'expense',
            ];

            $pendingPayments = BankingPaymentRequest::whereIn('status', ['pending', 'approved', 'released'])->count();

            $kpis[] = [
                'label' => 'Pending Banking',
                'value' => $pendingPayments,
                'sub'   => BankingPaymentRequest::where('status', 'pending')->count() . ' awaiting approval',
                'color' => 'amber',
                'icon'  => 'pending',
            ];
        }

        if ($isMD && ! $isAdmin) {
            $thisMonthExpense = (float) Expense::where('status', 'posted')
                ->whereMonth('date', now()->month)
                ->sum('amount');

            $kpis[] = [
                'label' => 'Expenses This Month',
                'value' => number_format($thisMonthExpense, 2),
                'sub'   => now()->format('F Y'),
                'color' => 'rose',
                'icon'  => 'expense',
            ];
        }

        if ($isPurchase) {
            $activePOs = PurchaseOrder::whereNotIn('status', ['completed', 'cancelled'])->count();

            $kpis[] = [
                'label' => 'Active Purchase Orders',
                'value' => $activePOs,
                'sub'   => PurchaseOrder::where('status', 'pending')->count() . ' pending approval',
                'color' => 'blue',
                'icon'  => 'po',
            ];
        }

        if ($isStore) {
            $totalStock  = (float) StockBalance::sum('quantity');
            $lowStock    = StockBalance::whereRaw('quantity <= 5')->count();

            $kpis[] = [
                'label' => 'Total Stock (Units)',
                'value' => number_format($totalStock, 0),
                'sub'   => $lowStock . ' items low / out of stock',
                'color' => $lowStock > 0 ? 'amber' : 'emerald',
                'icon'  => 'stock',
            ];
        }

        if ($isAdmin) {
            $kpis[] = [
                'label' => 'Total Users',
                'value' => User::count(),
                'sub'   => Supplier::count() . ' suppliers',
                'color' => 'violet',
                'icon'  => 'users',
            ];
        }

        // ── Recent Banking Requests ────────────────────────────────────────
        $recentBankingRequests = ($isFinance)
            ? BankingPaymentRequest::with(['bankAccount:id,bank_name', 'requestedBy:id,name'])
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        // ── Recent Expenses ────────────────────────────────────────────────
        $recentExpenses = ($isFinance)
            ? Expense::with(['transactionCategory:id,name', 'bankAccount:id,bank_name'])
                ->latest('date')
                ->limit(6)
                ->get()
            : collect();

        // ── Recent Purchase Orders ─────────────────────────────────────────
        $recentPOs = ($isPurchase)
            ? PurchaseOrder::with(['supplier:id,name'])
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        // ── Bank accounts summary ──────────────────────────────────────────
        if ($isFinance) {
            $summaryBanks = BankAccount::where('status', 'active')
                ->whereNotNull('account_id')
                ->orderByDesc('id')
                ->limit(6)
                ->get(['id', 'bank_name', 'ac_number', 'type', 'account_id']);

            $summaryAcctIds = $summaryBanks->pluck('account_id')->filter()->all();

            $summaryBalances = $summaryAcctIds
                ? DB::table('transactions')
                    ->whereIn('account_id', $summaryAcctIds)
                    ->selectRaw('account_id, COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                    ->groupBy('account_id')
                    ->pluck('balance', 'account_id')
                : collect();

            $bankSummary = $summaryBanks->map(fn ($b) => [
                'name'    => $b->bank_name,
                'code'    => $b->ac_number,
                'type'    => $b->type,
                'balance' => (float) ($summaryBalances[$b->account_id] ?? 0),
            ]);
        } else {
            $bankSummary = collect();
        }

        // ── Stock low alert ────────────────────────────────────────────────
        $lowStockItems = ($isStore || $isAdmin)
            ? StockBalance::with(['product:id,name', 'store:id,name'])
                ->whereRaw('quantity <= 5')
                ->orderBy('quantity')
                ->limit(6)
                ->get()
            : collect();

        // ── Role display label ─────────────────────────────────────────────
        $roleLabel = match (true) {
            $isAdmin    => 'Administrator',
            $isAccounts => 'Accounts',
            $isMD       => in_array('md', $roles) ? 'Managing Director' : 'Chairman',
            $isStore    => 'Store Manager',
            $isEngineer => in_array('chiefengineer', $roles) ? 'Chief Engineer' : 'Engineer',
            default     => ucfirst($roles[0] ?? 'User'),
        };

        return view('livewire.admin.dashboard.dashboard', compact(
            'kpis',
            'recentBankingRequests',
            'recentExpenses',
            'recentPOs',
            'bankSummary',
            'lowStockItems',
            'roleLabel',
            'isAdmin',
            'isAccounts',
            'isFinance',
            'isPurchase',
            'isStore',
        ))->layout('layouts.admin.admin');
    }

    private function hasRole(array $userRoles, array $check): bool
    {
        foreach ($check as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
