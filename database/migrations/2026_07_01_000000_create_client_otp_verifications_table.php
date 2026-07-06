<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('verification_id')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('channel', ['sms', 'email']);
            $table->string('destination');        // phone number or email the OTP was sent to
            $table->string('otp_hash');           // hashed OTP, never stored in plain text
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_otp_verifications');
    }
};
