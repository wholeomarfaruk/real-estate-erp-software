<?php

use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        $hasCode = Schema::hasColumn('suppliers', 'code');
        $hasCompanyName = Schema::hasColumn('suppliers', 'company_name');
        $hasAlternatePhone = Schema::hasColumn('suppliers', 'alternate_phone');
        $hasTradeLicenseNo = Schema::hasColumn('suppliers', 'trade_license_no');
        $hasTinNo = Schema::hasColumn('suppliers', 'tin_no');
        $hasBinNo = Schema::hasColumn('suppliers', 'bin_no');
        $hasOpeningBalance = Schema::hasColumn('suppliers', 'opening_balance');
        $hasOpeningBalanceType = Schema::hasColumn('suppliers', 'opening_balance_type');
        $hasPaymentTermsDays = Schema::hasColumn('suppliers', 'payment_terms_days');
        $hasCreditLimit = Schema::hasColumn('suppliers', 'credit_limit');
        $hasIsBlocked = Schema::hasColumn('suppliers', 'is_blocked');
        $hasNotes = Schema::hasColumn('suppliers', 'notes');
        $hasCreatedBy = Schema::hasColumn('suppliers', 'created_by');
        $hasUpdatedBy = Schema::hasColumn('suppliers', 'updated_by');
        $hasSoftDeletes = Schema::hasColumn('suppliers', 'deleted_at');

        Schema::table('suppliers', function (Blueprint $table) use (
            $hasCode,
            $hasCompanyName,
            $hasAlternatePhone,
            $hasTradeLicenseNo,
            $hasTinNo,
            $hasBinNo,
            $hasOpeningBalance,
            $hasOpeningBalanceType,
            $hasPaymentTermsDays,
            $hasCreditLimit,
            $hasIsBlocked,
            $hasNotes,
            $hasCreatedBy,
            $hasUpdatedBy,
            $hasSoftDeletes
        ): void {
            if (! $hasCode) {
                $table->string('code', 100)->nullable()->unique()->after('id');
            }

            if (! $hasCompanyName) {
                $table->string('company_name')->nullable()->after('name');
            }

            if (! $hasAlternatePhone) {
                $table->string('alternate_phone', 50)->nullable()->after('phone');
            }

            if (! $hasTradeLicenseNo) {
                $table->string('trade_license_no', 100)->nullable()->after('address');
            }

            if (! $hasTinNo) {
                $table->string('tin_no', 100)->nullable()->after('trade_license_no');
            }

            if (! $hasBinNo) {
                $table->string('bin_no', 100)->nullable()->after('tin_no');
            }

            if (! $hasOpeningBalance) {
                $table->decimal('opening_balance', 14, 2)->default(0)->after('bin_no');
            }

            if (! $hasOpeningBalanceType) {
                $table->string('opening_balance_type', 20)->default(Supplier::OPENING_BALANCE_TYPE_PAYABLE)->after('opening_balance');
            }

            if (! $hasPaymentTermsDays) {
                $table->unsignedInteger('payment_terms_days')->default(0)->after('opening_balance_type');
            }

            if (! $hasCreditLimit) {
                $table->decimal('credit_limit', 14, 2)->default(0)->after('payment_terms_days');
            }

            if (! $hasIsBlocked) {
                $table->boolean('is_blocked')->default(false)->after('status');
                $table->index(['status', 'is_blocked']);
            }

            if (! $hasNotes) {
                $table->text('notes')->nullable()->after('is_blocked');
            }

            if (! $hasCreatedBy) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }

            if (! $hasUpdatedBy) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! $hasSoftDeletes) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('suppliers', 'secondary_phone') && Schema::hasColumn('suppliers', 'alternate_phone')) {
            DB::table('suppliers')
                ->whereNull('alternate_phone')
                ->whereNotNull('secondary_phone')
                ->update([
                    'alternate_phone' => DB::raw('secondary_phone'),
                ]);
        }

        if (Schema::hasColumn('suppliers', 'code')) {
            DB::table('suppliers')
                ->where(function ($query): void {
                    $query->whereNull('code')
                        ->orWhere('code', '');
                })
                ->orderBy('id')
                ->chunkById(100, function ($suppliers): void {
                    foreach ($suppliers as $supplier) {
                        $baseCode = 'SUP-'.str_pad((string) $supplier->id, 6, '0', STR_PAD_LEFT);
                        $code = $baseCode;
                        $counter = 1;

                        while (DB::table('suppliers')
                            ->where('code', $code)
                            ->where('id', '!=', $supplier->id)
                            ->exists()) {
                            $code = $baseCode.'-'.$counter;
                            $counter++;
                        }

                        DB::table('suppliers')
                            ->where('id', $supplier->id)
                            ->update(['code' => $code]);
                    }
                });
        }

        if (Schema::hasColumn('suppliers', 'opening_balance')) {
            DB::table('suppliers')
                ->whereNull('opening_balance')
                ->update(['opening_balance' => 0]);
        }

        if (Schema::hasColumn('suppliers', 'opening_balance_type')) {
            DB::table('suppliers')
                ->where(function ($query): void {
                    $query->whereNull('opening_balance_type')
                        ->orWhere('opening_balance_type', '');
                })
                ->update(['opening_balance_type' => Supplier::OPENING_BALANCE_TYPE_PAYABLE]);
        }

        if (Schema::hasColumn('suppliers', 'payment_terms_days')) {
            DB::table('suppliers')
                ->whereNull('payment_terms_days')
                ->update(['payment_terms_days' => 0]);
        }

        if (Schema::hasColumn('suppliers', 'credit_limit')) {
            DB::table('suppliers')
                ->whereNull('credit_limit')
                ->update(['credit_limit' => 0]);
        }

        if (Schema::hasColumn('suppliers', 'is_blocked')) {
            DB::table('suppliers')
                ->whereNull('is_blocked')
                ->update(['is_blocked' => false]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        if (Schema::hasColumn('suppliers', 'created_by')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('suppliers', 'updated_by')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('updated_by');
            });
        }

        $dropColumns = [];

        foreach ([
            'code',
            'company_name',
            'alternate_phone',
            'trade_license_no',
            'tin_no',
            'bin_no',
            'opening_balance',
            'opening_balance_type',
            'payment_terms_days',
            'credit_limit',
            'is_blocked',
            'notes',
        ] as $column) {
            if (Schema::hasColumn('suppliers', $column)) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            Schema::table('suppliers', function (Blueprint $table) use ($dropColumns): void {
                $table->dropColumn($dropColumns);
            });
        }

        if (Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
