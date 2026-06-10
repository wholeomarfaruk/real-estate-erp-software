{{--
    resources/views/admin/reports/index.blade.php
    Reports hub — Admin · Star Unity ERP
    Stack: Tailwind CSS 4 + Alpine.js 3.x

    @extends your existing layout. Alpine drives the live search client-side.
    Add @stack('scripts') to your layout <body> end if not already present.
--}}
@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
    $cats = [
        ['key'=>'crm',       'name'=>'CRM Reports',          'count'=>6],
        ['key'=>'sales',     'name'=>'Sales & Rents Reports', 'count'=>7],
        ['key'=>'finance',   'name'=>'Finance Reports',       'count'=>12],
        ['key'=>'project',   'name'=>'Project Reports',       'count'=>8],
        ['key'=>'marketing', 'name'=>'Marketing Reports',     'count'=>6],
        ['key'=>'hr',        'name'=>'HR Reports',            'count'=>5],
        ['key'=>'inventory', 'name'=>'Inventory Reports',     'count'=>6],
        ['key'=>'document',  'name'=>'Document Reports',      'count'=>4],
        ['key'=>'custom',    'name'=>'Custom Reports',        'count'=>4],
    ];
    $totalReports = array_sum(array_column($cats, 'count'));
@endphp

<div class="max-w-[1100px] mx-auto px-6 py-7" x-data="reportsHub()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1.5 text-[11.5px] font-mono text-ink-3 mb-3.5">
        <a href="#" class="hover:text-ink-1 transition-colors">Admin</a>
        <span class="opacity-50">/</span>
        <span class="text-ink-1">Reports</span>
    </div>

    {{-- Header --}}
    <div class="flex justify-between items-end gap-5 mb-5 flex-wrap">
        <div>
            <h1 class="text-[25px] font-semibold tracking-tight">Reports</h1>
            <div class="mt-1.5 flex items-center gap-2 text-[12px] font-mono text-ink-2 flex-wrap">
                <span x-text="catLabel"></span>
                <span class="w-[3px] h-[3px] rounded-full bg-ink-3"></span>
                <span x-text="repLabel"></span>
                <span class="w-[3px] h-[3px] rounded-full bg-ink-3"></span>
                <span>Real-estate ERP</span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input x-ref="q" x-model="q"
                       placeholder="Search all reports…"
                       @keydown.window.slash.prevent="$refs.q.focus()"
                       @keydown.escape="q='';$refs.q.blur()"/>
                <kbd>/</kbd>
            </div>
            <a class="btn" href="{{ route('admin.reports.scheduled') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Scheduled
            </a>
            <a class="btn btn-primary" href="{{ route('admin.reports.builder') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                Report builder
            </a>
        </div>
    </div>

    {{-- Featured: Dashboard Reports --}}
    <a class="feature" href="{{ route('admin.reports.dashboard') }}">
        <div class="f-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg></div>
        <div class="flex-1 min-w-0">
            <div class="f-title">Dashboard Reports <span class="f-tag">Live</span></div>
            <div class="f-sub">Real-time KPI overview across sales, finance, projects, CRM and inventory.</div>
        </div>
        <div class="f-go">
            Open dashboards
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </div>
        <div class="f-watermark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg></div>
    </a>

    {{-- Category masonry (Alpine renders + filters) --}}
    <div class="cat-grid">
        <template x-for="cat in filtered" :key="cat.key">
            <article class="cat-card">
                <div class="cat-head">
                    <div class="cat-ic" :class="'t-' + cat.key" x-html="icons[cat.key]"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[14.5px] font-semibold tracking-[-0.005em]" x-text="cat.name"></div>
                        <div class="mt-0.5 text-[10.5px] font-mono text-ink-3">
                            <span x-text="cat.count"></span>&nbsp;reports
                        </div>
                    </div>
                    <a class="view-all" :href="catRoute(cat.key)">
                        View all
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
                <div class="cat-list">
                    <template x-for="item in cat.items" :key="item">
                        <a class="rep-row" :href="catRoute(cat.key)">
                            <span class="dot" :class="'d-' + cat.key"></span>
                            <span class="rep-name" x-html="highlight(item)"></span>
                            <span class="chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </a>
                    </template>
                </div>
            </article>
        </template>
    </div>

    {{-- Empty --}}
    <div class="py-16 text-center text-ink-3" x-show="filtered.length === 0">
        <svg class="w-8 h-8 opacity-50 mx-auto mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <div class="text-[14px] font-semibold text-ink-2">No reports match</div>
        <div class="text-[12.5px] mt-1">Try a different keyword.</div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportsHub', () => ({
        q: '',
        totalReports: {{ $totalReports }},
        cats: @js(array_map(fn($c) => array_merge($c, ['items' => match($c['key']) {
            'crm'       => ['Lead Reports','Follow-up Reports','Conversion Reports','Lead Source Reports','Agent Performance Reports','Activity Reports'],
            'sales'     => ['Booking Reports','Unit Sales Reports','Rent & Lease Reports','Reservation Reports','Cancellation Reports','Salesperson Performance Reports','Commission Reports'],
            'finance'   => ['Income Reports','Expense Reports','Profit & Loss','Balance Sheet','Cash Flow','Bank Reports','Accounts Receivable','Accounts Payable','Invoice Reports','Payment Reports','Transaction Reports','Tax Reports'],
            'project'   => ['Project Summary','Progress Reports','Budget vs Actual','Resource Allocation','Task Reports','Milestone Reports','Contractor Reports','Project Profitability'],
            'marketing' => ['Campaign Reports','SMS Reports','Email Reports','Lead Generation Reports','Campaign ROI Reports','Open & Click Reports'],
            'hr'        => ['Employee Reports','Attendance Reports','Leave Reports','Payroll Reports','Performance Reports'],
            'inventory' => ['Stock Reports','Purchase Reports','Supplier Reports','Material Usage Reports','Warehouse Reports','Inventory Valuation'],
            'document'  => ['Document Status','Expiry Reports','Missing Documents','Audit Logs'],
            'custom'    => ['Report Builder','Saved Reports','Scheduled Reports','Shared Reports'],
            default     => [],
        }]), $cats)),
        icons: {
            crm:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            sales:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            finance:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            project:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
            marketing:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
            hr:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
            inventory:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            document: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            custom:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>',
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            return this.cats.map(c => {
                const matchCat = c.name.toLowerCase().includes(q);
                const items = (q && !matchCat) ? c.items.filter(i => i.toLowerCase().includes(q)) : c.items;
                return { ...c, items };
            }).filter(c => c.items.length);
        },
        highlight(name) {
            const q = this.q.trim();
            const esc = s => s.replace(/[&<>"]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
            if (!q) return esc(name);
            const i = name.toLowerCase().indexOf(q.toLowerCase());
            if (i < 0) return esc(name);
            return esc(name.slice(0,i))+'<mark>'+esc(name.slice(i,i+q.length))+'</mark>'+esc(name.slice(i+q.length));
        },
        catRoute(key) {
            const routes = @json(collect($cats)->mapWithKeys(fn($c) => [$c['key'], route('admin.reports.category', $c['key'])]));
            return routes[key] ?? '#';
        },
        get catLabel() {
            const n = this.filtered.length;
            return this.q.trim() ? `${n} ${n===1?'category':'categories'}` : '10 categories';
        },
        get repLabel() {
            if (!this.q.trim()) return `${this.totalReports} reports`;
            const n = this.filtered.reduce((a,c)=>a+c.items.length,0);
            return `${n} ${n===1?'report':'reports'}`;
        },
    }));
});
</script>
@endpush
