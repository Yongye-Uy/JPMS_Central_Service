<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS messages table (docx table #18) — editor/author communication thread tied to a manuscript. */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('sent_at')->useCurrent();
            $table->boolean('read_status')->default(false);

            $table->index('manuscript_id');
            $table->index('sender_id');
            $table->index(['recipient_id', 'read_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
