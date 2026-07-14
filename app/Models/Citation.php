<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citation extends Model
{
    public $timestamps = false;

    protected $fillable = ['citing_article_id', 'cited_reference_text', 'cited_article_id'];

    public function citingArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'citing_article_id');
    }

    public function citedArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'cited_article_id');
    }
}
