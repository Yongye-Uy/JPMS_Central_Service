<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS issues table (docx table #19). status: Draft|Published. */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->restrictOnDelete();
            $table->unsignedInteger('volume')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('status')->default('Draft');

            $table->index(['journal_id', 'status']);
            $table->unique(['journal_id', 'volume', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
