<?php

namespace Tests\Feature;

use App\Models\IssueReport;
use App\Models\Mru;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class IssueReportingAndAiTriageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_authenticated_user_can_submit_issue_report(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('issues.report'), [
            'title' => 'Average reading calculation offset',
            'description' => 'When decreasing by 20% and then increasing by 10%, units did not calculate from normal base.',
            'category' => 'calculation',
            'severity' => 'high',
            'ca_number' => '102300783538',
            'mru_id' => $mru->id,
            'billing_month' => 9,
            'billing_year' => 2026,
            'page_url' => 'http://localhost/dashboard?mru_id='.$mru->id.'&month=9&year=2026',
            'client_context' => [
                'user_agent' => 'Mozilla/5.0 Test',
                'screen' => '1920x1080',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'issue_code',
            'id',
        ]);

        $issueCode = $response->json('issue_code');
        $this->assertNotEmpty($issueCode);
        $this->assertStringStartsWith('BUG-', $issueCode);

        $this->assertDatabaseHas('issue_reports', [
            'issue_code' => $issueCode,
            'user_id' => $user->id,
            'title' => 'Average reading calculation offset',
            'category' => 'calculation',
            'severity' => 'high',
            'status' => 'pending',
            'ca_number' => '102300783538',
            'mru_id' => $mru->id,
        ]);
    }

    public function test_issue_report_requires_title_and_description(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('issues.report'), [
            'category' => 'calculation',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_admin_can_view_issues_list_and_filter_by_status(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $user = User::factory()->create(['status' => 'active']);

        $pending = IssueReport::create([
            'user_id' => $user->id,
            'title' => 'Bug 1',
            'description' => 'Test bug 1',
            'status' => 'pending',
        ]);

        $verified = IssueReport::create([
            'user_id' => $user->id,
            'title' => 'Bug 2',
            'description' => 'Test bug 2',
            'status' => 'verified',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.issues.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee($pending->issue_code);
        $response->assertDontSee($verified->issue_code);
    }

    public function test_admin_can_mark_issue_as_spam(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $issue = IssueReport::create([
            'title' => 'Test spam report',
            'description' => 'Random gibberish text',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.issues.spam', $issue), [
            'admin_notes' => 'User tested button with random text.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('spam', $issue->fresh()->status);
        $this->assertStringContainsString('User tested button', $issue->fresh()->admin_notes);
    }

    public function test_admin_can_verify_bug_for_ai_resolution(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $issue = IssueReport::create([
            'title' => 'Real calculation bug',
            'description' => 'Formula discrepancy in bulk projection',
            'status' => 'pending',
            'severity' => 'medium',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.issues.verify', $issue), [
            'severity' => 'critical',
            'admin_notes' => 'Confirmed in production MRU 0244.',
        ]);

        $response->assertRedirect();
        $fresh = $issue->fresh();
        $this->assertEquals('verified', $fresh->status);
        $this->assertEquals('critical', $fresh->severity);
        $this->assertEquals($admin->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
    }

    public function test_to_ai_prompt_generates_markdown_bundle(): void
    {
        $user = User::factory()->create(['name' => 'Shamim', 'email' => 'shamim244d@gmail.com']);
        $mru = Mru::create(['user_id' => $user->id, 'code' => '0244', 'name' => 'NISARBHATI', 'status' => 'active']);

        $issue = IssueReport::create([
            'user_id' => $user->id,
            'title' => 'Compounding tuning calculation issue',
            'description' => 'Normal base is 50, -20% gives 40, but +10% gave 44 instead of 55.',
            'category' => 'calculation',
            'severity' => 'high',
            'status' => 'verified',
            'ca_number' => '102300783538',
            'mru_id' => $mru->id,
            'billing_month' => 9,
            'billing_year' => 2026,
            'admin_notes' => 'Priority fix for current billing period.',
        ]);

        $prompt = $issue->toAiPrompt();

        $this->assertStringContainsString($issue->issue_code, $prompt);
        $this->assertStringContainsString('Shamim (ID: '.$user->id, $prompt);
        $this->assertStringContainsString('102300783538', $prompt);
        $this->assertStringContainsString('NISARBHATI', $prompt);
        $this->assertStringContainsString('php artisan issue:resolve', $prompt);
    }

    public function test_artisan_issue_commands_lifecycle(): void
    {
        $issue = IssueReport::create([
            'title' => 'Database constraint violation on duplicate MRU code',
            'description' => 'Adding identical MRU codes for different users failed.',
            'category' => 'mru_sync',
            'severity' => 'high',
            'status' => 'verified',
        ]);

        // 1. Test issue:list
        $this->artisan('issue:list')
            ->expectsOutputToContain($issue->issue_code)
            ->assertExitCode(0);

        // 2. Test issue:show
        Artisan::call('issue:show', ['identifier' => $issue->issue_code]);
        $output = Artisan::output();
        $this->assertStringContainsString($issue->issue_code, $output);
        $this->assertStringContainsString('Action Instructions for AI Agent', $output);

        // 3. Test issue:resolve
        $this->artisan('issue:resolve', [
            'identifier' => $issue->issue_code,
            '--notes' => 'Scoped MRU unique code to user_id in database migration.',
        ])
            ->expectsOutputToContain('marked as RESOLVED')
            ->assertExitCode(0);

        $fresh = $issue->fresh();
        $this->assertEquals('resolved', $fresh->status);
        $this->assertEquals('Scoped MRU unique code to user_id in database migration.', $fresh->ai_resolution_notes);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_user_can_track_issue_by_reference_code(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $issue = IssueReport::create([
            'user_id' => $user->id,
            'title' => 'Arrow keys scroll bug',
            'description' => 'Up and down arrows hijacked by card navigation.',
            'category' => 'ui_display',
            'status' => 'resolved',
            'ai_resolution_notes' => 'Restored native vertical scrolling on ArrowUp/Down.',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('issues.track', ['code' => $issue->issue_code]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'issue' => [
                    'issue_code' => $issue->issue_code,
                    'title' => 'Arrow keys scroll bug',
                    'status' => 'resolved',
                    'status_label' => 'Resolved',
                    'ai_resolution_notes' => 'Restored native vertical scrolling on ArrowUp/Down.',
                    'is_owner' => true,
                ],
            ]);
    }

    public function test_tracking_nonexistent_code_returns_404(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $response = $this->actingAs($user)->getJson(route('issues.track', ['code' => 'BUG-99999999-FAKE']));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_retrieve_my_reports_api(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        IssueReport::create([
            'user_id' => $user1->id,
            'title' => 'User 1 Bug',
            'description' => 'Desc 1',
            'status' => 'pending',
        ]);

        IssueReport::create([
            'user_id' => $user2->id,
            'title' => 'User 2 Bug',
            'description' => 'Desc 2',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user1)->getJson(route('issues.my_reports'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
            ])
            ->assertJsonPath('issues.0.title', 'User 1 Bug');
    }

    public function test_user_can_view_user_panel_issues_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $issue = IssueReport::create([
            'user_id' => $user->id,
            'title' => 'Sample calculation bug',
            'description' => 'Test description',
            'status' => 'resolved',
            'ai_resolution_notes' => 'Fixed algorithm base formula',
        ]);

        $response = $this->actingAs($user)->get(route('user-panel.issues'));

        $response->assertOk()
            ->assertSee($issue->issue_code)
            ->assertSee('Sample calculation bug')
            ->assertSee('Fixed algorithm base formula');
    }
}
