<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS manuscript_files table (docx table #7). file_type: main|supplementary. */
    public function up(): void
    {
        Schema::create('manuscript_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('manuscript_versions')->cascadeOnDelete();
            $table->string('file_type');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('size_kb')->nullable();

            $table->index('version_id');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscript_files');
    }
};
