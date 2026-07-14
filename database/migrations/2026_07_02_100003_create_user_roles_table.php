<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS user_roles table (docx table #3). journal_id NULL = global role
     * (e.g. Admin, or a Reader/Author role that isn't journal-scoped).
     *
     * Postgres treats each NULL as distinct in a plain UNIQUE constraint, so
     * a composite UNIQUE(user_id, role_id, journal_id) would NOT stop two
     * rows with journal_id IS NULL for the same (user_id, role_id) — hence
     * the two separate partial unique indexes below instead.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->cascadeOnDelete();

            $table->index(['user_id', 'journal_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX user_roles_journal_scoped_unique ON user_roles (user_id, role_id, journal_id) WHERE journal_id IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX user_roles_global_unique ON user_roles (user_id, role_id) WHERE journal_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
