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
            $table->text('style_instruction')->nullable()->after('voice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_translations', function (Blueprint $table) {
            $table->dropColumn('style_instruction');
        });
    }
};
