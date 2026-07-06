# Handoff: Star Unity Development — Customer Portal (Mobile)

> **Domain:** `client.starunitydevelopment.com`
> **Company:** Star Unity Development Ltd (real-estate / apartment developer, Dhaka)
> **This package = UI implementation spec.** Backend is a **later phase** — do the UI first, pixel-for-pixel.

---

## 1. Overview

A customer self-service portal for property buyers: they log in, see their properties, track
installments & payments, download documents, read notices/offers/news, and manage their profile.

The deliverable in this bundle is a set of **design references built in HTML** (a pannable "site map"
of all 15 mobile screens). They show the intended **look and behaviour**, not production code.
Your job is to **recreate these screens exactly** in the target stack below.

**Fidelity: HIGH.** Colors, typography, spacing, radii, and copy are final. Match them precisely.
Use the design-token values in `scss/_tokens.scss` — do not eyeball or invent new values.

---

## 2. Target stack (binding)

| Concern | Choice |
|---|---|
| Framework | **Laravel + Livewire** (each screen → a Livewire component) |
| Interactivity | **Alpine.js 3.6** across the whole app (drawer, tabs, OTP inputs, password toggles, carousel, accordions) |
| Styling | **SCSS (Sass), split into separate files** — see §4. No utility framework, no inline styles. |
| Delivery | **WebView mobile app** (Android WebView + iOS WKWebView) wrapping the responsive web app |

### Build order (strict priority)
1. **UI first** — all 15 screens, static/mock data, fully styled & responsive. ← _start here_
2. Backend wiring (ERP API + website content) — **only after UI is signed off.**

### Device / responsive priority
The app is **mobile-first and stays phone-shaped on every screen size** — it is NOT a
responsive desktop layout.

1. **Mobile WebView** (primary target) — design width **390px**.
2. **iPhone-specific** — respect safe areas (notch/home indicator) via `env(safe-area-inset-*)`.
3. **Tablet** — same phone UI, centered.
4. **Laptop** — same, centered.
5. **Desktop** — same, centered. **Never expand to large screens.**

> **Max-width container rule:** wrap the whole app in a centered container capped at **≈480px**
> (`max-width: 480px; margin-inline: auto`). On wide viewports show a calm page backdrop
> (`--c-bg-mist`) behind the centered column. All layout math assumes the 390px design width.

---

## 3. Design language

- **Font:** `Plus Jakarta Sans` (weights 400/500/600/700/800). Google Fonts.
  Fallback: `system-ui, -apple-system, sans-serif`.
- **Brand green** is primary; **orange** is the secondary accent (CTAs, badges, alerts);
  **gold** is a tertiary accent (2nd property, some offers/news). See tokens.
- Cards: white, 1px hairline border (`--c-border`), soft green-tinted shadow, 14–18px radius.
- Icons: inline SVG, 1.7–2.0 stroke width, `stroke-linecap:round`. (In code, use Lucide or Heroicons
  outline — they match. Icon list per screen in §6.)
- Currency: **BDT `৳`**, Bengali-style grouping (e.g. `৳ 4,50,000`, `৳ 38,50,000`).
- Language: English UI with Bangla-appropriate names/places and an `Assalamu Alaikum` greeting.
- **No emoji** in the UI.

---

## 4. SCSS architecture (binding)

Split styles into **separate `.scss` partials**, compiled from one entry file. Order matters:
**base → layout → components → pages.**

