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
        Schema::create('relationships', function (Blueprint $table) {
            $table->uuid('relationship_id')->primary();
            $table->foreignUuid('from_id')->references('character_id')->on('characters')->cascadeOnDelete();
            $table->foreignUuid('to_id')->references('character_id')->on('characters')->cascadeOnDelete();
            $table->string('type', 255);
            $table->text('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
