<?php

namespace App\Constants;

class CreditConstants
{
    // Credit Costs
    public const COST_PER_TRANSLATION = 0.50; // €0.50 per translation
    
    // Free Tier
    public const FREE_TIER_TRANSLATIONS = 2;
    
    // Credit Packages
    public const STARTER_PACKAGE_CREDITS = 10;
    public const STARTER_PACKAGE_PRICE = 5.00; // €5.00
    
    // Transaction Types
    public const TRANSACTION_TYPE_USAGE = 'usage';
    public const TRANSACTION_TYPE_PURCHASE = 'purchase';
    public const TRANSACTION_TYPE_ADMIN_ADD = 'admin_add';
    public const TRANSACTION_TYPE_ADMIN_REMOVE = 'admin_remove';
    public const TRANSACTION_TYPE_REFUND = 'refund';
    
    // Payment Status
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_COMPLETED = 'completed';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';
    
    // Subscription Types
    public const SUBSCRIPTION_TYPE_FREE = 'free';
    public const SUBSCRIPTION_TYPE_PAY_PER_USE = 'pay_per_use';
}
