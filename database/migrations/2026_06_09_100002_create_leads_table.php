<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_no', 30)->unique();

            // Basic info
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Relations
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();

            // Budget
            $table->decimal('budget_min', 18, 2)->nullable();
            $table->decimal('budget_max', 18, 2)->nullable();

            // Status & pipeline
            $table->enum('status', [
                'new', 'contacted', 'qualified', 'site_visit',
                'negotiation', 'won', 'lost',
            ])->default('new');
            $table->string('closed_reason')->nullable();

            // Conversion
            $table->unsignedBigInteger('converted_customer_id')->nullable();
            $table->timestamp('converted_at')->nullable();

            // CRM score
            $table->unsignedTinyInteger('score')->default(0);

            // JSON enrichment
            $table->json('social_profiles')->nullable(); // {facebook, whatsapp, instagram, linkedin}
            $table->json('extra_data')->nullable();      // {occupation, company, income_range, ...}
            $table->json('attachments')->nullable();     // {file_ids: [1,2,3]}

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('converted_customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
