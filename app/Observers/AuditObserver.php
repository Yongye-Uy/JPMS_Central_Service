<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generic model observer that writes to MongoDB's audit_log collection on
 * create/update/delete. Attached to every auditable model in
 * AppServiceProvider::boot() (see plan: Manuscript, ManuscriptVersion,
 * Review, EditorialDecision, ReviewInvitation, Article, Issue, UserRole).
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated', $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getAttributes(), null);
    }

    private function log(Model $model, string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => AuditContext::actorId(),
            'entity_type' => Str::snake(class_basename($model)),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'old_value' => $old ? json_encode($old) : null,
            'new_value' => $new ? json_encode($new) : null,
            'created_at' => now(),
        ]);
    }
}
