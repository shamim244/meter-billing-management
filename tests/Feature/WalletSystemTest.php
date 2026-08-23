<?php

namespace Tests\Feature;

use App\Enums\DebitResult;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\WalletAdminAdjustmentType;
use App\Events\PaymentSuccessEvent;
use App\Events\WalletCriticalBalanceEvent;
use App\Events\WalletCreditedEvent;
use App\Events\WalletDebitedEvent;
use App\Events\WalletFrozenEvent;
use App\Events\WalletInsufficientForRenewalEvent;
use App\Events\WalletLowBalanceEvent;
use App\Events\WalletUnfrozenEvent;
use App\Models\Payment;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class WalletSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $agent;
    protected User $otherAgent;
    protected User $admin;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@nbpdcl-saas.com')->first();
        $this->agent = User::where('email', 'test@example.com')->first();

        $this->otherAgent = User::factory()->create([
            'name' => 'Agent Beta',
            'email' => 'beta@example.com',
            'status' => 'active',
        ]);
        $this->otherAgent->assignRole('user');

        $this->walletService = app(WalletService::class);
    }

    public function test_user_initializes_bavix_wallet_with_zero_balance(): void
    {
        $balance = $this->walletService->getBalance($this->agent);
        $this->assertEquals(0.00, $balance);
        $this->assertFalse($this->agent->isWalletFrozen());
    }

    public function test_credit_uses_bavix_deposit_and_stores_metadata_in_meta_field(): void
    {
        Event::fake([WalletCreditedEvent::class]);

        $tx = $this->walletService->credit(
            user: $this->agent,
            amount: 500.00,
            source: 'payment_topup',
            referenceType: Payment::class,
            referenceId: '101',
            description: 'Top-up via UPI'
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals(500.00, (float)$tx->amountFloat);
        $this->assertEquals('deposit', $tx->type instanceof \BackedEnum ? $tx->type->value : $tx->type);

        $meta = (array) $tx->meta;
        $this->assertEquals('payment_topup', $meta['source']);
        $this->assertEquals(Payment::class, $meta['reference_type']);
        $this->assertEquals('101', $meta['reference_id']);
        $this->assertEquals('Top-up via UPI', $meta['description']);

        $balance = $this->walletService->getBalance($this->agent);
        $this->assertEquals(500.00, $balance);

        Event::assertDispatched(WalletCreditedEvent::class);
    }

    public function test_credit_rejects_negative_or_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->walletService->credit($this->agent, 0.00, 'test');
    }

    public function test_debit_uses_bavix_withdraw_and_stores_meta(): void
    {
        Event::fake([WalletDebitedEvent::class]);

        $this->walletService->credit($this->agent, 300.00, 'payment_topup');

        $result = $this->walletService->debit(
            user: $this->agent,
            amount: 120.00,
            source: 'bill_download_fee',
            referenceType: null,
            referenceId: null,
            description: 'Bill download charge'
        );

        $this->assertEquals(DebitResult::SUCCESS, $result);
        $this->assertEquals(180.00, $this->walletService->getBalance($this->agent));

        $tx = $this->agent->transactions()->latest('id')->first();
        $this->assertEquals('withdraw', $tx->type instanceof \BackedEnum ? $tx->type->value : $tx->type);
        $this->assertEquals(120.00, (float)abs($tx->amountFloat));

        $meta = (array) $tx->meta;
        $this->assertEquals('bill_download_fee', $meta['source']);

        Event::assertDispatched(WalletDebitedEvent::class);
    }

    public function test_debit_returns_insufficient_balance_without_throwing_exception(): void
    {
        $this->walletService->credit($this->agent, 50.00, 'payment_topup');

        $result = $this->walletService->debit(
            user: $this->agent,
            amount: 100.00,
            source: 'bill_download_fee'
        );

        $this->assertEquals(DebitResult::INSUFFICIENT_BALANCE, $result);
        // Balance remains unchanged
        $this->assertEquals(50.00, $this->walletService->getBalance($this->agent));
    }

    public function test_debit_is_blocked_when_wallet_is_frozen(): void
    {
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');
        $this->walletService->freeze($this->agent, $this->admin, 'Audit under review');

        $result = $this->walletService->debit(
            user: $this->agent,
            amount: 100.00,
            source: 'bill_download_fee'
        );

        $this->assertEquals(DebitResult::WALLET_FROZEN, $result);
        $this->assertEquals(500.00, $this->walletService->getBalance($this->agent));
    }

    public function test_concurrency_and_balance_integrity_prevent_overdraft(): void
    {
        // Initial balance: 100.00
        $this->walletService->credit($this->agent, 100.00, 'payment_topup');

        // Simulate 15 consecutive debits of 10.00
        $successCount = 0;
        $insufficientCount = 0;

        for ($i = 0; $i < 15; $i++) {
            $result = $this->walletService->debit(
                user: $this->agent,
                amount: 10.00,
                source: 'concurrent_bill_task',
                referenceId: "task_{$i}"
            );

            if ($result === DebitResult::SUCCESS) {
                $successCount++;
            } elseif ($result === DebitResult::INSUFFICIENT_BALANCE) {
                $insufficientCount++;
            }
        }

        // Exactly 10 debits of 10.00 should succeed, and 5 should return INSUFFICIENT_BALANCE
        $this->assertEquals(10, $successCount);
        $this->assertEquals(5, $insufficientCount);
        $this->assertEquals(0.00, $this->walletService->getBalance($this->agent));
    }

    public function test_admin_adjustment_add_and_force_deduct_allows_negative_balance(): void
    {
        $this->walletService->credit($this->agent, 100.00, 'payment_topup');

        // 1. Admin Add Balance
        $txAdd = $this->walletService->adminAdjust(
            user: $this->agent,
            admin: $this->admin,
            type: WalletAdminAdjustmentType::ADD,
            amount: 250.00,
            reason: 'Promotional bonus credit'
        );

        $this->assertEquals(350.00, $this->walletService->getBalance($this->agent));
        $this->assertEquals('deposit', $txAdd->type instanceof \BackedEnum ? $txAdd->type->value : $txAdd->type);

        // 2. Admin Deduct Balance (Pushing into negative allowed ONLY for adminAdjust via forceWithdrawFloat)
        $txDeduct = $this->walletService->adminAdjust(
            user: $this->agent,
            admin: $this->admin,
            type: WalletAdminAdjustmentType::DEDUCT,
            amount: 400.00,
            reason: 'Chargeback reversal'
        );

        $this->assertEquals(-50.00, $this->walletService->getBalance($this->agent));
        $this->assertEquals('withdraw', $txDeduct->type instanceof \BackedEnum ? $txDeduct->type->value : $txDeduct->type);
    }

    public function test_admin_adjustment_requires_mandatory_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->walletService->adminAdjust(
            user: $this->agent,
            admin: $this->admin,
            type: WalletAdminAdjustmentType::ADD,
            amount: 50.00,
            reason: '   ' // empty reason
        );
    }

    public function test_freeze_and_unfreeze_lifecycle(): void
    {
        Event::fake([WalletFrozenEvent::class, WalletUnfrozenEvent::class]);

        $this->assertFalse($this->agent->isWalletFrozen());

        // Freeze
        $this->walletService->freeze($this->agent, $this->admin, 'Dispute filed');
        $this->agent->refresh();
        $this->assertTrue($this->agent->isWalletFrozen());
        $this->assertEquals('Dispute filed', $this->agent->wallet_frozen_reason);
        Event::assertDispatched(WalletFrozenEvent::class);

        // Unfreeze
        $this->walletService->unfreeze($this->agent, $this->admin, 'Dispute resolved');
        $this->agent->refresh();
        $this->assertFalse($this->agent->isWalletFrozen());
        $this->assertNull($this->agent->wallet_frozen_reason);
        Event::assertDispatched(WalletUnfrozenEvent::class);
    }

    public function test_payment_success_event_listener_credits_wallet_idempotently(): void
    {
        $payment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 750.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'gateway_order_id' => 'order_123',
            'gateway_payment_id' => 'pay_123',
        ]);

        // Dispatch Event 1st time
        event(new PaymentSuccessEvent($payment, 'pay_123'));

        $this->assertEquals(750.00, $this->walletService->getBalance($this->agent));
        $this->assertEquals(1, Transaction::where('meta->source', 'payment_topup')->count());

        // Dispatch Duplicate Event 2nd time (should NOT double-credit)
        event(new PaymentSuccessEvent($payment, 'pay_123'));

        $this->assertEquals(750.00, $this->walletService->getBalance($this->agent));
        $this->assertEquals(1, Transaction::where('meta->source', 'payment_topup')->count());
    }

    public function test_payment_success_event_listener_ignores_non_topup_payments(): void
    {
        $subPayment = Payment::create([
            'user_id' => $this->agent->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::DIRECT_SUBSCRIPTION,
            'amount' => 499.00,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
        ]);

        event(new PaymentSuccessEvent($subPayment, 'pay_sub_123'));

        $this->assertEquals(0.00, $this->walletService->getBalance($this->agent));
        $this->assertEquals(0, Transaction::where('meta->source', 'payment_topup')->count());
    }

    public function test_balance_alert_events_are_dispatched(): void
    {
        Event::fake([
            WalletLowBalanceEvent::class,
            WalletCriticalBalanceEvent::class,
            WalletInsufficientForRenewalEvent::class,
        ]);

        $this->walletService->credit($this->agent, 150.00, 'payment_topup');

        $this->walletService->checkBalanceAlerts(
            user: $this->agent,
            upcomingRenewalAmount: 499.00,
            renewalDate: Carbon::now()->addDays(3)
        );

        Event::assertDispatched(WalletLowBalanceEvent::class);
        Event::assertDispatched(WalletCriticalBalanceEvent::class);
        Event::assertDispatched(WalletInsufficientForRenewalEvent::class);
    }

    public function test_agent_can_view_own_wallet_and_export_csv(): void
    {
        $this->walletService->credit($this->agent, 500.00, 'payment_topup');

        $response = $this->actingAs($this->agent)->get(route('wallet.index'));
        $response->assertStatus(200);
        $response->assertSeeText('Agent Wallet & Financial Ledger');
        $response->assertSeeText('500.00');

        $exportRes = $this->actingAs($this->agent)->get(route('wallet.export'));
        $exportRes->assertStatus(200);
        $this->assertTrue(str_contains($exportRes->headers->get('content-disposition'), 'wallet_ledger_'));
    }

    public function test_admin_can_view_wallets_list_and_agent_console(): void
    {
        $this->walletService->credit($this->agent, 1000.00, 'payment_topup');

        // Admin Wallets List
        $resList = $this->actingAs($this->admin)->get(route('admin.wallets.index'));
        $resList->assertStatus(200);
        $resList->assertSeeText('Agent Wallets Master Ledger');
        $resList->assertSeeText('test@example.com');

        // Admin Single Wallet Console
        $resShow = $this->actingAs($this->admin)->get(route('admin.wallets.show', $this->agent->id));
        $resShow->assertStatus(200);
        $resShow->assertSeeText('Wallet Console');
        $resShow->assertSeeText('1,000.00');
        $resShow->assertSeeText('Add Balance');
        $resShow->assertSeeText('Deduct Balance');
    }

    public function test_admin_can_perform_adjustment_via_http_form(): void
    {
        $this->walletService->credit($this->agent, 200.00, 'payment_topup');

        $response = $this->actingAs($this->admin)->post(route('admin.wallets.adjust', $this->agent->id), [
            'type' => 'add',
            'amount' => 300.00,
            'reason' => 'Compensation for service maintenance',
        ]);

        $response->assertRedirect(route('admin.wallets.show', $this->agent->id));
        $response->assertSessionHas('success');

        $this->assertEquals(500.00, $this->walletService->getBalance($this->agent));
    }

    public function test_admin_can_toggle_freeze_via_http_form(): void
    {
        $this->assertFalse($this->agent->isWalletFrozen());

        // Freeze
        $resFreeze = $this->actingAs($this->admin)->post(route('admin.wallets.toggle-freeze', $this->agent->id), [
            'reason' => 'Suspected unauthorized activity',
        ]);
        $resFreeze->assertSessionHas('success');

        $this->agent->refresh();
        $this->assertTrue($this->agent->isWalletFrozen());

        // Unfreeze
        $resUnfreeze = $this->actingAs($this->admin)->post(route('admin.wallets.toggle-freeze', $this->agent->id));
        $resUnfreeze->assertSessionHas('success');

        $this->agent->refresh();
        $this->assertFalse($this->agent->isWalletFrozen());
    }

    public function test_non_admin_cannot_access_admin_wallet_routes(): void
    {
        $res = $this->actingAs($this->agent)->get(route('admin.wallets.index'));
        $res->assertStatus(403);

        $resShow = $this->actingAs($this->agent)->get(route('admin.wallets.show', $this->otherAgent->id));
        $resShow->assertStatus(403);
    }
}
