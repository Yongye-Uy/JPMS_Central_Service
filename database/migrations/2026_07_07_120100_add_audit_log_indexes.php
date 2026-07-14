<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * The audit_log MongoDB collection (App\Models\AuditLog) has no indexes
     * beyond the implicit _id. AuditLogController::index filters by
     * entity_type+entity_id and user_id, and always sorts by created_at
     * descending — at ~0.5-1M documents these would otherwise be full
     * collection scans.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->table('audit_log', function (Blueprint $collection) {
            $collection->index(['entity_type' => 1, 'entity_id' => 1]);
            $collection->index('user_id');
            $collection->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('audit_log', function (Blueprint $collection) {
            $collection->dropIndex(['entity_type' => 1, 'entity_id' => 1]);
            $collection->dropIndex(['user_id' => 1]);
            $collection->dropIndex(['created_at' => 1]);
        });
    }
};
