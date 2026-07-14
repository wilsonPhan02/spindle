<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Project::query()->each(function (Project $project) {
            $project->seedDefaultCharacterDetailGroups();
        });
    }

    public function down(): void
    {
        // Tidak ada rollback data: group/field default tetap aman dibiarkan ada.
    }
};
