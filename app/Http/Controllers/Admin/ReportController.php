<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\ConfigBasedRegistry;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private array $icons = [
        'crm'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'sales'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'finance'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'project'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        'marketing'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
        'hr'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
        'inventory'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'document' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'custom'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>',
    ];

    /**
     * Get categories with reports from config
     */
    private function categories(ConfigBasedRegistry $registry): array
    {
        $config = config('reports');
        $configCategories = $registry->getCategorized();

        // Hard-coded categories from config
        $staticCategories = [
            'crm', 'finance', 'project', 'marketing', 'hr', 'inventory', 'document', 'custom'
        ];

        // Build result with config + static categories
        $result = [];

        // Add config-driven categories first (sales, finance, etc if in config)
        foreach ($staticCategories as $key) {
            if ($key === 'sales' && isset($configCategories['sales'])) {
                // Use registry for sales
                $result[] = array_merge(
                    $configCategories['sales'],
                    ['icon' => $this->icons[$key]]
                );
                continue;
            }

            // Static categories (not in config yet)
            $result[] = match($key) {
                'crm' => [
                    'key' => 'crm',
                    'name' => 'CRM Reports',
                    'desc' => 'Track every lead from first contact to conversion — with per-agent and source breakdowns.',
                    'icon' => $this->icons['crm'],
                    'items' => [
                        ['name' => 'Lead Reports', 'desc' => 'Volume, source and quality of all incoming leads by period.', 'route' => '#'],
                        ['name' => 'Follow-up Reports', 'desc' => 'Scheduled and completed follow-up activity by agent and date.', 'route' => '#'],
                        ['name' => 'Conversion Reports', 'desc' => 'Lead-to-booking conversion rate by agent, source and campaign.', 'route' => '#'],
                        ['name' => 'Lead Source Reports', 'desc' => 'Leads broken down by origin channel, referral and campaign.', 'route' => '#'],
                        ['name' => 'Agent Performance Reports', 'desc' => 'Individual agent KPIs across leads, follow-ups and closings.', 'route' => '#'],
                        ['name' => 'Activity Reports', 'desc' => 'Timeline of all CRM actions, calls, emails and site visits.', 'route' => '#'],
                    ],
                ],
                'finance' => [
                    'key' => 'finance',
                    'name' => 'Finance Reports',
                    'desc' => 'Full financial reporting suite — from daily transactions to annual balance sheet and tax.',
                    'icon' => $this->icons['finance'],
                    'items' => [
                        ['name' => 'Income Reports', 'desc' => 'All revenue streams broken down by category, project and period.', 'route' => '#'],
                        ['name' => 'Expense Reports', 'desc' => 'Expenditure by type, project and cost centre with approval status.', 'route' => '#'],
                        ['name' => 'Profit & Loss', 'desc' => 'Net operating income vs. expenditure over any selected period.', 'route' => '#'],
                        ['name' => 'Balance Sheet', 'desc' => 'Assets, liabilities and equity position at a given date.', 'route' => '#'],
                        ['name' => 'Cash Flow', 'desc' => 'Inflows and outflows with running and closing balance by day or month.', 'route' => '#'],
                        ['name' => 'Bank Reports', 'desc' => 'Bank account activity, statement reconciliation and balances.', 'route' => '#'],
                        ['name' => 'Accounts Receivable', 'desc' => 'Outstanding customer invoices, aging buckets and collection status.', 'route' => '#'],
                        ['name' => 'Accounts Payable', 'desc' => 'Supplier payables, due dates, overdue amounts and payment status.', 'route' => '#'],
                        ['name' => 'Invoice Reports', 'desc' => 'All invoices with paid, partial and unpaid breakdown and aging.', 'route' => '#'],
                        ['name' => 'Payment Reports', 'desc' => 'Received and issued payments with method, reference and date.', 'route' => '#'],
                        ['name' => 'Transaction Reports', 'desc' => 'Full ledger of financial transactions filterable by date or account.', 'route' => '#'],
                        ['name' => 'Tax Reports', 'desc' => 'VAT, TDS and other tax liabilities, filings and compliance status.', 'route' => '#'],
                    ],
                ],
                'project' => [
                    'key' => 'project',
                    'name' => 'Project Reports',
                    'desc' => 'Monitor project health, budget adherence, resource use and contractor progress.',
                    'icon' => $this->icons['project'],
                    'items' => [
                        ['name' => 'Project Summary', 'desc' => 'High-level status overview across all active and completed projects.', 'route' => '#'],
                        ['name' => 'Progress Reports', 'desc' => 'Physical progress vs. planned schedule with variance by project.', 'route' => '#'],
                        ['name' => 'Budget vs Actual', 'desc' => 'Planned vs. spent costs with variance and forecast-to-complete.', 'route' => '#'],
                        ['name' => 'Resource Allocation', 'desc' => 'Manpower and equipment usage and availability across projects.', 'route' => '#'],
                        ['name' => 'Task Reports', 'desc' => 'Task completion rates, overdue items and blockers by project.', 'route' => '#'],
                        ['name' => 'Milestone Reports', 'desc' => 'Key milestone tracking and schedule adherence by project.', 'route' => '#'],
                        ['name' => 'Contractor Reports', 'desc' => 'Contractor work orders, progress percentages and payment status.', 'route' => '#'],
                        ['name' => 'Project Profitability', 'desc' => 'Revenue vs. cost per project for unit-level profit margin analysis.', 'route' => '#'],
                    ],
                ],
                'marketing' => [
                    'key' => 'marketing',
                    'name' => 'Marketing Reports',
                    'desc' => 'Measure the reach, engagement and ROI of every campaign across email, SMS and ads.',
                    'icon' => $this->icons['marketing'],
                    'items' => [
                        ['name' => 'Campaign Reports', 'desc' => 'Reach, impressions, engagement and cost of each marketing campaign.', 'route' => '#'],
                        ['name' => 'SMS Reports', 'desc' => 'Delivery rates, click-through and responses from SMS campaigns.', 'route' => '#'],
                        ['name' => 'Email Reports', 'desc' => 'Email send, open, click-through and bounce metrics by campaign.', 'route' => '#'],
                        ['name' => 'Lead Generation Reports', 'desc' => 'Leads attributed to each campaign, channel and ad set.', 'route' => '#'],
                        ['name' => 'Campaign ROI Reports', 'desc' => 'Return on investment per campaign, channel and marketing period.', 'route' => '#'],
                        ['name' => 'Open & Click Reports', 'desc' => 'Per-email and per-link open and click-through analytics.', 'route' => '#'],
                    ],
                ],
                'hr' => [
                    'key' => 'hr',
                    'name' => 'HR Reports',
                    'desc' => 'People data — attendance, leave, payroll and performance in one place.',
                    'icon' => $this->icons['hr'],
                    'items' => [
                        ['name' => 'Employee Reports', 'desc' => 'Full workforce directory with role, department, status and tenure.', 'route' => '#'],
                        ['name' => 'Attendance Reports', 'desc' => 'Daily attendance, late arrivals and absenteeism by department.', 'route' => '#'],
                        ['name' => 'Leave Reports', 'desc' => 'Leave balances, approvals and utilisation trends by department.', 'route' => '#'],
                        ['name' => 'Payroll Reports', 'desc' => 'Monthly payroll summary, deductions and individual payslips.', 'route' => '#'],
                        ['name' => 'Performance Reports', 'desc' => 'Appraisal scores and KPI achievement by employee and cycle.', 'route' => '#'],
                    ],
                ],
                'inventory' => [
                    'key' => 'inventory',
                    'name' => 'Inventory Reports',
                    'desc' => 'Track stock levels, purchases, supplier dues and material consumption across warehouses.',
                    'icon' => $this->icons['inventory'],
                    'items' => [
                        ['name' => 'Stock Reports', 'desc' => 'Current stock levels by item, category and warehouse location.', 'route' => '#'],
                        ['name' => 'Purchase Reports', 'desc' => 'All purchase orders and receipts with supplier and date details.', 'route' => '#'],
                        ['name' => 'Supplier Reports', 'desc' => 'Supplier-wise purchase history, outstanding dues and advances.', 'route' => '#'],
                        ['name' => 'Material Usage Reports', 'desc' => 'Material consumed per project vs. estimated with variance.', 'route' => '#'],
                        ['name' => 'Warehouse Reports', 'desc' => 'Inbound, outbound and current balance by warehouse location.', 'route' => '#'],
                        ['name' => 'Inventory Valuation', 'desc' => 'Stock value at cost and market price across all warehouses.', 'route' => '#'],
                    ],
                ],
                'document' => [
                    'key' => 'document',
                    'name' => 'Document Reports',
                    'desc' => 'Monitor document compliance, identify missing files and review the full audit trail.',
                    'icon' => $this->icons['document'],
                    'items' => [
                        ['name' => 'Document Status', 'desc' => 'Status of all uploaded documents by category, entity and date.', 'route' => '#'],
                        ['name' => 'Expiry Reports', 'desc' => 'Documents nearing or past their expiry date requiring renewal.', 'route' => '#'],
                        ['name' => 'Missing Documents', 'desc' => 'Required documents not yet uploaded per entity or project.', 'route' => '#'],
                        ['name' => 'Audit Logs', 'desc' => 'Full history of document access, uploads, edits and deletions.', 'route' => '#'],
                    ],
                ],
                'custom' => [
                    'key' => 'custom',
                    'name' => 'Custom Reports',
                    'desc' => 'Build, save, schedule and share your own reports using any combination of data fields.',
                    'icon' => $this->icons['custom'],
                    'items' => [
                        ['name' => 'Report Builder', 'desc' => 'Drag-and-drop interface to create custom reports from any data source.', 'route' => '#'],
                        ['name' => 'Saved Reports', 'desc' => 'Your saved custom report templates — open, clone or delete.', 'route' => '#'],
                        ['name' => 'Scheduled Reports', 'desc' => 'Automated reports delivered by email on a recurring schedule.', 'route' => '#'],
                        ['name' => 'Shared Reports', 'desc' => 'Reports shared with your team, department or management.', 'route' => '#'],
                    ],
                ],
            };
        }

        return $result;
    }

    /** GET /admin/reports — hub page */
    public function index(ConfigBasedRegistry $registry)
    {
        abort_unless(auth()->user()?->can('reports.hub.view'), 403);

        $categories = $this->categories($registry);

        return view('admin.reports.index', [
            'cats' => array_map(fn($c) => [
                'key'   => $c['key'],
                'name'  => $c['name'],
                'count' => \count($c['items']),
            ], $categories),
        ]);
    }

    /** GET /admin/reports/{category} — category detail */
    public function category(string $key, ConfigBasedRegistry $registry)
    {
        abort_unless(auth()->user()?->can('reports.category.view'), 403);

        $all = $this->categories($registry);
        $category = collect($all)->firstWhere('key', $key);

        abort_if(! $category, 404);

        return view('admin.reports.category', [
            'category'      => $category,
            'allCategories' => $all,
        ]);
    }

    /* ─── Stubs — keep the buttons, fill in later ─── */
    public function scheduled() { return view('admin.reports.scheduled'); }  // TODO
    public function builder()   { return view('admin.reports.builder'); }    // TODO
    public function dashboard() { return view('admin.reports.dashboard'); }  // TODO
}
