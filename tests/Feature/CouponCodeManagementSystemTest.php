<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\PaymentSuccessEvent;
use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use App\Services\Coupon\CouponRedemptionService;
use App\Services\Coupon\CouponService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CouponCodeManagementSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Plan $plan;
    protected PlanDuration $duration;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $agentRole = Role::firstOrCreate(['name' => 'user']);

        $this->admin = User::factory()->create([
            'email' => 'admin@nbpdcl-saas.com',
            'status' => 'active',
        ]);
        $this->admin->assignRole($adminRole);

        $this->agent = User::factory()->create([
            'email' => 'agent@example.com',
            'status' => 'active',
        ]);
        $this->agent->assignRole($agentRole);

        $this->walletService->credit($this->agent, 5000.00, 'test_initial_balance');

        $this->plan = Plan::create([
            'name' => 'Professional Agent',
            'description' => 'For professional billing agents',
            'included_mrus' => 5,
            'included_consumers' => 5000,
            'base_price' => 1000.00,
            'is_active' => true,
        ]);

        $this->duration = PlanDuration::create([
            'plan_id' => $this->plan->id,
            'name' => '3 Months',
            'duration_months' => 3,
            'duration_unit' => 'month',
            'duration_value' => 3,
            'discount_percent' => 10.0, // Base price * 3 = 3000 -> 10% off = 2700
            'final_price' => 2700.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_coupons_index(): void
    {
        CouponCode::create([
            'code' => 'SAVE20',
            'type' => 'subscription_discount',
            'discount_kind' => 'percentage',
            'discount_value' => 20,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('SAVE20');
        $response->assertSee('Coupon Code Campaigns');
    }

    public function test_admin_can_create_subscription_discount_coupon(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'launch50',
            'type' => 'subscription_discount',
            'discount_kind' => 'percentage',
            'discount_value' => 50,
            'plan_restriction_id' => $this->plan->id,
            'minimum_amount' => 500,
            'usage_limit_per_user' => 1,
            'usage_limit_total' => 100,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupon_codes', [
            'code' => 'LAUNCH50', // Auto uppercase
            'type' => 'subscription_discount',
            'discount_kind' => 'percentage',
            'discount_value' => 50.00,
            'plan_restriction_id' => $this->plan->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_topup_bonus_coupon_with_slabs(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.coupons.store'), [
            'code' => 'BONUSWALLET',
            'type' => 'topup_bonus',
            'usage_limit_per_user' => 3,
            'is_active' => 1,
            'slabs' => [
                ['min_amount' => 100, 'max_amount' => 1000, 'bonus_percent' => 5],
                ['min_amount' => 1001, 'max_amount' => 5000, 'bonus_percent' => 10],
                ['min_amount' => 5001, 'max_amount' => null, 'bonus_percent' => 15],
            ],
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $coupon = CouponCode::where('code', 'BONUSWALLET')->first();
        $this->assertNotNull($coupon);
        $this->assertEquals(3, $coupon->slabs()->count());
        $this->assertDatabaseHas('coupon_topup_slabs', [
            'coupon_code_id' => $coupon->id,
            'min_amount' => 1001,
            'bonus_percent' => 10.0,
        ]);
    }

    public function test_admin_can_toggle_and_soft_delete_coupon(): void
    {
        $coupon = CouponCode::create([
            'code' => 'TOGGLEME',
            'type' => 'subscription_discount',
            'discount_kind' => 'flat',
            'discount_value' => 100,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        // Toggle
        $this->actingAs($this->admin)->patch(route('admin.coupons.toggle', $coupon));
        $this->assertFalse($coupon->fresh()->is_active);

        // Delete
        $response = $this->actingAs($this->admin)->delete(route('admin.coupons.destroy', $coupon));
        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertSoftDeleted('coupon_codes', ['id' => $coupon->id]);
    }

    public function test_subscription_coupon_stacks_on_duration_discount(): void
    {
        $coupon = CouponCode::create([
            'code' => 'EXTRA10',
            'type' => 'subscription_discount',
            'discount_kind' => 'percentage',
            'discount_value' => 10, // 10% off duration final price (2700 -> 2700 - 270 = 2430)
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->agent)->get(
            route('subscription.quote', ['plan' => $this->plan->id, 'duration' => $this->duration->id]) . '?coupon_code=EXTRA10'
        );

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(2700.0, (float)$data['duration']['final_price']);
        $this->assertEquals(270.0, (float)$data['coupon']['discount_amount']);
        $this->assertEquals(2430.0, (float)$data['final_amount']);
    }

    public function test_coupon_plan_restriction_rejects_other_plans(): void
    {
        $otherPlan = Plan::create([
            'name' => 'Starter Plan',
            'base_price' => 500.00,
            'included_mrus' => 1,
            'included_consumers' => 1000,
            'is_active' => true,
        ]);
        $otherDuration = PlanDuration::create([
            'plan_id' => $otherPlan->id,
            'name' => '1 Month',
            'duration_months' => 1,
            'duration_unit' => 'month',
            'duration_value' => 1,
            'discount_percent' => 0,
            'final_price' => 500.00,
            'is_active' => true,
        ]);

        $coupon = CouponCode::create([
            'code' => 'PROONLY',
            'type' => 'subscription_discount',
            'discount_kind' => 'flat',
            'discount_value' => 200,
            'plan_restriction_id' => $this->plan->id, // Restricted to Pro plan
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->agent)->get(
            route('subscription.quote', ['plan' => $otherPlan->id, 'duration' => $otherDuration->id]) . '?coupon_code=PROONLY'
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertFalse($data['coupon']['valid']);
        $this->assertStringContainsString('Professional Agent', $data['coupon']['message']);
    }

    public function test_agent_wallet_checkout_redeems_subscription_coupon(): void
    {
        $coupon = CouponCode::create([
            'code' => 'FLAT500',
            'type' => 'subscription_discount',
            'discount_kind' => 'flat',
            'discount_value' => 500, // 2700 - 500 = 2200
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $this->plan->id,
            'duration_id' => $this->duration->id,
            'action_mode' => 'new',
            'coupon_code' => 'FLAT500',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        $this->assertEquals(2800.00, $this->walletService->getBalance($this->agent));

        // Check coupon redemption recorded
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_code_id' => $coupon->id,
            'user_id' => $this->agent->id,
            'redeemed_for_type' => 'subscription_payment',
            'original_amount' => 2700.00,
            'discount_or_bonus_amount' => 500.00,
            'final_amount' => 2200.00,
        ]);

        $this->assertEquals(1, $coupon->fresh()->times_used_total);

        // Second use should fail limit per user
        $secondAttempt = $this->actingAs($this->agent)->postJson(route('subscription.subscribe_wallet'), [
            'plan_id' => $this->plan->id,
            'duration_id' => $this->duration->id,
            'action_mode' => 'new',
            'coupon_code' => 'FLAT500',
        ]);

        $secondAttempt->assertStatus(422);
    }

    public function test_topup_payment_success_credits_bonus_to_wallet(): void
    {
        $coupon = CouponCode::create([
            'code' => 'TOPUP10',
            'type' => 'topup_bonus',
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $coupon->slabs()->create([
            'min_amount' => 1000,
            'max_amount' => 5000,
            'bonus_percent' => 10, // 10% bonus on 2000 = ₹200 bonus
        ]);

        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 2000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'gateway_payment_id' => 'pay_test_topup_123',
            'meta' => [
                'coupon_code' => 'TOPUP10',
                'bonus_percent' => 10,
                'bonus_amount' => 200.00,
            ],
        ]);

        // Dispatch PaymentSuccessEvent
        event(new PaymentSuccessEvent($payment, 'pay_test_topup_123'));

        // Wallet was 5000 + 2000 (topup) + 200 (coupon bonus) = 7200
        $this->assertEquals(7200.00, $this->walletService->getBalance($this->agent));

        // Check redemption audit log
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_code_id' => $coupon->id,
            'user_id' => $this->agent->id,
            'redeemed_for_type' => 'topup',
            'original_amount' => 2000.00,
            'discount_or_bonus_amount' => 200.00,
            'final_amount' => 2200.00,
        ]);
    }

    public function test_non_admin_cannot_manage_coupons(): void
    {
        $response = $this->actingAs($this->agent)->get(route('admin.coupons.index'));
        $response->assertStatus(403);
    }
}
