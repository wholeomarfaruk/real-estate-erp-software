<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['sms', 'email', 'both'])->default('sms');
            $table->unsignedBigInteger('audience_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->enum('schedule_type', ['now', 'scheduled'])->default('now');
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'queued', 'running', 'completed', 'paused', 'failed'])->default('draft');
            $table->json('stats')->nullable(); // sent, delivered, failed, opened
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('audience_id')->references('id')->on('marketing_audiences')->nullOnDelete();
            $table->foreign('template_id')->references('id')->on('communication_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
