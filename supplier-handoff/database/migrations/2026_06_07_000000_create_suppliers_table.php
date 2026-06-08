<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();          // auto SUP-000001 (see Supplier::booted)
            $table->string('name');                          // required
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('alternate_phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // Compliance
            $table->string('trade_license_no', 100)->nullable();
            $table->string('tin_no', 100)->nullable();
            $table->string('bin_no', 100)->nullable();

            // Flags
            $table->boolean('status')->default(true);        // true = active
            $table->boolean('is_blocked')->default(false);

            $table->text('notes')->nullable();

            // Media — attachment IDs (FK to your media/attachments table)
            $table->unsignedBigInteger('image_id')->nullable();        // profile image
            $table->unsignedBigInteger('cover_image_id')->nullable();  // cover image

            // documents: JSON ARRAY OF FILE IDs ONLY — e.g. [40192, 40193]. No metadata.
            $table->json('documents')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_blocked']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
