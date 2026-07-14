<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    public const RECOMMENDATIONS = ['Accept', 'Minor Revision', 'Major Revision', 'Reject'];
    public const CRITERIA = ['Originality', 'Methodology', 'Clarity', 'Significance', 'References'];

    public $timestamps = false;

    protected $fillable = [
        'invitation_id', 'recommendation', 'comments_to_author', 'comments_to_editor', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(ReviewInvitation::class, 'invitation_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ReviewFile::class);
    }
}
