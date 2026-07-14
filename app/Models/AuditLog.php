<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * JPMS audit_log (docx table #23) — relocated to MongoDB per the
 * architecture diagram's "MongoDB Document Database -> Audit Logs" box.
 * Column shape preserved from the docx: user_id, entity_type, entity_id,
 * action, old_value, new_value, created_at.
 */
class AuditLog extends Model
{
    protected $connection = 'mongodb';

    // mongodb/laravel-mongodb 5.x doesn't read $collection (that was the
    // old API in earlier major versions of this package) — it defers to
    // base Eloquent's $table, which is what actually names the collection.
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'entity_type', 'entity_id', 'action', 'old_value', 'new_value', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
