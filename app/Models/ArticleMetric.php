<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleMetric extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'metric_date', 'views', 'downloads', 'citations_count'];

    protected function casts(): array
    {
        return ['metric_date' => 'date'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
