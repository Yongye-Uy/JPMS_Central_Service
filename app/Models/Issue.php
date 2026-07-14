<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    public const STATUSES = ['Draft', 'Published'];

    public $timestamps = false;

    protected $fillable = ['journal_id', 'volume', 'number', 'year', 'publication_date', 'status'];

    protected function casts(): array
    {
        return ['publication_date' => 'date'];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
