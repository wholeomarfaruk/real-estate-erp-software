<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id', 20)->unique(); // CUST-0000001
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('division')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_registration_no')->nullable();
            $table->string('company_tax_id')->nullable();
            $table->enum('doc_type', ['nid', 'passport', 'driving_licence', 'birth_certificate', 'trade_licence'])->nullable();
            $table->string('doc_no')->nullable();
            $table->date('doc_issue_date')->nullable();
            $table->date('doc_expiry_date')->nullable();
            $table->unsignedBigInteger('doc_file_id')->nullable();
            $table->unsignedBigInteger('profile_image_id')->nullable();
            $table->enum('kyc_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->date('kyc_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('source', ['walk_in', 'website', 'referral', 'facebook_ad', 'property_fair', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
