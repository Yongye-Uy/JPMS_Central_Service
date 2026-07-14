<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS review_invitations table (docx table #13). Status values:
     * Pending, Accepted, Declined, In Progress, Completed, Overdue.
     *
     * The docx's ERD notes explicitly say "No extension-request table" for
     * module 3 function 5 ("Request Review Extension"), so that flow is
     * modeled with extra columns here rather than a new table.
     */
    public function up(): void
    {
        Schema::create('review_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('deadline')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->text('declined_reason')->nullable();

            // Extension-request flow (no separate table, per docx note).
            $table->timestamp('requested_deadline')->nullable();
            $table->text('extension_reason')->nullable();
            $table->string('extension_status')->nullable(); // Pending|Approved|Rejected

            $table->index('manuscript_id');
            $table->index('reviewer_id');
            $table->index(['manuscript_id', 'status']);
            $table->index('deadline');
            $table->index('extension_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_invitations');
    }
};
