<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->enum('type', [
                'note', 'call', 'email', 'whatsapp', 'sms',
                'site_visit', 'meeting', 'status_change', 'assigned', 'converted',
            ])->default('note');
            $table->text('description');
            $table->string('old_value')->nullable(); // for status_change
            $table->string('new_value')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
