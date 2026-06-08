<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sms', 'email'])->default('sms');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('automation_id')->nullable();
            $table->string('member_type')->nullable(); // 'lead' or 'customer'
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('recipient'); // phone or email
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'opened'])->default('queued');
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['member_type', 'member_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
