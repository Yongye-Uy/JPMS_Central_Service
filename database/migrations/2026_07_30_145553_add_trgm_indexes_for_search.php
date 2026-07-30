<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS users_full_name_trgm_idx ON users USING gin (full_name gin_trgm_ops);');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS keywords_text_trgm_idx ON keywords USING gin (keyword_text gin_trgm_ops);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_full_name_trgm_idx;');
        DB::statement('DROP INDEX IF EXISTS keywords_text_trgm_idx;');
    }
};
