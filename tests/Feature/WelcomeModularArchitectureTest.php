<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomeModularArchitectureTest extends TestCase
{
    public function test_welcome_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('⚡ v2.4 SaaS PRO');
        $response->assertSee('NBPDCL Billing');
        $response->assertSee('Automate 50,000+ Electricity Bills');
        $response->assertSee('The Old Manual Way vs. The Automated Way');
        $response->assertSee('Try the 4-Box Meter Reading Engine');
        $response->assertSee('Built to Handle Millions of Records');
        $response->assertSee('How Much Time', false);
        $response->assertSee('Transparent Plans for Every Agency Scale');
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Ready to 10x Your Meter Reading Speed?');
        $response->assertSee('welcome.css');
        $response->assertSee('welcome-app.js');
    }

    public function test_welcome_static_assets_exist_on_disk(): void
    {
        $this->assertFileExists(public_path('css/welcome/welcome.css'));
        $this->assertFileExists(public_path('js/welcome/welcome-app.js'));

        $jsContent = file_get_contents(public_path('js/welcome/welcome-app.js'));
        $this->assertStringContainsString('function marketingApp()', $jsContent);
        $this->assertStringNotContainsString('{{', $jsContent, 'External JS must not contain raw Blade directives.');
    }

    public function test_welcome_blade_template_obeys_line_count_invariant(): void
    {
        $templatePath = resource_path('views/welcome.blade.php');
        $this->assertFileExists($templatePath);

        $lineCount = count(file($templatePath));
        $this->assertLessThanOrEqual(
            120,
            $lineCount,
            "Master welcome.blade.php must not exceed 120 lines. Currently: {$lineCount} lines."
        );
    }
}
