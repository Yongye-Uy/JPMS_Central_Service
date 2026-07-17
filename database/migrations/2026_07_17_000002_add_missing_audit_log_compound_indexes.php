<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Add compound indexes to audit_log to support sorting by created_at descending
     * while filtering by other fields, avoiding the 32MB in-memory sort limit.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->table('audit_log', function (Blueprint $collection) {
            $collection->index(['user_id' => 1, 'created_at' => -1]);
            $collection->index(['entity_type' => 1, 'entity_id' => 1, 'created_at' => -1]);
            $collection->index(['entity_type' => 1, 'created_at' => -1]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('audit_log', function (Blueprint $collection) {
            $collection->dropIndex(['entity_type' => 1, 'created_at' => -1]);
            $collection->dropIndex(['entity_type' => 1, 'entity_id' => 1, 'created_at' => -1]);
            $collection->dropIndex(['user_id' => 1, 'created_at' => -1]);
        });
    }
};
