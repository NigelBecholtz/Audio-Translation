<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin add credits to a user', function () {
    $user = User::factory()->create(['credits' => 1]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.add-credits', $user), ['amount' => 4, 'description' => 'Bonus'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $user->fresh()->credits)->toBe(5.0);
    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'admin_id' => $this->admin->id,
        'type' => 'admin_add',
        'amount' => 4,
        'description' => 'Bonus',
    ]);
});

it('lets an admin remove credits from a user', function () {
    $user = User::factory()->create(['credits' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.remove-credits', $user), ['amount' => 2])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $user->fresh()->credits)->toBe(3.0);
    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'admin_id' => $this->admin->id,
        'type' => 'admin_remove',
        'amount' => -2,
        'description' => 'Credits removed by admin',
    ]);
});

it('shows an error when an admin removes more credits than the user has', function () {
    $user = User::factory()->create(['credits' => 1]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.remove-credits', $user), ['amount' => 5])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect((float) $user->fresh()->credits)->toBe(1.0);
    $this->assertDatabaseCount('credit_transactions', 0);
});
