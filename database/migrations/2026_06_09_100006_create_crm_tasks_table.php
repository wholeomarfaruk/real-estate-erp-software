<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['call', 'email', 'meeting', 'follow_up', 'document', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'done', 'cancelled'])->default('todo');
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('related_type', 50)->nullable(); // lead | customer
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index(['assigned_to', 'status']);
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
