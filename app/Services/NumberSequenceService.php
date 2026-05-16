<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Transaction-safe sequential number generator.
 *
 * Usage (always call inside an active DB::transaction):
 *   $no = app(NumberSequenceService::class)->next('FR');   // FR-000001
 *   $no = app(NumberSequenceService::class)->next('PI');   // PI-000001
 *
 * Supported prefixes (pre-seeded):
 *   PO      → Purchase Orders      (PO-000001 …)
 *   SR      → Stock Receives       (SR-000001 …)
 *   PI      → Purchase Invoices    (PI-000001 …)
 *   FR      → Fund Releases        (FR-000001 …)
 *   PI-PMT  → Purchase Inv. Payments (PI-PMT-000001 …)
 *   PAY     → Generic Payments     (PAY-000001 …)
 *
 * How it works:
 *   1. insertOrIgnore ensures a sequence row exists for the prefix.
 *   2. lockForUpdate serialises concurrent transactions on that row.
 *   3. last_number is incremented and persisted atomically.
 *
 * Never call this outside a DB::transaction — lockForUpdate has no effect
 * on autocommit queries and concurrent calls will produce duplicates.
 */
class NumberSequenceService
{
    /**
     * Reserve and return the next formatted number for the given prefix.
     *
     * @param  string  $prefix   e.g. 'FR', 'PI', 'PO'
     * @param  int     $padding  zero-pad width (default 6 → FR-000001)
     */
    public function next(string $prefix, int $padding = 6): string
    {
        // Guarantee the row exists before we lock it.
        // insertOrIgnore is safe under concurrent calls: the unique constraint
        // on `prefix` means exactly one insert wins; the rest are silently dropped.
        DB::table('number_sequences')->insertOrIgnore([
            'prefix'      => $prefix,
            'last_number' => 0,
            'padding'     => $padding,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Exclusive row lock — serialises concurrent transactions.
        $seq = DB::table('number_sequences')
            ->where('prefix', $prefix)
            ->lockForUpdate()
            ->first();

        $next = (int) $seq->last_number + 1;

        DB::table('number_sequences')
            ->where('prefix', $prefix)
            ->update([
                'last_number' => $next,
                'updated_at'  => now(),
            ]);

        return $prefix . '-' . str_pad((string) $next, (int) $seq->padding, '0', STR_PAD_LEFT);
    }

    /**
     * Peek at the next number without reserving it (for pre-filling forms).
     * Not transaction-safe — use only for display / suggestions.
     */
    public function peek(string $prefix, int $padding = 6): string
    {
        $seq = DB::table('number_sequences')
            ->where('prefix', $prefix)
            ->first();

        $next = $seq ? (int) $seq->last_number + 1 : 1;
        $pad  = $seq ? (int) $seq->padding : $padding;

        return $prefix . '-' . str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
