# How to use this handoff

## 1. Get the bundle into your project

Download the zip (link below the chat), unzip it, and drop the `design_handoff_properties_module/` folder at the **root of your Laravel project** — next to `app/`, `database/`, `resources/`.

Your project tree should now look like:

```
your-laravel-project/
├── app/
├── bootstrap/
├── config/
├── database/
├── design_handoff_properties_module/      ← this bundle
│   ├── design/
│   │   ├── Properties.html
│   │   └── Property Detail.html
│   ├── migrations/
│   ├── PROMPT_FOR_CLAUDE_CODE.md
│   ├── README.md
│   └── USAGE.md                            ← you are here
├── resources/
├── routes/
└── ...
```

## 2. Open Claude Code

Open Claude Code in the project root:

```bash
cd /path/to/your-laravel-project
claude
```

## 3. Paste the prompt

Open `design_handoff_properties_module/PROMPT_FOR_CLAUDE_CODE.md`, copy everything below the horizontal rule (`---`), and paste it into the Claude Code session.

Claude Code can read the README, migrations, and HTML mocks directly from disk because they're inside the project — you don't need to attach them, just point at the folder.

## 4. Review before each big step

The prompt asks Claude Code to:

1. Inspect existing tables first, then show you a reconciliation plan.
2. Wait for your approval before running migrations.
3. Walk you through any decisions instead of editing silently.

Stay in the loop on those steps — confirm the table-rename decision, the layout it picks up, and the CSS approach (raw hex vs. existing Tailwind tokens). Claude Code is good at this kind of step-by-step build but better when it has your input on the small choices.

## 5. After it's done

Visit `/properties` and `/properties/{id}` in the browser. The verification checklist is at the end of `PROMPT_FOR_CLAUDE_CODE.md` — Claude Code is told to run through it once everything compiles.
