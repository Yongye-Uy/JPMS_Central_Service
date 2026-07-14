<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 'issue_id', 'source_version_id', 'doi',
        'page_start', 'page_end', 'pdf_path', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(ManuscriptVersion::class, 'source_version_id');
    }

    public function citationsMade(): HasMany
    {
        return $this->hasMany(Citation::class, 'citing_article_id');
    }

    public function citedBy(): HasMany
    {
        return $this->hasMany(Citation::class, 'cited_article_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ArticleMetric::class);
    }
}
