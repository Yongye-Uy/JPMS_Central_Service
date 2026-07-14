<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS manuscripts table (docx table #5). Status values: Draft,
     * Submitted, Under Review, Revision Required, Accepted, Rejected,
     * Withdrawn, Published, Archived. current_version_id is added via a
     * later ALTER migration once manuscript_versions exists (circular FK).
     */
    public function up(): void
    {
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('manuscript_type');
            $table->string('status')->default('Draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            $table->index('journal_id');
            $table->index('author_id');
            $table->index('status');
            $table->index(['journal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscripts');
    }
};
