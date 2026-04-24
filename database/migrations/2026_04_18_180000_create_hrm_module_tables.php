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

        Schema::create('salary_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_from');
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('house_rent', 14, 2)->default(0);
            $table->decimal('medical_allowance', 14, 2)->default(0);
            $table->decimal('transport_allowance', 14, 2)->default(0);
            $table->decimal('food_allowance', 14, 2)->default(0);
            $table->decimal('other_allowance', 14, 2)->default(0);
            $table->decimal('gross_salary', 14, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'status']);
        });

        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('payroll_date')->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('allowance_total', 14, 2)->default(0);
            $table->decimal('bonus_total', 14, 2)->default(0);
            $table->decimal('deduction_total', 14, 2)->default(0);
            $table->decimal('gross_salary', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->string('payment_status', 30)->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['month', 'year', 'payment_status']);
        });

        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('label', 100);
            $table->decimal('amount', 14, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payroll_id', 'type']);
        });

        Schema::create('employee_advances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('advance_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('adjusted_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['advance_date']);
        });

        Schema::create('employee_advance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_advance_id')->constrained('employee_advances')->cascadeOnDelete();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('adjustment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_advance_id', 'payroll_id'],'emp_adv_adj_idx'
            );
        });

        Schema::create('payroll_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_date', 'payment_method']);
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

