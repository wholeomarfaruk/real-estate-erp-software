<div class="max-w-[1100px] mx-auto px-6 py-7" x-data="reportsHub()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1.5 text-[11.5px] font-mono text-ink-3 mb-3.5">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-ink-1 transition-colors">Admin</a>
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
                    <div class="cat-ic" :class="'t-' + cat.key" x-html="cat.icon"></div>
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
                    <template x-for="item in cat.items" :key="item.name">
                        <a class="rep-row" :href="item.route !== '#' ? item.route : catRoute(cat.key)">
                            <span class="dot" :class="'d-' + cat.key"></span>
                            <span class="rep-name" x-html="highlight(item.name)"></span>
                            <span class="chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                        </a>
                    </template>
                </div>
            </article>
        </template>
    </div>

    {{-- Empty state --}}
    <div class="py-16 text-center text-ink-3" x-show="filtered.length === 0">
        <svg class="w-8 h-8 opacity-50 mx-auto mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <div class="text-[14px] font-semibold text-ink-2">No reports match</div>
        <div class="text-[12.5px] mt-1">Try a different keyword.</div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportsHub', () => ({
        q: '',
        totalReports: {{ $totalReports }},
        cats: @js($categories),
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.cats;
            return this.cats.map(c => {
                const matchCat = c.name.toLowerCase().includes(q);
                const items = matchCat
                    ? c.items
                    : c.items.filter(i => i.name.toLowerCase().includes(q) || i.desc.toLowerCase().includes(q));
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
            const routes = {
                @foreach($categories as $cat)
                    '{{ $cat['key'] }}': '{{ route('admin.reports.category', $cat['key']) }}',
                @endforeach
            };
            return routes[key] ?? '#';
        },
        get catLabel() {
            const n = this.filtered.length;
            return this.q.trim() ? `${n} ${n===1?'category':'categories'}` : `${this.cats.length} categories`;
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
