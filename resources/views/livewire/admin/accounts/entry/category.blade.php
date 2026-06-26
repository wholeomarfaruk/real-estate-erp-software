<div class="max-w-[1100px] mx-auto px-4 py-6">
    <style>
        :root {
            --gap: 0.75rem;
            --padding: 1rem;
        }
        .entry-category { display: flex; flex-direction: column; gap: calc(var(--gap) * 2); }
        .entry-breadcrumb { font-size: 11.5px; font-weight: 500; color: #999; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .entry-breadcrumb a { color: #666; text-decoration: none; }
        .entry-breadcrumb a:hover { color: #333; }
        .entry-breadcrumb span { opacity: 0.5; }
        .entry-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .entry-hero h1 { font-size: 28px; font-weight: 700; margin: 0 0 0.5rem 0; }
        .entry-hero p { font-size: 13px; opacity: 0.9; margin: 0; }
        .entry-header h1 { font-size: 20px; font-weight: 600; margin: 0 0 1rem 0; }
        .entry-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: var(--gap); }
        .entry-item { background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; display: flex; flex-direction: column; }
        .entry-item:hover { border-color: #0066cc; box-shadow: 0 3px 10px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .entry-icon { width: 28px; height: 28px; margin-bottom: 0.75rem; opacity: 0.7; }
        .entry-title { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 0.25rem; }
        .entry-desc { font-size: 11px; color: #777; line-height: 1.4; margin-bottom: auto; }
        .entry-arrow { font-size: 12px; color: #0066cc; margin-top: 0.75rem; font-weight: 600; }
        .entry-pills { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #f0f0f0; }
        .entry-pill { padding: 0.4rem 0.75rem; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; font-size: 11px; font-weight: 500; color: #666; text-decoration: none; cursor: pointer; transition: all 0.2s; }
        .entry-pill:hover { background: #efefef; border-color: #999; }
        .entry-pill.active { background: #0066cc; color: white; border-color: #0066cc; }
    </style>

    <div class="entry-category">
        {{-- Breadcrumb --}}
        <div class="entry-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Admin</a>
            <span>/</span>
            <a href="{{ route('admin.account-entries.index') }}">Account Entries</a>
            <span>/</span>
            <span>{{ $categoryData->title }}</span>
        </div>

        {{-- Hero Section --}}
        <div class="entry-hero">
            <h1>{{ $categoryData->title }}</h1>
            <p>{{ $categoryData->description }}</p>
        </div>

        {{-- Entry Types Grid --}}
        <div class="entry-grid">
            @forelse ($categoryData->items as $entry)
                @if ($entry->enabled && $entry->visible)
                    <a href="{{ route('admin.account-entries.form', ['category' => $entry->categoryKey, 'slug' => $entry->slug]) }}" class="entry-item">
                        @if ($entry->icon)
                            <div class="entry-icon">
                                {!! $entry->icon !!}
                            </div>
                        @endif
                        <div class="entry-title">{{ $entry->title }}</div>
                        <div class="entry-desc">{{ $entry->description }}</div>
                        <div class="entry-arrow">Create →</div>
                    </a>
                @endif
            @empty
                <div style="grid-column: 1/-1; padding: 2rem; text-align: center; color: #999;">
                    No entry types available in this category.
                </div>
            @endforelse
        </div>

        {{-- Category Pills --}}
        <div class="entry-pills">
            <span style="font-size: 11px; font-weight: 600; color: #333; margin-right: 0.5rem;">Browse:</span>
            @foreach ($allCategories as $cat)
                <a href="{{ route('admin.account-entries.category', $cat->key) }}"
                   class="entry-pill @if ($cat->key === $categoryData->key) active @endif">
                    {{ $cat->title }}
                </a>
            @endforeach
        </div>
    </div>
</div>
