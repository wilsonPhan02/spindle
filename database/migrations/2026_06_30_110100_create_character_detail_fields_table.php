<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_detail_fields', function (Blueprint $table) {
            $table->uuid('character_detail_field_id')->primary();
            $table->foreignUuid('character_detail_group_id')->references('character_detail_group_id')->on('character_detail_groups')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_detail_fields');
    }
};
