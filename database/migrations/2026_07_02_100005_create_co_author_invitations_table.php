<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JPMS co_author_invitations table (docx table #9 — missing from
     * JPMS_ERD.sql). Invited person must already be a registered user
     * (matched by email search per module 2 function 4). Status values:
     * Pending, Accepted, Declined.
     */
    public function up(): void
    {
        Schema::create('co_author_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('inviting_author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('invited_author_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('invited_at')->useCurrent();
            $table->string('status')->default('Pending');
            $table->timestamp('responded_at')->nullable();

            $table->index('manuscript_id');
            $table->index('invited_author_id');
            $table->index(['manuscript_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('co_author_invitations');
    }
};