```
scss/
├── main.scss                 # entry: @use all partials in order
│
├── abstracts/
│   ├── _tokens.scss          # colors, type scale, spacing, radius, shadow (provided — see this bundle)
│   ├── _mixins.scss          # helpers (safe-area, card, truncate, screen-container)
│   └── _functions.scss
│
├── base/
│   ├── _reset.scss           # box-sizing, margin reset, font smoothing
│   ├── _typography.scss      # base font, headings, .u-* text helpers
│   └── _globals.scss         # body bg, app container / max-width rule, scrollbar hiding
│
├── layout/
│   ├── _app-shell.scss       # centered ≤480px column, status bar, safe areas
│   ├── _bottom-nav.scss      # fixed 4-tab bar (Dashboard/Offers/News/Menu)
│   ├── _drawer.scss          # slide-out sidebar + scrim
│   └── _screen-header.scss   # hamburger + title + notification bell row
│
├── components/               # reusable, page-agnostic
│   ├── _button.scss          # .btn, .btn--primary (green), .btn--accent (orange), .btn--outline
│   ├── _card.scss            # .card base
│   ├── _stat-strip.scss      # dashboard 3-up stat strip
│   ├── _quick-actions.scss   # 2-row × 4-col icon grid
│   ├── _offer-card.scss      # carousel image card
│   ├── _property-card.scss   # property summary w/ progress bar
│   ├── _progress.scss        # .progress track + fill
│   ├── _list-row.scss        # payment/document/notification rows
│   ├── _timeline.scss        # installment schedule timeline
│   ├── _badge.scss           # status pills (ON SCHEDULE, PAID, PINNED, etc.)
│   ├── _notice-card.scss
│   ├── _otp-input.scss       # 6-box code entry
│   ├── _form-field.scss      # labeled input + focus ring + password eye toggle
│   └── _carousel.scss        # horizontal snap scroller + dots
│
└── pages/                    # only page-specific overrides
    ├── _welcome.scss
    ├── _auth.scss            # login, forgot, otp
    ├── _dashboard.scss
    ├── _properties.scss
    ├── _property-detail.scss
    ├── _payments.scss
    ├── _installments.scss
    ├── _noticeboard.scss
    ├── _offers.scss
    ├── _news.scss
    ├── _notifications.scss
    ├── _profile.scss
    └── _change-password.scss
```

Rules:
- Use the modern Sass module system: `@use 'abstracts/tokens' as *;` in `main.scss`, and
  `@use 'abstracts/tokens' as *;` at the top of any partial that needs tokens.
- **Never hard-code a hex/size that exists as a token.** Reference `$c-*`, `$sp-*`, `$r-*`, etc.
- One component = one partial. Page partials hold only layout glue, not restyled components.
- BEM-ish class naming (`.property-card__progress`, `.btn--accent`). No deep nesting (>3 levels).

---

## 5. Alpine.js 3.6 + Livewire structure

Each **screen is a Livewire component**; shared chrome (drawer, bottom nav, header) are
**Livewire layout partials / small components** included by every authenticated page.

```
resources/views/
├── layouts/
│   └── app.blade.php               # <html>, <head> (fonts, compiled css), app-shell container, @yield
├── components/                     # blade + alpine (dumb UI; no data logic yet in UI phase)
│   ├── bottom-nav.blade.php        # x-data tab state; highlights active
│   ├── drawer.blade.php            # x-data="{open:false}" slide-out
│   ├── screen-header.blade.php     # slot: title; notification bell w/ badge
│   ├── otp-input.blade.php         # x-data 6-cell, auto-advance, paste split
│   ├── password-field.blade.php    # x-data="{show:false}" eye toggle
│   └── offer-carousel.blade.php    # scroll-snap + active dot
└── livewire/                       # one folder-view per screen (mock data in UI phase)
    ├── welcome.blade.php
    ├── auth/login.blade.php
    ├── auth/forgot-password.blade.php
    ├── auth/otp.blade.php
    ├── dashboard.blade.php
    ├── my-properties.blade.php
    ├── property-detail.blade.php
    ├── payment-history.blade.php
    ├── installments.blade.php
    ├── noticeboard.blade.php
    ├── offers.blade.php
    ├── news.blade.php
    ├── notifications.blade.php
    ├── profile.blade.php
    └── change-password.blade.php
```

**Alpine responsibilities (UI phase — no backend):**
- Drawer open/close + body scroll lock + scrim tap-to-close.
- Bottom-nav active tab (from current route).
- OTP: 6 single-char cells, auto-focus next, backspace to previous, paste splits across cells,
  resend countdown timer (`x-data="{t:48}"` ticking down).
- Password fields: show/hide toggle; live checklist (≥8 chars, upper+number, special char).
- Offers carousel: horizontal scroll-snap; update active dot on scroll.
- Filter chips (Payment History): toggle active state.
- Installment property selector ("Heights ▾"): dropdown.

Livewire will own data/actions in the **backend phase** (auth, `wire:model` on inputs, pagination,
API-fed lists). Keep markup Livewire-ready now (semantic, componentized) but wire real data later.

---

## 6. Screens (15)

