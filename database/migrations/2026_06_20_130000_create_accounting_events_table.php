<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business events that auto-post to the double-entry ledger. One row per event
 * key (seeded from AccountingEventRegistry); admins configure each event's
 * debit/credit legs via posting_rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_events', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('module', 50)->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('transaction_type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_events');
    }
};
