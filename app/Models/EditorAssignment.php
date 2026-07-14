<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['manuscript_id', 'editor_id', 'assigned_at', 'role'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