Design width **390px**, height **844px** (iPhone reference). Screen body padding is **18px** L/R
unless noted. Status bar row (time + signal/battery) sits at top of every screen.

**Auth group (NO bottom nav, NO drawer):**

### 01 · Welcome — `/`
- Full-bleed **green gradient** background (`160deg, #0F6B2E → #1B8A3D → #157C36`), decorative
  translucent circles (one orange-tinted top-right, one white bottom-left).
- Centered: 120×120 white rounded-square (radius 30px) holding the app logo (`sud-logo.png`, 88px).
- Headline (white, 800/27px): "Your home journey, in one place".
- Sub (white 82%, 500/15px): "Track payments, installments, documents and project updates — anytime, anywhere."
- Primary button: **white bg, green text**, 54px tall, radius 15px → "Log in to your account".
- Footer link: "Need help? **Contact support**" (underlined).

### 02 · Login — `/login`
- White screen. Logo (`sud-logo-black.png`, 42px) top-left.
- H1 "Welcome back" (800/26px), sub "Log in to manage your properties & payments."
- Field 1 **Phone or Email** (envelope icon; shown focused = green 1.5px border + soft glow).
- Field 2 **Password** (dots + eye toggle icon).
- "Forgot password?" link right-aligned (green).
- Primary button (green, 54px) "Log In".
- Footer: "Account is created by Star Unity. No public sign-up." (muted, centered).
- **Flow:** Log In → **03 OTP**.

### 03 · Forgot Password — `/forgot-password`
- Back row ("← Back to login").
- 72×72 orange-tint rounded square with lock icon.
- H1 "Reset your password" + sub about sending a secure link.
- Field **Phone or Email** (phone icon).
- Primary button (green) "Send reset link".
- Info box (green tint): "Still stuck? Call **+880 9610-000111** — Sat–Thu, 10am–6pm."

### 04 · OTP Verification — `/login` step 2
- Back row. 72×72 green-tint square w/ keypad-ish icon.
- H1 "Enter verification code", sub "We sent a 6-digit code to **+880 1712-345678** via SMS."
- **6 OTP cells** (flex, gap 9px): filled cells show digit (800/24px) + green border; active cell
  shows a blinking caret; empty cells grey border.
- Primary button (green) "Verify & continue".
- "Didn't get the code? **Resend in 0:48**" (timer).
- Bottom warning box (orange tint): "Never share this code. Star Unity staff will never ask for your OTP."

**Authenticated group (bottom nav + drawer available). Bottom nav = Dashboard · Offers · News · Menu.**

### Slide-out Drawer (shared)
- Left panel **300px**, green (`#0F6B2E`), over a dark scrim.
- Top: white logo (`sud-logo-white.png`), then avatar (orange rounded square initials "RI"),
  name "Md. Rafiqul Islam", "Customer ID · SUD-10472".
- Menu items (active = white pill w/ green text; others white text): Dashboard, My Properties,
  Payment History, Installments, Noticeboard, Offers, Notifications (orange "3" badge), Profile.
- **Fixed bottom** (separated by hairline): **Call us · 09610-000111**, **Email support**,
  **WhatsApp Chat** (WhatsApp glyph), **Logout** (gold text, log-out icon).

### 05 · Dashboard — `/dashboard`
- **Green header** (rounded bottom 26px): row = hamburger · logo (`sud-logo-white.png` ~40px) ·
  bell (orange dot). Greeting "Assalamu Alaikum," + name (800/20px).
- **Stat strip** (translucent white pill inside header): 3 cells — **2** Properties | **৳ 38.5L**
  Total paid | **৳ 23.5L** Outstanding (last value in `--c-accent-soft`).
- **Next-installment card** (white, overlapping header by -16px): "Next installment due" +
  orange "in 6 days" pill; amount **৳ 4,50,000** (800/28px); "Star Unity Heights · Apt B-7 · Due 05 Jul 2026";
  **outline green** button "View installment details →".
- **Quick actions**: 2 rows × 4 icons — Pay now (orange tile), Schedule, Documents, Properties,
  History, Notices, Support, Profile. Tiles 48×48 radius 15px, green-tint bg (Pay now = orange tint).
- **Offers carousel**: "Offers for you" + "See all". Horizontal scroll of **image-only** cards
  (296×152, radius 16px), 2nd card peeks ~16%. (Real banners drop in later.)

