<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoAuthorInvitation extends Model
{
    public const STATUSES = ['Pending', 'Accepted', 'Declined'];

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 'inviting_author_id', 'invited_author_id',
        'invited_at', 'status', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime', 'responded_at' => 'datetime'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function invitingAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviting_author_id');
    }

    public function invitedAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_author_id');
    }
}
