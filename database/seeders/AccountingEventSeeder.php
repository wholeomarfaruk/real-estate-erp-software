<?php

namespace Database\Seeders;

use App\Accounting\AccountingEventRegistry;
use App\Models\Account;
use App\Models\AccountingEvent;
use Illuminate\Database\Seeder;

/**
 * Seeds the accounting events + their default posting-rule recipes from the
 * code-owned AccountingEventRegistry. Fixed legs are bound to chart-of-accounts
 * entries by their stable `code`. Idempotent: events are matched on `key` and
 * their rules are (re)created to match the registry default only on first create.
 */
class AccountingEventSeeder extends Seeder
{
    public function run(): void
    {
        $accountIdByCode = Account::query()
            ->whereNotNull('code')
            ->pluck('id', 'code');

        foreach (AccountingEventRegistry::all() as $key => $def) {
            $event = AccountingEvent::query()->updateOrCreate(
                ['key' => $key],
                [
                    'module'           => $def['module'],
                    'name'             => $def['name'],
                    'description'      => $def['description'] ?? null,
                    'transaction_type' => $def['transaction_type'],
                    'is_active'        => true,
                ]
            );

            // Only seed default rules when the event has none yet, so admin edits
            // are never clobbered on re-seed.
            if ($event->rules()->exists()) {
                continue;
            }

            foreach ($def['default_rules'] as $i => $rule) {
                $accountId = null;

                if (($rule['account_source'] ?? 'fixed') === 'fixed') {
                    $code = $rule['account_code'] ?? null;
                    $accountId = $code ? ($accountIdByCode[$code] ?? null) : null;

                    if (! $accountId) {
                        $this->command?->warn("AccountingEventSeeder: account code '{$code}' not found for event '{$key}'. Leg left unbound.");
                    }
                }

                $event->rules()->create([
                    'leg'            => $rule['leg'],
                    'account_source' => $rule['account_source'] ?? 'fixed',
                    'account_id'     => $accountId,
                    'runtime_slot'   => $rule['runtime_slot'] ?? null,
                    'amount_source'  => $rule['amount_source'] ?? 'full',
                    'sort_order'     => $i,
                    'description'    => $rule['description'] ?? null,
                ]);
            }
        }
    }
}
