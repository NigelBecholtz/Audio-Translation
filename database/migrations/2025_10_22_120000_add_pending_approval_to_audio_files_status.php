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
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB: Modify ENUM column
            DB::statement("ALTER TABLE audio_files MODIFY COLUMN status ENUM('uploaded', 'transcribing', 'pending_approval', 'translating', 'generating_audio', 'completed', 'failed') DEFAULT 'uploaded'");
        } elseif ($driver === 'sqlite') {
            // SQLite: No action needed - SQLite doesn't enforce ENUM constraints
            // The application code will handle validation
            // SQLite stores ENUM as TEXT and allows any string value
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Use CHECK constraint
            DB::statement("ALTER TABLE audio_files DROP CONSTRAINT IF EXISTS audio_files_status_check");
            DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_status_check CHECK (status IN ('uploaded', 'transcribing', 'pending_approval', 'translating', 'generating_audio', 'completed', 'failed'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB: Revert to original ENUM
            DB::statement("ALTER TABLE audio_files MODIFY COLUMN status ENUM('uploaded', 'transcribing', 'translating', 'generating_audio', 'completed', 'failed') DEFAULT 'uploaded'");
        } elseif ($driver === 'sqlite') {
            // SQLite: No action needed
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Revert CHECK constraint
            DB::statement("ALTER TABLE audio_files DROP CONSTRAINT IF EXISTS audio_files_status_check");
            DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_status_check CHECK (status IN ('uploaded', 'transcribing', 'translating', 'generating_audio', 'completed', 'failed'))");
        }
    }
};

