<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['name']);
            $table->index(['status']);
        });

        Schema::create('designations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['department_id']);
            $table->index(['name']);
            $table->index(['status']);
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('employee_id', 50)->unique();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date');
            $table->date('confirmation_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->boolean('has_login')->default(false);
            $table->foreignId('photo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'designation_id']);
            $table->index(['name']);
            $table->index(['status']);
            $table->index(['phone']);
            $table->index(['email']);
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('employee_advance_adjustments');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};

