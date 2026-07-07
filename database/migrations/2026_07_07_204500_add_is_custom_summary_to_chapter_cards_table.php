<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapter_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('chapter_cards', 'is_custom_summary')) {
                $table->boolean('is_custom_summary')->default(false)->after('summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chapter_cards', function (Blueprint $table) {
            if (Schema::hasColumn('chapter_cards', 'is_custom_summary')) {
                $table->dropColumn('is_custom_summary');
            }
        });
    }
};
