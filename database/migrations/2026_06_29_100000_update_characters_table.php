<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('full_name')->after('project_id');
            $table->string('nick_name')->after('full_name');
            $table->renameColumn('description', 'bio');
            $table->integer('canvas_top')->default(0)->after('image_path');
            $table->integer('canvas_left')->default(0)->after('canvas_top');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('name')->after('project_id');
            $table->dropColumn(['full_name', 'nick_name', 'canvas_top', 'canvas_left']);
            $table->renameColumn('bio', 'description');
        });
    }
};
