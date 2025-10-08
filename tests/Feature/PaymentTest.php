<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_credits_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('payment.credits'));

        $response->assertStatus(200);
        $response->assertViewIs('payment.credits');
        $response->assertViewHas('user');
        $response->assertViewHas('creditPackage');
    }

    public function test_guest_cannot_view_credits_page()
    {
        $response = $this->get(route('payment.credits'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_credit_calculation_is_correct()
    {
        $user = User::factory()->create([
            'translations_used' => 0,
            'translations_limit' => 2,
            'credits' => 5.0
        ]);

        // Should have 2 free + (5.0 / 0.5) = 2 + 10 = 12 translations
        $this->assertEquals(12, $user->getRemainingTranslations());
    }

    public function test_user_without_credits_cannot_translate()
    {
        $user = User::factory()->create([
            'translations_used' => 2,
            'translations_limit' => 2,
            'credits' => 0
        ]);

        $this->assertFalse($user->canMakeTranslation());
    }

    public function test_user_with_credits_can_translate()
    {
        $user = User::factory()->create([
            'translations_used' => 2,
            'translations_limit' => 2,
            'credits' => 1.0
        ]);

        $this->assertTrue($user->canMakeTranslation());
    }
}
