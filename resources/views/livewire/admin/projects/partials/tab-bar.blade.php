{{--
  Project tab navigation bar.
  Props: $project (Project model), $activeTab (string: details|estimates|consumption|expenses|reports)
  Optional: $estimatesCount, $consumptionCount, $expensesCount
--}}
<nav class="nav-tabs" role="tablist">
  <a class="nav-tab {{ $activeTab === 'details' ? 'active' : '' }}" href="{{ route('admin.projects.details', $project) }}" role="tab">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M10 21V14h4v7"/></svg>
    Details
  </a>
  <a class="nav-tab {{ $activeTab === 'estimates' ? 'active' : '' }}" href="{{ route('admin.projects.estimates', $project) }}" role="tab">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7h6M9 11h6M9 15h4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
    Estimates
    @if(!empty($estimatesCount))
      <span class="pill">{{ $estimatesCount }}</span>
    @endif
  </a>
  <a class="nav-tab {{ $activeTab === 'consumption' ? 'active' : '' }}" href="{{ route('admin.projects.consumption', $project) }}" role="tab">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/><path d="M3 3h18v4H3z"/></svg>
    Consumption
    @if(!empty($consumptionCount))
      <span class="pill">{{ $consumptionCount }}</span>
    @endif
  </a>
  <a class="nav-tab {{ $activeTab === 'expenses' ? 'active' : '' }}" href="{{ route('admin.projects.expenses', $project) }}" role="tab">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    Expenses
    @if(!empty($expensesCount))
      <span class="pill">{{ $expensesCount }}</span>
    @endif
  </a>
  <a class="nav-tab {{ $activeTab === 'reports' ? 'active' : '' }}" href="{{ route('admin.projects.reports', $project) }}" role="tab">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Reports
  </a>
</nav>
