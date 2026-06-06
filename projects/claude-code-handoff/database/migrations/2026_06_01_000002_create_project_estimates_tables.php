<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------- Estimate header / version / approval ----------
        Schema::create('project_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('estimate_no')->unique();
            $table->string('title')->nullable();                        // Initial Estimate, Revised Estimate...
            $table->unsignedInteger('version')->default(1);
            $table->date('estimate_date');
            $table->string('status')->default('draft');                 // draft, submitted, approved, rejected
            $table->decimal('total_estimated_amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->json('attachments')->nullable();                    // [media_file_ids]
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        // ---------- Estimate breakdown (BOQ): material + labour + other ----------
        Schema::create('project_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_estimate_id')->constrained('project_estimates')->cascadeOnDelete();
            $table->unsignedBigInteger('material_id')->nullable();          // materials.id  (exactly one of these two)
            $table->unsignedBigInteger('expense_category_id')->nullable();  // expense_categories.id
            $table->string('unit')->nullable();                             // Bag, Ton, Day, Month, CFT
            $table->decimal('estimated_qty', 12, 2);
            $table->decimal('estimated_rate', 15, 2);
            $table->decimal('estimated_amount', 15, 2);                     // qty × rate (compute server-side)
            $table->string('cost_type')->nullable();                        // material, labour, overhead, indirect
            $table->string('work_phase')->nullable();                       // foundation, structure, brick_work, plaster, electrical, plumbing, finishing, other
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['project_estimate_id', 'work_phase']);
            // App-level rule: exactly one of material_id / expense_category_id must be set.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_estimate_items');
        Schema::dropIfExists('project_estimates');
    }
};
