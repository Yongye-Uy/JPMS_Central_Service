<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS manuscript_versions table (docx table #6). */
    public function up(): void
    {
        Schema::create('manuscript_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('file_path');
            $table->text('response_note')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            $table->unique(['manuscript_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscript_versions');
    }
};
