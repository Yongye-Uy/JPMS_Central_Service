<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS articles table (docx table #20). Kept normalized (joins to
     * manuscripts/authors/journals) rather than the prototype's denormalized
     * authors[]/journalName/title fields — Redis cache-aside on the read
     * path absorbs the join cost.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->restrictOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('manuscript_versions')->nullOnDelete();
            $table->string('doi')->unique()->nullable();
            $table->unsignedInteger('page_start')->nullable();
            $table->unsignedInteger('page_end')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->index('manuscript_id');
            $table->index('issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
