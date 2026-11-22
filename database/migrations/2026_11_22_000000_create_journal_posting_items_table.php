<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_posting_items', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 60)->index();
            $table->string('group_name', 100)->nullable(); // set when the item creates a custom category
            $table->string('slug', 80)->unique();
            $table->string('label', 150);
            $table->string('description', 500)->nullable();
            $table->string('icon', 50)->default('folder');
            $table->string('color', 100)->default('bg-gray-50 border-gray-200 text-gray-700');
            $table->string('feature_type', 100)->nullable(); // features.key — drives the account list in the entry form
            $table->string('transaction_type', 50)->nullable(); // TransactionType value posted on approval
            $table->string('url', 255)->nullable();       // null => shown as "Coming soon"
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_posting_items');
    }
};
