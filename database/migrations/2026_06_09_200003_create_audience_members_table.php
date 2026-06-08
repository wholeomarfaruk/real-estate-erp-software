<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audience_id');
            $table->string('member_type'); // 'lead' or 'customer'
            $table->unsignedBigInteger('member_id');
            $table->timestamps();

            $table->foreign('audience_id')->references('id')->on('marketing_audiences')->cascadeOnDelete();
            $table->index(['member_type', 'member_id']);
            $table->unique(['audience_id', 'member_type', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_members');
    }
};
