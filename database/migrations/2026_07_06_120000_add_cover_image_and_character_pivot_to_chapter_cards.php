<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapter_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('chapter_cards', 'cover_image_path')) {
                $table->string('cover_image_path')->nullable()->after('summary');
            }
        });

        if (! Schema::hasTable('chapter_character')) {
            Schema::create('chapter_character', function (Blueprint $table) {
                $table->foreignUuid('chapter_card_id')->references('chapter_card_id')->on('chapter_cards')->cascadeOnDelete();
                $table->foreignUuid('character_id')->references('character_id')->on('characters')->cascadeOnDelete();
                $table->primary(['chapter_card_id', 'character_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_character');

        Schema::table('chapter_cards', function (Blueprint $table) {
            if (Schema::hasColumn('chapter_cards', 'cover_image_path')) {
                $table->dropColumn('cover_image_path');
            }
        });
    }
};
