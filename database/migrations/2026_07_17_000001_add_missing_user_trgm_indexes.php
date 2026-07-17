<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add missing trigram indexes to support fast ilike queries on email and affiliation
     * in the UserController.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX users_email_trgm_idx ON users USING GIN (email gin_trgm_ops)');
        DB::statement('CREATE INDEX users_affiliation_trgm_idx ON users USING GIN (affiliation gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_affiliation_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS users_email_trgm_idx');
    }
};
