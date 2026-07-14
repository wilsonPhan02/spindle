<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->uuid('manuscript_id')->primary();
            $table->foreignUuid('chapter_card_id')->references('chapter_card_id')->on('chapter_cards')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscripts');
    }
};
