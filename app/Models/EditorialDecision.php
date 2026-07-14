<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialDecision extends Model
{
    public const DECISIONS = [
        'Accept', 'Return to Edit', 'Minor Revision', 'Major Revision', 'Reject', 'Desk Reject',
    ];

    public $timestamps = false;

    protected $fillable = ['manuscript_id', 'editor_id', 'decision', 'decision_letter', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
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
