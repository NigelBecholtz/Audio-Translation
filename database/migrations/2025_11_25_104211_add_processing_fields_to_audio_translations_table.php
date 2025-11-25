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
        Schema::table('audio_translations', function (Blueprint $table) {
            $table->string('processing_stage')->nullable()->after('status');
            $table->integer('processing_progress')->default(0)->after('processing_stage');
            $table->text('processing_message')->nullable()->after('processing_progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_translations', function (Blueprint $table) {
            $table->dropColumn(['processing_stage', 'processing_progress', 'processing_message']);
        });
    }
};
