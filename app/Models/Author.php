<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Author extends Model
{
    public $timestamps = false;

    protected $fillable = ['manuscript_id', 'user_id', 'author_order', 'is_corresponding'];

    protected function casts(): array
    {
        return ['is_corresponding' => 'boolean'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
