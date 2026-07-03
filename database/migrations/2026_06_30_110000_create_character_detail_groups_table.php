<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_detail_groups', function (Blueprint $table) {
            $table->uuid('character_detail_group_id')->primary();
            $table->foreignUuid('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_detail_groups');
    }
};
