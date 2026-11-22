<?php

use App\Enums\Accounts\TransactionType;
use App\Enums\Accounts\ReportGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-sided (double entry) account sources per Journal Posting item.
 *   debit_source / credit_source: "all" | "cashbank" | "feature:<feature key>"
 *
 * Existing items are converted from the old "head / other side" model:
 *   head side  = Credit for receipt types, Debit otherwise  → feature (if set) or all accounts
 *   other side = all accounts for journals, cash & bank otherwise
 * Default accounts in `behaviour` move the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->string('debit_source', 120)->nullable()->after('feature_type');
            $table->string('credit_source', 120)->nullable()->after('debit_source');
        });

        foreach (DB::table('journal_posting_items')->get() as $item) {
            $type = TransactionType::tryFrom((string) $item->transaction_type) ?? TransactionType::EXPENSE;
            $side = $type->reportGroup();
            $isJournal = $side === ReportGroup::NEUTRAL && $type !== TransactionType::TRANSFER;

            $head = $item->feature_type ? 'feature:' . $item->feature_type : 'all';
            $other = $isJournal ? 'all' : 'cashbank';
            $headIsCredit = $side === ReportGroup::RECEIPT;

            $behaviour = json_decode((string) $item->behaviour, true) ?: [];
            $headDefault = $behaviour['default_head_account_id'] ?? null;
            $otherDefault = $behaviour['default_other_account_id'] ?? null;
            unset($behaviour['default_head_account_id'], $behaviour['default_other_account_id']);
            $behaviour['default_debit_account_id'] = $headIsCredit ? $otherDefault : $headDefault;
            $behaviour['default_credit_account_id'] = $headIsCredit ? $headDefault : $otherDefault;

            // Contra (transfer) items move money between cash & bank on both sides.
            $isTransfer = $type === TransactionType::TRANSFER && ! $item->feature_type;

            DB::table('journal_posting_items')->where('id', $item->id)->update([
                'debit_source'  => $isTransfer ? 'cashbank' : ($headIsCredit ? $other : $head),
                'credit_source' => $isTransfer ? 'cashbank' : ($headIsCredit ? $head : $other),
                'behaviour'     => json_encode($behaviour),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->dropColumn(['debit_source', 'credit_source']);
        });
    }
};
