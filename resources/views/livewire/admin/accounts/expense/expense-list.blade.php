{{-- Expense Module Dashboard — Quick Expense Entry --}}
<div class="min-h-screen bg-linear-to-br from-slate-50 to-slate-100 p-6"
    x-data x-init="$store.pageName = { name: 'Expenses', slug: 'accounts' }">

<style>
  :root {
    --accent: #0d2a4a;
    --accent-soft: #eaf0f8;
    --text-primary: #14181f;
    --text-secondary: #6b7280;
    --border-color: #e4e4e7;
    --bg-primary: #fff;
  }

  .expense-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    font-family: "Inter", system-ui, sans-serif;
  }

  .page-header {
    margin-bottom: 40px;
  }

  .page-header h1 {
    font-family: "Instrument Serif", Georgia, serif;
    font-size: 32px;
    font-weight: 400;
    color: var(--text-primary);
    margin: 0 0 8px 0;
  }

  .page-header p {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0;
  }

  .category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
  }

  .category-card {
    background: var(--bg-primary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 24px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .category-card:hover {
    border-color: var(--accent);
    box-shadow: 0 12px 24px -8px rgba(13, 42, 74, 0.15);
    transform: translateY(-2px);
  }

  .card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 24px;
  }

  .card-icon.project {
    background: #dbeafe;
    color: #1e40af;
  }

  .card-icon.office {
    background: #ede9fe;
    color: #6d28d9;
  }

  .card-icon.marketing {
    background: #fed7aa;
    color: #b45309;
  }

  .card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 12px 0;
  }

  .card-description {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0 0 20px 0;
    flex: 1;
  }

  .card-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    align-self: flex-start;
  }

  .card-action:hover {
    background: #0a1f35;
    box-shadow: 0 4px 12px rgba(13, 42, 74, 0.25);
  }

  .card-action:active {
    transform: scale(0.98);
  }

  .icon-svg {
    width: 16px;
    height: 16px;
  }
</style>

<div class="expense-dashboard">
  {{-- Page Header --}}
  <div class="page-header">
    <h1>Expenses</h1>
    <p>Quick expense entry by category. Select a category below to add a new expense.</p>
  </div>

  {{-- Category Cards Grid --}}
  <div class="category-grid">
    @foreach($expenseCategories as $category)
    <div class="category-card">
      {{-- Icon --}}
      <div class="card-icon {{ $category['slug'] }}">
        @if($category['slug'] === 'project')
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21h18M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7M3 7V5a2 2 0 012-2h14a2 2 0 012 2v2"/><rect x="7" y="3" width="10" height="4"/>
          </svg>
        @elseif($category['slug'] === 'office')
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><path d="M16 3v4M8 3v4M3 11h18"/>
          </svg>
        @elseif($category['slug'] === 'marketing')
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 9c0-1.657 1.343-3 3-3h2.5M23 9c0-1.657-1.343-3-3-3h-2.5M10 6.5v11m4-11v11M4 13v-2m12 2v-2"/>
          </svg>
        @endif
      </div>

      {{-- Title --}}
      <h3 class="card-title">{{ $category['name'] }}</h3>

      {{-- Description --}}
      <p class="card-description">{{ $category['description'] }}</p>

      {{-- Action Button --}}
      <a href="{{ route($category['route']) }}" class="card-action">
        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Expense
      </a>
    </div>
    @endforeach
  </div>
</div>

</div>
