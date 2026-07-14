<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Keyword extends Model
{
    public $timestamps = false;

    protected $fillable = ['keyword_text'];

    public function manuscripts(): BelongsToMany
    {
        return $this->belongsToMany(Manuscript::class, 'manuscript_keywords');
    }
}
