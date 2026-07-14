<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS review_files table (docx table #16) — reviewer annotated-file uploads. */
    public function up(): void
    {
        Schema::create('review_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->index('review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_files');
    }
};
