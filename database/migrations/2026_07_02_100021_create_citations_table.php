<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS citations table (docx table #21). cited_article_id NULL = external reference. */
    public function up(): void
    {
        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citing_article_id')->constrained('articles')->cascadeOnDelete();
            $table->text('cited_reference_text')->nullable();
            $table->foreignId('cited_article_id')->nullable()->constrained('articles')->nullOnDelete();

            $table->index('citing_article_id');
            $table->index('cited_article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
