<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS editor_assignments table (docx table #12). */
    public function up(): void
    {
        Schema::create('editor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->string('role')->nullable();

            $table->index('manuscript_id');
            $table->unique(['manuscript_id', 'editor_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_assignments');
    }
};
