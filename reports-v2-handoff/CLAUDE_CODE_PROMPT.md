# Reports module — Laravel implementation brief

> Hand this folder to **Claude Code**.
> Build two pages: the **Reports hub** and the **Category detail** page.
> Design source of truth: open both files in `ui-reference/` in a browser — the
> rendered Blade must be **pixel-identical**.

---

## Stack (fixed)
- Laravel 11+, PHP 8.2+
- **Tailwind CSS 4** — CSS-first (`@theme` tokens + `@layer components` in `reports.css`)
- **Alpine.js 3.x** — live search on the hub (client-side), accordion in sidebar
- **No Livewire needed** — both pages are read-only navigation with static data

---

## Files in this package

```
app/Http/Controllers/Admin/ReportController.php   ← all category data + hub/category actions
resources/
  css/reports.css                                  ← Tailwind 4 @theme + component classes (1:1 port)
  views/admin/reports/
    index.blade.php                                ← hub page  (Alpine search)
    category.blade.php                             ← category detail (server-rendered)
routes/web.php                                     ← route snippet + sidebar hint
ui-reference/
  Reports.html                                     ← approved hub design
  Report Category.html                             ← approved category design
```

---

## Install

```bash
# 1. Drop files into matching paths.

# 2. Import the CSS in resources/css/app.css:
#       @import "tailwindcss";
#       @import "./reports.css";

# 3. Add Alpine.js to your bundle (if not already):
#       import Alpine from 'alpinejs'
#       window.Alpine = Alpine
#       Alpine.start()
#    OR load via CDN:
#       <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

# 4. Add the routes from routes/web.php to your app's routes/web.php.

# 5. Your layout must render @stack('scripts') near </body>.

npm run dev
```

---

## Pages

### Hub (`/admin/reports`)
- Featured **Dashboard Reports** banner (navy, full-width)
- Masonry of 9 category cards — each has a coloured icon, name, count, "View all →" link, and a list of sub-reports
- **Live Alpine search** — filters both category names and report names, highlights the match, hides empty cards, shows empty state
- `/ ` keyboard shortcut focuses the search input; `Esc` clears it

### Category detail (`/admin/reports/{category}`)
- Hero card: tinted icon (64px), category name, report count, description, "← All reports" back link
- **3-column grid** of report cards — icon + name + description + animated chevron
- "Browse categories" pill row at bottom — active category highlighted, clicking navigates to that route
- `wire:navigate` on category pills = SPA feel via Livewire's navigate

---

## Category data — `ReportController::categories()`

All 9 categories (CRM · Sales & Rents · Finance · Project · Marketing · HR · Inventory · Document · Custom) are defined in **one place**: `ReportController::categories()`. Each report has:
```php
['name' => 'Income Reports', 'desc' => '...', 'route' => '#']
```

**To wire a report** when you build it, just replace `'#'` with `route('your.real.route')`. Nothing else changes. The UI keeps all buttons regardless — this is the golden rule.

---

## Sidebar Reports dropdown

Add the Reports accordion to your **existing** sidebar partial. Use Alpine:
```html
<div x-data="{ open: {{ request()->routeIs('admin.reports.*') ? 'true' : 'false' }} }">
    <button @click="open = !open">
        Reports
        <svg :class="open ? 'rotate-180' : ''"><!-- chevron --></svg>
    </button>
    <div x-show="open" x-transition>
        <a href="{{ route('admin.reports.index') }}" wire:navigate>All Reports</a>
        <a href="{{ route('admin.reports.category','crm') }}" wire:navigate
           class="{{ request()->route('category')==='crm' ? 'font-semibold' : '' }}">CRM</a>
        {{-- repeat for each category --}}
    </div>
</div>
```

See the bottom of `routes/web.php` for the full snippet with all categories.

---

## Do / Don't
- ✅ Keep `reports.css` class names — they guarantee pixel-identical output.
- ✅ Keep all report links even as `#` until their routes exist.
- ✅ Add `@stack('scripts')` to your layout if missing.
- ❌ Don't add Livewire to these pages — Alpine is sufficient and faster for pure navigation.
- ❌ Don't invent colors/fonts — tokens in `reports.css` `@theme` cover everything.
