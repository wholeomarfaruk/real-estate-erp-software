# Notification System — Enum + DB/Push/API Delivery

## Context

The ERP needs a unified notification system: a `notifications` content table plus a polymorphic `notification_recipients` table (Laravel-way, via `morphs()`) so one message can fan out to many recipients without duplicating text, a `NotificationType` enum instead of a free-text `type` column, and three delivery surfaces — an in-app notification bar (admin panel), a REST API feed (separate client webapp), and web push (browser push notifications, both surfaces).

Nothing here exists yet: no `notifications` table, no `app/Notifications/`, no push infrastructure (no VAPID keys, no service worker, no Pusher/FCM). This is new territory, built to match two patterns already established in the codebase:
- **Enum style**: `app/Enums/Accounts/TransactionType.php` + `ReportGroup.php` — backed string enum, `label()`, `badgeClass()`, a small grouping enum via `reportGroup()`, and static collection-filter helpers (`receipts()`, `payments()`, `neutral()`).
- **Polymorphic attachment style**: `app/Models/Fileable.php` + `app/Models/Concerns/HasFiles.php` — a dedicated pivot-like model plus a trait applied to whichever models can own the relation.

**Recipient scoping decision**: confirmed with user — true multi-model polymorphism. `recipient_type` can be `App\Models\User`, `App\Models\Customer`, or `App\Models\Employee` directly (not funneled through User only). Because `Customer`/`Employee` are *profile* tables (not independently authenticatable — auth is one `User` model with Sanctum + Spatie roles), delivery code (push, API "my notifications") must resolve back to the owning `User` row. This resolver + an aggregator query scope are the main non-obvious pieces of this plan.

## 1. Enum: `NotificationType`

New file `app/Enums/Notification/NotificationType.php` (new domain folder, same convention as `Accounts`/`Property`):

- Backed string enum, cases: `INVOICE_DUE`, `INVOICE_PAID`, `PAYMENT_RECEIVED`, `LEAVE_APPROVED`, `SALARY_GENERATED`, `ATTENDANCE`, `OFFER`, `NEWS`, `BLOG`, `SYSTEM` (values match the snake_case strings from the requirements table).
- `label(): string` — match expression, human-readable, mirrors `TransactionType::label()`.
- `reportGroup(): NotificationGroup` — new small grouping enum `app/Enums/Notification/NotificationGroup.php` (mirrors `Accounts/ReportGroup.php`), grouping cases into `FINANCIAL` (invoice_due, invoice_paid, payment_received, salary_generated), `HR` (leave_approved, attendance), `MARKETING` (offer, news, blog), `SYSTEM` (system).
- Static helpers `financial()`, `hr()`, `marketing()` built the same `collect(self::cases())->filter(...)->map(fn($c) => $c->value)->toArray()` way as `TransactionType::receipts()`/`payments()`/`neutral()`.
- `defaultBadgeClass(): string` — optional match-expression fallback (Tailwind classes, same convention as `badgeClass()`) used only when the caller doesn't supply an explicit `badge` value.

## 2. Migration: two tables (content vs. per-recipient state)

Split into a **message-content table** and a **thin per-recipient tracking table**, so a broadcast (e.g. one "offer" sent to 5,000 clients) stores the title/body/etc. **once**, not once per recipient. This is the key change from the first draft — the original one-table design duplicates the full body text on every recipient row, which doesn't scale for broadcast-style types (`offer`, `news`, `blog`, `system`).

