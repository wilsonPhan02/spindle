<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_hashtag', function (Blueprint $table) {
            $table->foreignUuid('character_id')->references('character_id')->on('characters')->cascadeOnDelete();
            $table->foreignUuid('hashtag_id')->references('hashtag_id')->on('hashtags')->cascadeOnDelete();
            $table->primary(['character_id', 'hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_hashtag');
    }
};
