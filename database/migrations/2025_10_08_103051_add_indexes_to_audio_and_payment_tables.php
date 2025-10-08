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
        Schema::table('audio_files', function (Blueprint $table) {
            $table->index('user_id', 'audio_files_user_id_index');
            $table->index('status', 'audio_files_status_index');
            $table->index('created_at', 'audio_files_created_at_index');
            $table->index(['user_id', 'status'], 'audio_files_user_id_status_index');
            $table->index(['user_id', 'created_at'], 'audio_files_user_id_created_at_index');
        });

        Schema::table('text_to_audio', function (Blueprint $table) {
            $table->index('user_id', 'text_to_audio_user_id_index');
            $table->index('status', 'text_to_audio_status_index');
            $table->index('created_at', 'text_to_audio_created_at_index');
            $table->index(['user_id', 'status'], 'text_to_audio_user_id_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('user_id', 'payments_user_id_index');
            $table->index('status', 'payments_status_index');
            $table->index('created_at', 'payments_created_at_index');
            $table->index(['user_id', 'status'], 'payments_user_id_status_index');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->index('user_id', 'credit_transactions_user_id_index');
            $table->index('type', 'credit_transactions_type_index');
            $table->index('created_at', 'credit_transactions_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_files', function (Blueprint $table) {
            $table->dropIndex('audio_files_user_id_index');
            $table->dropIndex('audio_files_status_index');
            $table->dropIndex('audio_files_created_at_index');
            $table->dropIndex('audio_files_user_id_status_index');
            $table->dropIndex('audio_files_user_id_created_at_index');
        });

        Schema::table('text_to_audio', function (Blueprint $table) {
            $table->dropIndex('text_to_audio_user_id_index');
            $table->dropIndex('text_to_audio_status_index');
            $table->dropIndex('text_to_audio_created_at_index');
            $table->dropIndex('text_to_audio_user_id_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_user_id_index');
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_created_at_index');
            $table->dropIndex('payments_user_id_status_index');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropIndex('credit_transactions_user_id_index');
            $table->dropIndex('credit_transactions_type_index');
            $table->dropIndex('credit_transactions_created_at_index');
        });
    }
};