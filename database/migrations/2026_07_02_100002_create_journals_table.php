<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** JPMS journals table (docx table #4). */
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('issn')->unique()->nullable();
            $table->text('scope_description')->nullable();
            $table->foreignId('editor_in_chief_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index('editor_in_chief_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
