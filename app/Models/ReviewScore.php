<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewScore extends Model
{
    public $timestamps = false;

    protected $fillable = ['review_id', 'criterion', 'score'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
