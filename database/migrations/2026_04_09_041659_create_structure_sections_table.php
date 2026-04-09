<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('structure_sections', function (Blueprint $table) {
            $table->uuid('section_id')->primary();
            $table->foreignUuid('template_id')->references('template_id')->on('templates')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('goal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structure_sections');
    }
};
