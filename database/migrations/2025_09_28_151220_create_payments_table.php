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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('stripe_session_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->decimal('amount', 8, 2); // Amount in euros
            $table->integer('credits_purchased');
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->string('currency', 3)->default('eur');
            $table->json('stripe_metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
