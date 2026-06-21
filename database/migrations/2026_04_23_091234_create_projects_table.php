<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('project_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->references('template_id')->on('templates')->nullOnDelete();
            $table->foreignUuid('section_id')->references('section_id')->on('sections')->cascadeOnDelete();
            $table->string('title')->default('Untitled Project');
            $table->text('synopsis')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
