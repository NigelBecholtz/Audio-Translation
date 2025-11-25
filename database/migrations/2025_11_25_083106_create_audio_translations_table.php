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
        Schema::create('audio_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_file_id')->constrained()->onDelete('cascade');
            $table->string('target_language');
            $table->text('translated_text');
            $table->string('translated_audio_path')->nullable();
            $table->string('voice');
            $table->enum('status', ['pending', 'translating', 'generating_audio', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_translations');
    }
};
