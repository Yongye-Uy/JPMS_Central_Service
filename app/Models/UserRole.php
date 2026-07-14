<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Explicit pivot model for user_roles so it can be audited/observed like any other model. */
class UserRole extends Pivot
{
    public $incrementing = true;
    public $timestamps = false;

    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'role_id', 'journal_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
