<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event');
            // Events: lead.created, lead.status_changed, lead.converted,
            //         followup.scheduled, followup.completed, booking.created
            $table->enum('action_type', ['send_sms', 'send_email', 'send_both'])->default('send_sms');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->json('conditions')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('template_id')->references('id')->on('communication_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
