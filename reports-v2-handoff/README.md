# Reports module — Claude Code handoff

Builds two pages for Star Unity ERP:
1. **Reports hub** (`/admin/reports`) — featured dashboard banner + masonry of 9 category cards + live Alpine search
2. **Category detail** (`/admin/reports/{category}`) — hero + 3-col report cards + browse pills

**Stack:** Laravel 11 · Tailwind CSS 4 · Alpine.js 3.x (no Livewire — pure navigation)

## Start here
1. Open `ui-reference/Reports.html` and `ui-reference/Report Category.html` — exact designs to match.
2. Read `CLAUDE_CODE_PROMPT.md` — install, how it works, sidebar snippet.
3. Drop files in, import `reports.css`, add routes, `npm run dev`.

## Files
- `app/Http/Controllers/Admin/ReportController.php` — all data + actions (hub + category + stubs)
- `resources/views/admin/reports/index.blade.php` — hub (Alpine live search)
- `resources/views/admin/reports/category.blade.php` — category (server-rendered)
- `resources/css/reports.css` — Tailwind 4 `@theme` + 1:1 ported styles
- `routes/web.php` — route group + sidebar snippet

## Key rules
- All report links stay as `#` until their page exists — swap the `'route'` value in `ReportController::categories()`.
- Sidebar dropdown is Alpine-only (`open: request()->routeIs('admin.reports.*')`).
- "Sales Reports" = **Sales & Rents Reports**.
