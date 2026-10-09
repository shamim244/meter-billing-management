<?php

namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillHistoryViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Mru $mru;

    protected ConsumerAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'operator_history@nbpdcl-saas.com',
            'status' => 'active',
        ]);

        $this->mru = Mru::create([
            'user_id' => $this->user->id,
            'code' => 'MRU-HIST-01',
            'name' => 'History Test Ward',
        ]);

        $this->account = ConsumerAccount::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => '99990001111',
            'consumer_name' => 'History Test Consumer',
            'tariff_category' => 'DS-II',
            'billing_basis' => 'OK',
            'baseline_amount' => 500.00,
        ]);
    }

    public function test_guest_is_redirected_from_bill_history(): void
    {
        $response = $this->get(route('bills.history', ['ca_number' => '99990001111']));
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_bill_history_page_with_modular_partials(): void
    {
        BillRecord::create([
            'user_id' => $this->user->id,
            'mru_id' => $this->mru->id,
            'ca_number' => $this->account->ca_number,
            'billing_month' => 8,
            'billing_year' => 2026,
            'total_amount' => 750.50,
            'units_consumed' => 85,
            'working_reading' => '1240',
            'current_reading' => '1240',
            'previous_reading' => '1155',
            'review_status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user)->get(route('bills.history', ['ca_number' => $this->account->ca_number]));

        $response->assertOk()
            ->assertSeeText('Back to Dashboard')
            ->assertSeeText('History Test Consumer')
            ->assertSeeText('99990001111')
            ->assertSeeText('Dedicated Monthly Meter Reading History (2D Matrix)')
            ->assertSeeText('Billing History Across Months')
            ->assertSeeText('750.50')
            ->assertSeeText('1240');
    }
}
