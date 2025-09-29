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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_file_id')->constrained()->onDelete('cascade');
            $table->string('source_language');
            $table->string('target_language');
            $table->text('original_text');
            $table->text('translated_text');
            $table->string('translation_service')->nullable(); // 'openai', 'deepl', 'google'
            $table->decimal('cost', 8, 4)->nullable(); // Kosten van deze vertaling
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
