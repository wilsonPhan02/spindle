<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    use HasUuids;

    protected $primaryKey = 'note_id';
    protected $touches = ['project'];

    /** Kolom UUID yang di-auto-generate oleh HasUuids */
    public function uniqueIds(): array
    {
        return ['note_id'];
    }

    protected $fillable = [
        'project_id',
        'parent_note_id',
        'depth',
        'sort_order',
        'title',
        'body',
    ];

    protected $casts = [
        'depth'      => 'integer',
        'sort_order' => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Konstanta
    // -----------------------------------------------------------------------

    /** Batas depth (0-based). Depth 2 = level ke-3 = tidak boleh punya children */
    public const MAX_DEPTH = 2;

    /** Batas karakter body (sesuai TEXT di MySQL = 65.535, atau MEDIUMTEXT = 16MB) */
    public const MAX_BODY_CHARS = 65535;

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_note_id', 'note_id');
    }

    /** Sub-tabs langsung (1 level) */
    public function children(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_note_id', 'note_id')
                    ->orderBy('sort_order');
    }

    /** Semua keturunan hingga level terdalam (eager via nested) */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Apakah note ini masih boleh punya sub-tab? */
    public function canHaveChildren(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    /** Apakah note ini adalah root (top-level)? */
    public function isRoot(): bool
    {
        return is_null($this->parent_note_id);
    }
}
