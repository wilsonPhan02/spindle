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
        Schema::table('projects', function (Blueprint $table) {
            // Menambahkan kolom icon_type dengan nilai default 'default'
            $table->string('icon_type')->default('default')->after('cover_image_path');

            // Menambahkan kolom icon yang nullable (kosong jika icon_type = default)
            $table->string('icon')->nullable()->after('icon_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Menghapus kedua kolom jika migration di-rollback
            $table->dropColumn(['icon_type', 'icon']);
        });
    }
};
