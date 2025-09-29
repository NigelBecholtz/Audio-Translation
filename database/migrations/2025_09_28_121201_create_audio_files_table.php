<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('duration')->nullable();
            $table->string('source_language');
            $table->string('target_language');
            $table->text('transcription')->nullable();
            $table->text('translated_text')->nullable();
            $table->string('translated_audio_path')->nullable();
            $table->enum('status', ['uploaded', 'transcribing', 'translating', 'generating_audio', 'completed', 'failed'])->default('uploaded');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};