### 06 · My Properties — `/my-properties`
- Header row: hamburger · "My Properties" · bell.
- Property cards (radius 18px). Each: gradient banner (green for Heights, gold for Greenview) with
  a status pill (white bg) — "ON SCHEDULE" / "HANDOVER SOON" — building icon watermark, title +
  meta ("Apt B-7 · 1,450 sqft · Bashundhara R/A"). Body: **payment progress** label + % + progress
  bar; Total price vs Outstanding (outstanding in orange). Heights = 62%, Greenview = 88%.

### 07 · Property Detail + Documents — (from My Properties)
- Green hero (188px) with back + ⋮ buttons, title + address.
- Sheet (radius 22px top, overlaps hero) with 3 fact chips: Size / Floor / Facing.
- **Documents** ("4 files"): list rows, each = colored file icon (PDF = red tint, receipt = green
  tint) + name + "PDF · size" + green download icon. Items: Allotment Letter, Sale Agreement (Deed),
  Latest Payment Receipt (+ one more). _Documents live inside My Properties — not a nav item._

### 08 · Payment History — `/payment-history`
- Header: hamburger · "Payment History" · bell.
- Green summary card: "Total paid (all properties)" **৳ 38,50,000**, "14 transactions · since Mar 2024".
- Filter chips: All (active green) / Heights / Greenview.
- Month group labels ("JUNE 2026"). Rows: green-tick icon + title + "date · method · property" +
  amount + green "PAID". Sample rows: Installment #8 (৳4,50,000, bKash), Utility & service charge
  (৳35,000, Bank), Installment #7…

### 09 · Installment Schedule — `/installments`
- Header: hamburger · "Installments" · property selector "Heights ▾".
- Summary card: Paid **8 / 13** | Next due **05 Jul** | Remaining **৳ 23.5L**.
- **Timeline**: paid steps = filled green node + check; **due** step = orange ring node + highlighted
  card (orange tint, "Due in 6 days · 05 Jul 2026", orange "Pay now" button); upcoming = grey nodes,
  muted text. Each row: "Installment #n" + amount + status line.

### 10 · Noticeboard — `/noticeboard`
- Header: hamburger · "Noticeboard" · bell.
- Cards: first is **PINNED · IMPORTANT** (orange accent border) with alert icon; others carry a
  category pill (CONSTRUCTION green / PAYMENT gold). Title + body + date.

### 11 · Offers — `/offers`  *(content from website)*
- Header: hamburger · "Offers" · bell.
- Full offer cards: gradient banner (orange / green) with a badge (SAVE ৳5 LAKH / 0% INTEREST) +
  watermark icon; body = title + description + "Valid till…" + "View details →".

### 12 · News (Blogs) — *(content from website)*
- Header: hamburger · "News & Blogs" · bell.
- Featured card (140px gradient header, "FEATURED" pill) + title + "Star Unity Insights · date · read time".
- Then compact list rows: 78×72 thumb + title + "date · read time".

### 13 · Notifications — `/notifications`
- Header: hamburger · "Notifications" · **"Mark read"** (green). _No bell here (this is the destination)._
- Rows: icon tile (colored by type) + title + body + relative time; unread row = green tint bg +
  orange dot. Types: installment due (orange), payment received (green), new offer (gold),
  construction update (green).

### 14 · Profile — `/profile`
- Green hero: hamburger · "Profile" · edit (pencil) icon. Centered avatar (orange square "RI"),
  name, "Customer ID · SUD-10472".
- Section **Contact**: Phone / Email / Address rows (icon + label + value).
- Section **Identity**: NID row (masked "1990 •••• •••• 4521") + green "VERIFIED" pill.

### 15 · Change Password — `/change-password`
- Back row + "Change Password".
- Fields: Current password / New password (focused-green) / Confirm new password (all with eye toggle).
- **Rule checklist** box: ≥8 chars ✓, one uppercase & one number ✓, one special char (pending).
- Primary button (green) "Update password".

