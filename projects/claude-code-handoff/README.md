# Project Module — Claude Code Handoff Package

Hand this whole folder to **Claude Code in VS Code** to implement the Project module in your Laravel ERP.

## 📂 Contents

```
claude-code-handoff/
├── CLAUDE_CODE_PROMPT.md          ← START HERE. The full implementation brief.
├── README.md                      ← this file
├── ui-reference/                  ← pixel-accurate HTML mockups (the design source of truth)
│   ├── Project Details.html
│   ├── Project Estimates.html
│   ├── Project Consumption.html
│   ├── Project Expenses.html
│   └── Project Reports.html
├── database/
│   ├── migrations/                ← ready-to-run Laravel migrations
│   │   ├── ...create_projects_table.php
│   │   ├── ...create_project_estimates_tables.php
│   │   └── ...create_project_expenses_tables.php
│   └── seeders/
│       └── ExpenseCategorySeeder.php
└── app/Enums/Projects/            ← typed enums
    ├── ProjectStatus.php
    ├── EstimateStatus.php
    ├── CostType.php
    └── WorkPhase.php
```

## 🚀 How to use with Claude Code

1. Open this folder in VS Code with Claude Code.
2. Tell Claude: **"Read CLAUDE_CODE_PROMPT.md and implement the Project module. Open the ui-reference HTML files in a browser to match the design exactly. Start with the projects table + Details page."**
3. Claude will scaffold migrations, models, controllers, routes, and Blade views.
4. Review the `ui-reference/` mockups side-by-side as Claude builds each page.

## ⚠️ Before running migrations

These tables are **referenced but assumed to already exist** in your ERP — confirm before scaffolding:
- `users`, `media_files`, `materials`, `vendors`
- `accounts` (chart of accounts), `bank_accounts`
- `inventory_transactions` / `stock_movements` (Consumption reads from here)

## 🎨 Design system quick-reference

- Primary: deep navy `#0d2a4a`
- Fonts: Instrument Serif (headings), Inter (body), JetBrains Mono (numbers/codes)
- Currency: `BDT 1,50,000.00`
- Cost-type colours: Material `#0d2a4a` · Labour `#0e7490` · Other `#a16207`

Full details in `CLAUDE_CODE_PROMPT.md` §2.
