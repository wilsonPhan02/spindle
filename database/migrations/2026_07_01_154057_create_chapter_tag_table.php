<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chapter_tag', function (Blueprint $table) {
            $table->id();

            // Sesuaikan nama kolom referensi di sini:
            $table->foreignUuid('chapter_card_id')
                ->constrained('chapter_cards', 'chapter_card_id') // <--- TAMBAHKAN NAMA TABEL DAN KOLOM PRIMARY KEY-NYA
                ->onDelete('cascade');

            $table->foreignId('tag_id')
                ->constrained('tags') // Asumsi tabel tags primary key-nya 'id'
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_tag');
    }
};
