<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\EngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles & default users
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_user_isolation_hides_other_users_consumer_accounts(): void
    {
        // Create two users
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        // Create an MRU
        $mru = Mru::create([
            'code' => 'TEST_MRU',
            'name' => 'Test MRU',
            'full_identifier' => 'TEST_MRU',
            'status' => 'active',
        ]);

        // Authenticate as User 1 and create a consumer account
        Auth::login($user1);
        $account1 = ConsumerAccount::create([
            'ca_number' => '10230000001',
            'mru_id' => $mru->id,
            'consumer_name' => 'Client One',
            'status' => 'active',
        ]);

        // Verify account belongs to User 1
        $this->assertEquals($user1->id, $account1->user_id);

        // Verify User 1 can see it
        $this->assertEquals(1, ConsumerAccount::count());
        $this->assertNotNull(ConsumerAccount::where('ca_number', '10230000001')->first());

        // Authenticate as User 2
        Auth::login($user2);

        // Verify User 2 CANNOT see User 1's account
        $this->assertEquals(0, ConsumerAccount::count());
        $this->assertNull(ConsumerAccount::where('ca_number', '10230000001')->first());

        // Create a consumer account for User 2
        $account2 = ConsumerAccount::create([
            'ca_number' => '10230000002',
            'mru_id' => $mru->id,
            'consumer_name' => 'Client Two',
            'status' => 'active',
        ]);

        // Verify User 2 can see only their account
        $this->assertEquals(1, ConsumerAccount::count());
        $this->assertNotNull(ConsumerAccount::where('ca_number', '10230000002')->first());
        $this->assertNull(ConsumerAccount::where('ca_number', '10230000001')->first());
    }

    public function test_admin_bypasses_user_isolation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $admin = User::where('email', 'admin@nbpdcl-saas.com')->first();

        $mru = Mru::create([
            'code' => 'TEST_MRU',
            'name' => 'Test MRU',
            'full_identifier' => 'TEST_MRU',
            'status' => 'active',
        ]);

        // Create account as standard user
        Auth::login($user);
        ConsumerAccount::create([
            'ca_number' => '10230000003',
            'mru_id' => $mru->id,
            'consumer_name' => 'Client Three',
            'status' => 'active',
        ]);

        // Log in as Admin
        Auth::login($admin);

        // Admin should see all accounts
        $this->assertEquals(1, ConsumerAccount::count());
        $this->assertNotNull(ConsumerAccount::where('ca_number', '10230000003')->first());
    }

    public function test_engine_service_downloads_and_parses_bill(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Auth::login($user);

        $caNumber = '10230046961';

        $service = new EngineService();
        $results = $service->downloadAndParseBills([$caNumber], $user->id);

        $this->assertEquals(1, $results['total']);
        $this->assertEquals(1, $results['success']);
        
        // Assert record created in DB
        $record = \App\Models\BillRecord::where('ca_number', $caNumber)->first();
        $this->assertNotNull($record);
        $this->assertEquals('MD ASLAM  MAIRUDDIN', $record->consumer_name);
        $this->assertEquals('LAHGARIYA_LALPUR', $record->mru->code);
        $this->assertEquals($user->id, $record->user_id);

        // Assert file isolated under storage
        $expectedPath = "users/{$user->id}/pdfs/{$record->billing_year}/{$record->billing_month}/LAHGARIYA_LALPUR/{$caNumber}.pdf";
        $this->assertEquals($expectedPath, $record->pdf_path);
        Storage::disk('local')->assertExists($expectedPath);

        // Clean up stored file
        Storage::disk('local')->delete($expectedPath);
    }
}
