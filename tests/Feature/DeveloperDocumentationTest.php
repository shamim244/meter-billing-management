<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeveloperDocumentationTest extends TestCase
{
    protected function tearDown(): void
    {
        config(['app.testing_installer' => false]);
        parent::tearDown();
    }

    public function test_documentation_root_serves_docsify_index_html(): void
    {
        $response = $this->get('/documentation');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('NBPDCL SaaS Docs');
        $response->assertSee('docsify.min.js');
    }

    public function test_documentation_index_html_explicitly_serves_html(): void
    {
        $response = $this->get('/documentation/index.html');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('protocol-warning');
        $response->assertSee('window.$docsify');
    }

    public function test_documentation_serves_readme_markdown(): void
    {
        $response = $this->get('/documentation/README.md');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('Developer Side', false);
        $response->assertSee('AI Agent Side', false);
    }

    public function test_documentation_serves_sidebar_markdown(): void
    {
        $response = $this->get('/documentation/_sidebar.md');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('developer/01-system-architecture.md');
        $response->assertSee('ai-agent/llms.txt');
    }

    public function test_documentation_serves_developer_chapters(): void
    {
        $response = $this->get('/documentation/developer/01-system-architecture.md');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('High-Level Architecture Diagram');
        $response->assertSee('Adaptive Compression');
    }

    public function test_documentation_resolves_files_without_md_extension(): void
    {
        $response = $this->get('/documentation/developer/01-system-architecture');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('High-Level Architecture Diagram');
    }

    public function test_documentation_serves_ai_agent_resources(): void
    {
        $response = $this->get('/documentation/ai-agent/llms.txt');

        $response->assertStatus(200);
        $response->assertSee('LLM Context Map');

        $rulesResponse = $this->get('/documentation/ai-agent/ai-agent-guidelines.md');
        $rulesResponse->assertStatus(200);
        $rulesResponse->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $rulesResponse->assertSee('REAL DATA PROTECTION');
    }

    public function test_directory_traversal_is_safely_contained(): void
    {
        $response = $this->get('/documentation/....//....//composer.json');

        // Must never leak root composer.json
        $response->assertDontSee('"name": "laravel/laravel"');
    }

    public function test_documentation_is_accessible_when_application_is_uninstalled(): void
    {
        config(['app.testing_installer' => true]);

        $lockPath = storage_path('installed.lock');
        $hadLock = File::exists($lockPath);
        $savedContent = $hadLock ? File::get($lockPath) : null;

        if ($hadLock) {
            File::delete($lockPath);
        }

        try {
            $response = $this->get('/documentation');

            $response->assertStatus(200);
            $response->assertSee('NBPDCL SaaS Docs');
        } finally {
            if ($hadLock && $savedContent !== null) {
                File::put($lockPath, $savedContent);
            }
        }
    }

    public function test_documentation_serves_local_assets_with_immutable_cache(): void
    {
        $response = $this->get('/documentation/assets/docsify.min.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $this->assertStringContainsString('max-age=604800', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
    }

    public function test_documentation_returns_304_when_etag_matches(): void
    {
        $initial = $this->get('/documentation/README.md');
        $etag = $initial->headers->get('ETag');

        $this->assertNotEmpty($etag);

        $cachedResponse = $this->withHeaders([
            'If-None-Match' => $etag,
        ])->get('/documentation/README.md');

        $cachedResponse->assertStatus(304);
    }
}
