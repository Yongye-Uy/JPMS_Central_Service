<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds composite/foreign-key indexes for query shapes not covered by the
     * original create_* migrations, ahead of loading a ~0.5-1M row dataset:
     * "this reviewer's invitations by status" and "this user's co-author
     * invitations by status" each currently intersect two single-column
     * indexes instead of hitting one composite index, and manuscripts.created_at
     * (used by date-range reporting) has no index at all.
     */
    public function up(): void
    {
        Schema::table('review_invitations', function (Blueprint $table) {
            $table->index(['reviewer_id', 'status']);
        });

        Schema::table('co_author_invitations', function (Blueprint $table) {
            $table->index(['invited_author_id', 'status']);
            $table->index('inviting_author_id');
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('current_version_id');
        });

        Schema::table('manuscript_versions', function (Blueprint $table) {
            $table->index('uploaded_by');
        });

        Schema::table('editor_assignments', function (Blueprint $table) {
            $table->index('editor_id');
        });

        Schema::table('editorial_decisions', function (Blueprint $table) {
            $table->index('editor_id');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->index('source_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('review_invitations', function (Blueprint $table) {
            $table->dropIndex(['reviewer_id', 'status']);
        });

        Schema::table('co_author_invitations', function (Blueprint $table) {
            $table->dropIndex(['invited_author_id', 'status']);
            $table->dropIndex(['inviting_author_id']);
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['current_version_id']);
        });

        Schema::table('manuscript_versions', function (Blueprint $table) {
            $table->dropIndex(['uploaded_by']);
        });

        Schema::table('editor_assignments', function (Blueprint $table) {
            $table->dropIndex(['editor_id']);
        });

        Schema::table('editorial_decisions', function (Blueprint $table) {
            $table->dropIndex(['editor_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['source_version_id']);
        });
    }
};
