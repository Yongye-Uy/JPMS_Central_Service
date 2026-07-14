<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remembers the status a manuscript held right before an admin archived
     * it, so restoring puts it back where it was (e.g. Published) instead of
     * always falling back to Submitted.
     */
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->string('pre_archive_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropColumn('pre_archive_status');
        });
    }
};
