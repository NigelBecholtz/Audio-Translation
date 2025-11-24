<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'pending_approval'
        DB::statement("ALTER TABLE audio_files MODIFY COLUMN status ENUM('uploaded', 'transcribing', 'pending_approval', 'translating', 'generating_audio', 'completed', 'failed') DEFAULT 'uploaded'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE audio_files MODIFY COLUMN status ENUM('uploaded', 'transcribing', 'translating', 'generating_audio', 'completed', 'failed') DEFAULT 'uploaded'");
    }
};

