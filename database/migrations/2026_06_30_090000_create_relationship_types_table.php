<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->uuid('relationship_type_id')->primary();
            $table->foreignUuid('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('text_color');
            $table->string('bg_color');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_types');
    }
};
