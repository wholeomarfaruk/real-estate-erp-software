<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();//account_type
            $table->string('bank_name')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('ac_number')->nullable();
            $table->string('branch')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('route_code')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('active');
            $table->json('files')->nullable();
            $table->unsignedBigInteger('account_id')->nullable()->unique();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
