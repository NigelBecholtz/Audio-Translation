<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Stripe settings. You can find your API keys
    | in your Stripe dashboard at https://dashboard.stripe.com/apikeys
    |
    */

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Credit Packages
    |--------------------------------------------------------------------------
    |
    | Define the available credit packages for purchase
    |
    */

    'credit_packages' => [
        'starter' => [
            'name' => 'Starter Credits',
            'credits' => 10,
            'price' => 5.00, // €5.00
            'price_per_credit' => 0.50,
            'description' => '10 audio vertalingen voor €5.00. Perfect om te proberen!',
            'stripe_price_id' => env('STRIPE_STARTER_PRICE_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default cost per translation and other settings
    |
    */

    'default_cost_per_translation' => 0.50, // €0.50 per translation
    'currency' => 'eur',
];
