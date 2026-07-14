<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_cards', function (Blueprint $table) {
            $table->uuid('chapter_card_id')->primary();
            $table->foreignUuid('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
            $table->foreignUuid('structure_section_id')->nullable()->references('structure_section_id')->on('structure_sections')->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->string('status')->default('In Progress');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_cards');
    }
};
