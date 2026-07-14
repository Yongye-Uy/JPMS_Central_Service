<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS reviews table (docx table #14). One review per invitation. */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->unique()->constrained('review_invitations')->cascadeOnDelete();
            $table->string('recommendation')->nullable();
            $table->text('comments_to_author')->nullable();
            $table->text('comments_to_editor')->nullable();
            $table->timestamp('submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
