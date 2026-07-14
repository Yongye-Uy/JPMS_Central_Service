<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManuscriptFile extends Model
{
    public const TYPES = ['main', 'supplementary'];

    public $timestamps = false;

    protected $fillable = ['version_id', 'file_type', 'file_path', 'original_filename', 'size_kb'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ManuscriptVersion::class, 'version_id');
    }
}
