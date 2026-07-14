<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_detail_values', function (Blueprint $table) {
            $table->uuid('character_detail_value_id')->primary();
            $table->foreignUuid('character_id')->references('character_id')->on('characters')->cascadeOnDelete();
            $table->foreignUuid('character_detail_field_id')->references('character_detail_field_id')->on('character_detail_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'character_detail_field_id'], 'character_detail_values_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_detail_values');
    }
};
