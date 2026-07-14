<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS review_scores table (docx table #15). 5 fixed criteria:
     * Originality, Methodology, Clarity, Significance, References — each
     * scored 0-5.
     */
    public function up(): void
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->string('criterion');
            $table->unsignedTinyInteger('score');

            $table->unique(['review_id', 'criterion']);
            $table->index('review_id');
        });

        DB::statement('ALTER TABLE review_scores ADD CONSTRAINT review_scores_score_range CHECK (score BETWEEN 0 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
