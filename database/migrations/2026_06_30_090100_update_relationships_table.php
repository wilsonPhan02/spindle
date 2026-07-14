<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->uuid('relationship_type_id')->nullable()->after('to_id');
        });

        $this->backfillRelationshipTypes();

        Schema::table('relationships', function (Blueprint $table) {
            $table->foreign('relationship_type_id')->references('relationship_type_id')->on('relationship_types')->cascadeOnDelete();
        });

        Schema::table('relationships', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Data yang sudah ada (kolom 'type' bebas teks) dipetakan ke tabel
     * relationship_types per-project, supaya tidak ada relasi yang hilang.
     * Nama yang cocok dengan daftar default dapat warna default-nya;
     * nama custom (mis. "Soulmate") dapat warna dari sisa palet secara berurutan.
     */
    private function backfillRelationshipTypes(): void
    {
        $defaultColors = [
            'Father' => ['#1565C0', '#BBDEFB'],
            'Mother' => ['#AD1457', '#F8BBD0'],
            'Sibling' => ['#6A1B9A', '#E1BEE7'],
            'Friend' => ['#2E7D32', '#C8E6C9'],
            'Rival' => ['#E65100', '#FFE0B2'],
            'Enemy' => ['#C62828', '#FFCDD2'],
        ];

        $extraPalette = [
            ['#00695C', '#B2DFDB'],
            ['#8C7558', '#EAE1D5'],
            ['#283593', '#C5CAE9'],
            ['#5D4037', '#D7CCC8'],
            ['#37474F', '#CFD8DC'],
        ];

        $typeCache = [];
        $paletteCursor = 0;

        foreach (DB::table('relationships')->get() as $relationship) {
            $character = DB::table('characters')->where('character_id', $relationship->from_id)->first();
            if (! $character) {
                continue;
            }

            $projectId = $character->project_id;
            $name = $relationship->type !== null && $relationship->type !== '' ? $relationship->type : 'Unknown';
            $cacheKey = $projectId.'|'.$name;

            if (! isset($typeCache[$cacheKey])) {
                $existing = DB::table('relationship_types')
                    ->where('project_id', $projectId)
                    ->where('name', $name)
                    ->first();

                if ($existing) {
                    $typeCache[$cacheKey] = $existing->relationship_type_id;
                } else {
                    [$textColor, $bgColor] = $defaultColors[$name] ?? $extraPalette[$paletteCursor % count($extraPalette)];
                    if (! isset($defaultColors[$name])) {
                        $paletteCursor++;
                    }

                    $newId = (string) Str::uuid();
                    DB::table('relationship_types')->insert([
                        'relationship_type_id' => $newId,
                        'project_id' => $projectId,
                        'name' => $name,
                        'text_color' => $textColor,
                        'bg_color' => $bgColor,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $typeCache[$cacheKey] = $newId;
                }
            }

            DB::table('relationships')
                ->where('relationship_id', $relationship->relationship_id)
                ->update(['relationship_type_id' => $typeCache[$cacheKey]]);
        }
    }

    public function down(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->string('type')->default('Unknown');
        });

        Schema::table('relationships', function (Blueprint $table) {
            $table->dropForeign(['relationship_type_id']);
            $table->dropColumn('relationship_type_id');
        });
    }
};
