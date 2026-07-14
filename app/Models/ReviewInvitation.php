<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewInvitation extends Model
{
    public const STATUSES = ['Pending', 'Accepted', 'Declined', 'In Progress', 'Completed', 'Overdue'];
    public const EXTENSION_STATUSES = ['Pending', 'Approved', 'Rejected'];

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 'reviewer_id', 'invited_by', 'deadline', 'status',
        'invited_at', 'accepted_at', 'declined_reason',
        'requested_deadline', 'extension_reason', 'extension_status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'requested_deadline' => 'datetime',
        ];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'invitation_id');
    }
}
