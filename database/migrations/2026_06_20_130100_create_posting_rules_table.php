<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The debit/credit legs of each accounting event's journal recipe. The engine
 * sums these at post time and LedgerService rejects anything unbalanced, so a
 * misconfigured recipe can never write unbalanced books.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accounting_event_id')->constrained('accounting_events')->cascadeOnDelete();
            $table->enum('leg', ['debit', 'credit']);
            $table->enum('account_source', ['fixed', 'runtime'])->default('fixed');
            // Fixed legs reference an account; runtime legs are resolved at post time.
            $table->foreignId('account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->string('runtime_slot', 50)->nullable();
            $table->enum('amount_source', ['full', 'context'])->default('full');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('description', 150)->nullable();
            $table->timestamps();

            $table->index(['accounting_event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rules');
    }
};
