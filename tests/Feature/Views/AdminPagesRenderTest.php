<?php

use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Overview');
});

it('renders the admin users page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['name' => 'Regular User', 'credits' => 3]);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Regular User');
});

it('renders the admin payments page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    Payment::create([
        'user_id' => $user->id,
        'stripe_session_id' => 'cs_test_123',
        'amount' => 9.99,
        'credits_purchased' => 10,
        'status' => 'completed',
        'currency' => 'eur',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('Payments')
        ->assertSee('Paid');
});

it('renders the admin audio files page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'sample.mp3',
        'file_path' => 'audio/sample.mp3',
        'file_size' => 1000,
        'source_language' => 'en',
        'target_language' => 'nl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.audio-files'))
        ->assertOk()
        ->assertSee('Audio files')
        ->assertSee('sample.mp3');
});

it('renders the admin credit history page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'History User', 'credits' => 5]);

    CreditTransaction::create([
        'user_id' => $user->id,
        'admin_id' => $admin->id,
        'amount' => 5,
        'type' => 'admin_add',
        'description' => 'Welcome bonus',
        'balance_after' => 5,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.credit-history', $user))
        ->assertOk()
        ->assertSee('Credit history')
        ->assertSee('History User')
        ->assertSee('Welcome bonus');
});
