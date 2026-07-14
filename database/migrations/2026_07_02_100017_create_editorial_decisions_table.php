<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS editorial_decisions table (docx table #17). decision values:
     * Accept, Return to Edit, Minor Revision, Major Revision, Reject,
     * Desk Reject.
     */
    public function up(): void
    {
        Schema::create('editorial_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('decision_letter')->nullable();
            $table->timestamp('decided_at')->useCurrent();

            $table->index('manuscript_id');
            $table->index(['manuscript_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_decisions');
    }
};
