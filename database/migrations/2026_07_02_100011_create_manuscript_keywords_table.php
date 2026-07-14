<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS manuscript_keywords table (docx table #11) — many-to-many. */
    public function up(): void
    {
        Schema::create('manuscript_keywords', function (Blueprint $table) {
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('keyword_id')->constrained('keywords')->cascadeOnDelete();

            $table->primary(['manuscript_id', 'keyword_id']);
            $table->index('keyword_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscript_keywords');
    }
};
