<?php

use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin translate without free translations or credits', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'translations_used' => 5,
        'translations_limit' => 2,
        'credits' => 0,
    ]);

    expect($admin->hasUnlimitedCredits())->toBeTrue()
        ->and($admin->canMakeTranslation())->toBeTrue()
        ->and($admin->hasEnoughCredits(100))->toBeTrue();
});

it('never deducts credits or free translations from an admin', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'translations_used' => 0,
        'translations_limit' => 2,
        'credits' => 0,
    ]);

    app(CreditService::class)->deductCredit($admin, 'Test usage');

    $fresh = $admin->fresh();

    expect((float) $fresh->credits)->toBe(0.0)
        ->and((int) $fresh->translations_used)->toBe(0);

    $this->assertDatabaseCount('credit_transactions', 0);
});

it('still deducts credits from a non-admin', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'translations_used' => 2,
        'translations_limit' => 2,
        'credits' => 5,
    ]);

    app(CreditService::class)->deductCredit($user, 'Test usage');

    expect((float) $user->fresh()->credits)->toBe(4.5);
});

it('shows an unlimited balance on the credits page for an admin', function () {
    $admin = User::factory()->create(['is_admin' => true, 'credits' => 0]);

    $this->withoutVite()
        ->actingAs($admin)
        ->get(route('payment.credits'))
        ->assertOk()
        ->assertSee('∞')
        ->assertSee('Unlimited (admin)');
});