### 16 · My Properties **v2** (Units list) — `/my-properties`
Unit-based list (replaces the progress-bar cards of screen 06). **No progress bar** — quick info only.
- Header: hamburger · "My Properties" · bell.
- Filter chips: All units / Flat / Shop / Parking (unit types).
- Each **unit row**: type-colored thumbnail (Flat = green, Shop = gold, Parking = blue, Hall = violet),
  property name, **unit status** pill (top-right), unit code · type, floor · area, **payment status**
  chip (dot, on the meta line), price, and sale number (e.g. `SALE-0000006`).
- **Two independent status systems** (see below).

### 17 · Unit & Purchase Details — (from screen 16)
- **Image gallery hero** (swipeable, `n / N` counter, dot indicators, back button).
- Property + unit title, unit status pill, unit type/floor line + payment status chip.
- **Horizontal scrolling tab slider** (Overview · Gallery · Documents · Invoice · Payments … add more;
  a right-arrow affordance sits pinned at the end). Tabs are chips, NOT stretched to fit.
- Quick-info chips: Area / Floor / Total area.
- **Purchase amount breakdown** table: Sale amount, Discount, Tax, Service charge, Utility charge,
  Down payment %, and a highlighted **Net amount** row (green).
- **Sales Agreement / Invoice** document row (sale number + PDF + download).

---

## 6b. Status model (two independent dimensions)

Units carry **two separate statuses** — render them as visually distinct elements so they don't read
as one thing:

| Dimension | Where | Style | Values |
|---|---|---|---|
| **Unit status** (lifecycle/tenure) | top-right **solid-tint pill** | filled tint by status | `Booked` (orange) · `On Installment` (green) · `Purchased` (green) · `Handover` (gold) · `Rented` (violet) |
| **Payment status** (the money) | meta line **outlined chip + colored dot** | white bg, hairline border, colored dot | `Paid` (green dot) · `Partial` (gold dot) · `Pending` (orange dot) |

Keep unit-status labels short so long property names still fit on one line (property name truncates
with an ellipsis; the status pill is `flex-shrink:0`).

---

## 7. Interactions & behaviour

- **Navigation:** bottom nav routes to Dashboard / Offers / News / **Menu** (Menu opens the drawer).
  Drawer items route to their pages; Support items = tel:/mailto:/WhatsApp deep links; Logout ends session.
- **Header bell** → Notifications. Badge dot shows when unread > 0.
- **Drawer:** slide-in from left (~240ms ease), scrim fade, body scroll lock, close on scrim tap / back.
- **OTP:** auto-advance, backspace-back, paste-split, resend countdown; "Verify" enabled when 6 filled.
- **Password:** eye toggle per field; live rule checklist turns green as rules pass.
- **Carousel:** scroll-snap, dots reflect index.
- **Filter chips / property selector:** toggle & filter the list.
- **Empty/loading/error states** (backend phase): skeleton rows for lists, empty illustrations for
  no-data, inline field errors (red text under field) for forms.

---

## 8. Assets

In `assets/` of this bundle:
- `sud-logo.png` — full-color logo (Welcome tile).
- `sud-logo-black.png` — dark logo (Login, light headers).
- `sud-logo-white.png` — white logo (green headers, drawer).

Icons: use an outline icon set (Lucide / Heroicons outline) — see per-screen icon cues above.
Offer/News images: **placeholders** now; real banners come from the website in the backend phase.

---

## 9. Data sources (backend phase — reference only)

- **ERP API** → auth/OTP, properties, payments, installments, documents, profile, notices, notifications.
- **Website (starunitydevelopment.com)** → Offers and News/Blogs.
- Same responsive screens render inside Android WebView & iOS WKWebView.

---

## 10. Files in this bundle

- `README.md` — this spec.
- `scss/_tokens.scss` — **the exact design tokens** (import these; don't retype hex values).
- `scss/main.scss` — entry file showing the `@use` order.
- `scss/_mixins.scss` — helper mixins (screen container, safe-area, card, hide-scrollbar).
- `design-reference/Mobile Site Map.dc.html` — the full visual reference (all 15 screens).
- `design-reference/BottomNav.dc.html` — bottom-nav reference.
- `design-reference/support.js` — runtime for opening the reference files in a browser.
- `assets/` — logos.

**How to view the reference:** open `design-reference/Mobile Site Map.dc.html` in a browser
(it self-loads `support.js`). Pan/zoom to inspect any screen. Treat it as the source of truth for
spacing, color, and copy — measure against the tokens file.
