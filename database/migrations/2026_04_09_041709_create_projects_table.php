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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('project_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('template_id')->references('template_id')->on('templates')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->useCurrent();
            $table->timestamp('last_edited')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
