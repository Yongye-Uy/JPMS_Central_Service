<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Search infrastructure: pg_trgm for fuzzy name search (module 1 "Search
     * users", module 3 "Search Reviewers"), and a generated tsvector column
     * + GIN index on manuscripts.title/abstract for full-text search
     * (module 2 "Search Submission", module 6 "Search" articles/abstracts).
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX users_full_name_trgm_idx ON users USING GIN (full_name gin_trgm_ops)');

        DB::statement(<<<'SQL'
            ALTER TABLE manuscripts
            ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(abstract, '')), 'B')
            ) STORED
        SQL);

        DB::statement('CREATE INDEX manuscripts_search_vector_idx ON manuscripts USING GIN (search_vector)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS manuscripts_search_vector_idx');
        DB::statement('ALTER TABLE manuscripts DROP COLUMN IF EXISTS search_vector');
        DB::statement('DROP INDEX IF EXISTS users_full_name_trgm_idx');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
