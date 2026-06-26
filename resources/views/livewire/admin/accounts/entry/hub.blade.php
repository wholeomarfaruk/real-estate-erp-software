<div class="max-w-[1100px] mx-auto px-4 py-6">
    <style>
        :root {
            --gap: 0.75rem;
            --padding: 1rem;
        }
        .entry-hub { display: flex; flex-direction: column; gap: calc(var(--gap) * 2); }
        .entry-breadcrumb { font-size: 11.5px; font-weight: 500; color: #999; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .entry-breadcrumb a { color: #666; text-decoration: none; }
        .entry-breadcrumb a:hover { color: #333; }
        .entry-breadcrumb span { opacity: 0.5; }
        .entry-header { margin-bottom: 1.5rem; }
        .entry-header h1 { font-size: 26px; font-weight: 600; margin: 0 0 0.75rem 0; }
        .entry-header-sub { font-size: 12px; color: #666; font-family: monospace; display: flex; align-items: center; gap: 0.5rem; }
        .entry-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; }
        .entry-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; cursor: pointer; transition: all 0.2s; }
        .entry-card:hover { border-color: #999; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .entry-card-header { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.75rem; }
        .entry-card-icon { width: 24px; height: 24px; flex-shrink: 0; opacity: 0.6; }
        .entry-card-title { font-size: 14px; font-weight: 600; color: #333; }
        .entry-card-desc { font-size: 12px; color: #777; margin-top: 0.25rem; }
        .entry-card-count { font-size: 11px; color: #999; font-family: monospace; margin-top: 0.5rem; }
        .entry-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #f0f0f0; }
        .entry-card-link { font-size: 12px; font-weight: 600; color: #0066cc; text-decoration: none; display: flex; align-items: center; gap: 0.35rem; }
        .entry-card-link:hover { color: #0052a3; }
        .entry-card-link svg { width: 14px; height: 14px; }
    </style>

    <div class="entry-hub">
        {{-- Breadcrumb --}}
        <div class="entry-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Admin</a>
            <span>/</span>
            <span>Account Entries</span>
        </div>

        {{-- Header --}}
        <div class="entry-header">
            <h1>Account Entries</h1>
            <div class="entry-header-sub">
                <span>Create and manage accounting entries</span>
            </div>
        </div>

        {{-- Category Grid --}}
        <div class="entry-grid">
            @foreach ($categories as $categoryKey => $categoryData)
                @if ($categoryData->items->isNotEmpty())
                    <a href="{{ route('admin.account-entries.category', $categoryKey) }}" class="entry-card">
                        <div class="entry-card-header">
                            @if ($categoryData->icon)
                                <div class="entry-card-icon">
                                    {!! $categoryData->icon !!}
                                </div>
                            @endif
                            <div class="flex-1">
                                <div class="entry-card-title">{{ $categoryData->title }}</div>
                                <div class="entry-card-desc">{{ $categoryData->description }}</div>
                            </div>
                        </div>
                        <div class="entry-card-count">{{ $categoryData->items->count() }} entry types</div>
                        <div class="entry-card-footer">
                            <span class="entry-card-link">
                                View all
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </span>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
