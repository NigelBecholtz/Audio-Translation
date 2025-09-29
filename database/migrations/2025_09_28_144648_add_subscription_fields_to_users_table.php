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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('subscription_type', ['free', 'pay_per_use', 'monthly', 'yearly'])->default('free');
            $table->integer('translations_used')->default(0);
            $table->integer('translations_limit')->default(2); // Free tier: 2 translations
            $table->decimal('credits', 8, 2)->default(0.00); // For pay-per-use
            $table->timestamp('subscription_expires_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_type',
                'translations_used',
                'translations_limit',
                'credits',
                'subscription_expires_at',
                'stripe_customer_id',
                'stripe_subscription_id'
            ]);
        });
    }
};
