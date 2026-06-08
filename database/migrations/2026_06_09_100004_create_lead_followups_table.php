<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->enum('type', ['call', 'email', 'whatsapp', 'meeting', 'site_visit', 'other'])->default('call');
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', ['pending', 'done', 'cancelled', 'rescheduled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_followups');
    }
};