**`notifications`** (content — one row per message, whether sent to 1 person or 1 million):
- `id`.
- `type` (string) — cast to `NotificationType` enum at the model level (`protected $casts`, matching `Transaction.php`'s style).
- `title` (string), `body` (text).
- `badge` (string, nullable) — free text as in the original draft (normal/high/urgent), not its own enum.
- `action_url` (string, nullable) — fix the trailing-space typo from the draft schema.
- `timestamps()`.

**`notification_recipients`** (per-recipient delivery/read state — polymorphic, mirrors `Fileable`):
- `id`.
- `notification_id` (foreign key → `notifications.id`, cascade on delete).
- `morphs('recipient')` → `recipient_type` + `recipient_id` (can be `User`, `Customer`, or `Employee`).
- `is_read` (boolean, default false), `read_at` (timestamp, nullable).
- `timestamps()`.
- Composite index `(recipient_type, recipient_id, is_read)` for fast "unread for me" queries, in addition to the default morphs index.

This means: an individual event (invoice due for one customer) creates 1 `notifications` row + 1 `notification_recipients` row. A broadcast (offer to all clients) creates 1 `notifications` row + N cheap `notification_recipients` rows via a bulk insert — no text duplication, and editing/auditing the message content only ever touches one row.

## 3. Models

- **`app/Models/Notification.php`** (new, content model): `$fillable` (`type`, `title`, `body`, `badge`, `action_url`), `$casts` (`type` → `NotificationType::class`), `recipients(): HasMany` → `NotificationRecipient`.
- **`app/Models/NotificationRecipient.php`** (new, mirrors `Fileable`): `$fillable` (`notification_id`, `recipient_type`, `recipient_id`, `is_read`, `read_at`), `$casts` (`is_read` → `boolean`), `notification(): BelongsTo`, `recipient(): MorphTo`.
  - `scopeUnread($query)`.
  - `scopeForUser($query, User $user)` — **the aggregator**: since `recipient` can be `User`, `Customer`, or `Employee`, this OR's together morph conditions for `User::class` + `$user->id`, `Customer::class` + `$user->customer?->id`, and `Employee::class` + `$user->employee?->id`. Both the Livewire bell and the client API list use this (with `->with('notification')`) to fetch "everything addressed to me" regardless of which model it was actually attached to.
  - Requires adding an `employee(): HasOne` relation to `App\Models\User` (only `customer()` exists today) so the aggregator can resolve both directions symmetrically.
- **`app/Models/Concerns/HasNotifications.php`** (new trait, mirrors `HasFiles`): applied to `User`, `Customer`, `Employee`.
  - `notificationRecipients(): MorphMany` → `NotificationRecipient::class`, `'recipient'`.
  - `unreadNotifications(): MorphMany` (cloned with `where('is_read', false)`).
- **Resolver for push/auth delivery**: a `notifyingUser(): ?User` method added alongside the trait — returns `$this` on `User`, `$this->user` on `Customer`/`Employee`. Used only by the dispatcher to know which `User` row actually owns any push subscriptions.

## 4. Dispatch service

- **`app/Services/Notification/NotificationDispatcher.php`** (new) — single entry point other domains call (invoicing, leave, payroll, attendance, marketing/blog publishing). Two methods:
  - `sendToOne($recipient, NotificationType $type, string $title, string $body, ?string $actionUrl = null, ?string $badge = null)` — creates 1 `Notification` + 1 `NotificationRecipient`, then resolves `$recipient->notifyingUser()` and, if found, queues the push (below). Used for per-person events: invoice due, payment received, leave approved, salary generated, attendance.
  - `sendToMany(iterable $recipients, NotificationType $type, string $title, string $body, ?string $actionUrl = null, ?string $badge = null)` — creates 1 `Notification`, then bulk-inserts `NotificationRecipient` rows (chunked `insert()`, bypassing Eloquent events for speed), then queues push notifications for each resolved `User` in chunks. Used for broadcast types: offer, news, blog, system.
- **`app/Notifications/PushNotification.php`** (new, real Laravel `Notification` class, `implements ShouldQueue` — queue is already configured, `QUEUE_CONNECTION=database`, precedent: `app/Jobs/SendMessageJob.php`).
  - `via()` → `['webpush']`.
  - `toWebPush()` builds the payload (title, body, action_url, badge/icon) from the `Notification` (content) instance passed into the constructor.

## 5. Delivery surface 1 — in-app notification bar (admin panel)

- New Livewire component (project already uses `livewire/livewire` ^3.6) — bell icon + unread count + dropdown, `wire:poll` for near-live updates, mark-as-read on click (sets `is_read`/`read_at`).
- Query via `NotificationRecipient::forUser(auth()->user())->with('notification')->latest()->paginate(...)`.
- Mount into the existing admin layout's header/topbar partial (identify the exact blade file at implementation time — same layout `SidebarController` renders into).

## 6. Delivery surface 2 — REST API for the client webapp

- New `app/Http/Controllers/Api/Client/NotificationController.php`, following the exact shape of existing `ClientDashboardController`/`ClientSidebarController` (thin controller, `auth:sanctum` + `client` middleware, no versioned prefix — matches current `routes/api.php` convention).
- Routes added inside the existing protected group in `routes/api.php` (`Route::middleware(['auth:sanctum','client'])`):
  - `GET client/notifications` — paginated, `NotificationRecipient::forUser($request->user())->with('notification')`.
  - `GET client/notifications/unread-count`.
  - `POST client/notifications/{notification}/read`.
  - `POST client/notifications/mark-all-read`.

## 7. Delivery surface 3 — web push (admin panel + client webapp)

- `composer require laravel-notification-channels/webpush` — VAPID-based (web standard), no third-party account (no FCM/OneSignal dependency), integrates directly with the `PushNotification` class above via `via() => ['webpush']`.
- Publish package migration (adds a `push_subscriptions` table, polymorphic — attach `HasPushSubscriptions` trait to **`User`** only, since that's the one authenticatable/session-bound entity across both surfaces).
- Generate VAPID keys (`php artisan webpush:vapid`) → `.env` (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`) → `config/webpush.php`.
- **Admin panel** (session-based Blade):
  - `public/service-worker.js` handling `push` + `notificationclick` events.
  - Small JS in the admin layout: request `Notification` permission → register service worker → `PushManager.subscribe` with the VAPID public key → POST the subscription to a new web route (session auth) that calls `$request->user()->updatePushSubscription(...)`.
- **Client webapp** (separate frontend, Sanctum token):
  - New `POST api/client/push-subscriptions` (auth:sanctum + client) doing the same `updatePushSubscription()` call — the client webapp's own frontend implements its own service worker + subscribe JS and calls this endpoint with its bearer token. Document the expected payload (`endpoint`, `keys.p256dh`, `keys.auth`) so that team can integrate without backend guesswork.

## Verification

1. Tinker: `NotificationDispatcher::sendToOne($customer, NotificationType::INVOICE_DUE, 'Invoice Due', 'Invoice #123 is due', actionUrl: '/invoices/123')` → assert 1 `notifications` row and 1 `notification_recipients` row (`recipient_type = Customer::class`) are created.
2. Tinker: `NotificationDispatcher::sendToMany(Customer::all(), NotificationType::OFFER, 'New Offer', '10% off this month')` → assert exactly 1 `notifications` row exists but `notification_recipients` has one row per customer.
3. Confirm `NotificationRecipient::forUser($user)` returns the right rows when `$user` is the `Customer`'s linked `User`.
4. Call `GET /api/client/notifications` with a real client Sanctum token → rows appear (with `notification` content eager-loaded) in the paginated response; `POST /api/client/notifications/{id}/read` flips `is_read` on the recipient row only (content row untouched).
5. In a browser, load the admin panel, grant notification permission, confirm a `push_subscriptions` row is created; run `NotificationDispatcher::sendToOne(...)` from tinker and confirm an OS-level push notification arrives (check service worker registration in dev tools; confirm the queued job processes cleanly via `php artisan queue:work`).
6. Confirm the Livewire bell shows the unread count, lists the notification, and marking it read updates the badge without a full page reload.
