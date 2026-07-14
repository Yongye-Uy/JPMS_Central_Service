<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manuscript extends Model
{
    public const STATUSES = [
        'Draft', 'Submitted', 'Under Review', 'Ready for Decision', 'Revision Required',
        'Accepted', 'Rejected', 'Withdrawn', 'Published', 'Archived',
    ];

    public const DECIDABLE_STATUSES = ['Submitted', 'Under Review', 'Ready for Decision'];

    protected $fillable = [
        'journal_id', 'author_id', 'current_version_id', 'title', 'abstract',
        'manuscript_type', 'status', 'pre_archive_status', 'submitted_at', 'withdrawal_reason', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ManuscriptVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ManuscriptVersion::class)->orderBy('version_number');
    }

    public function coAuthorInvitations(): HasMany
    {
        return $this->hasMany(CoAuthorInvitation::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class)->orderBy('author_order');
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'manuscript_keywords');
    }

    public function editorAssignments(): HasMany
    {
        return $this->hasMany(EditorAssignment::class);
    }

    public function reviewInvitations(): HasMany
    {
        return $this->hasMany(ReviewInvitation::class);
    }

    public function editorialDecisions(): HasMany
    {
        return $this->hasMany(EditorialDecision::class)->orderByDesc('decided_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function article(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
