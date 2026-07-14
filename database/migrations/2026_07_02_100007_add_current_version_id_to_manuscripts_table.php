<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the FK for manuscripts.current_version_id now that
     * manuscript_versions exists (breaks the circular dependency between
     * the two tables, matching the approach JPMS_ERD.sql itself used for
     * appending late FKs).
     */
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')->on('manuscript_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
    }
};
