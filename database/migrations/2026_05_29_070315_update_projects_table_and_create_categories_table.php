<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update tabel projects yang sudah ada
        Schema::table('projects', function (Blueprint $table) {
            // Kolom untuk Synopsis (isi synopsis)
            $table->text('synopsis')->nullable()->after('description');

            // Kolom untuk menyimpan PATH gambar cover (STRING)
            $table->string('cover_image_path')->nullable()->after('synopsis');
        });

        // 2. Buat tabel baru untuk categories (custom user)
        Schema::create('project_categories', function (Blueprint $table) {
            $table->uuid('category_id')->primary();
            // Hubungkan ke project asli
            $table->foreignUuid('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_categories');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['synopsis', 'cover_image_path']);
        });
    }
};
