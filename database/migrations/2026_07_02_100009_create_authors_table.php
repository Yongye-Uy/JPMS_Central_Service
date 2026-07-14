<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS authors table (docx table #8) — finalized roster once co-author invitations are accepted. */
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('author_order');
            $table->boolean('is_corresponding')->default(false);

            $table->unique(['manuscript_id', 'user_id']);
            $table->index('manuscript_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
