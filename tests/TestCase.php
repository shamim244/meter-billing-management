<?php

namespace Tests;

use App\Models\SystemSetting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // STRICT INVARIANT GUARD: Prevent tests from ever touching real MySQL databases!
        if (config('database.default') === 'mysql' || config('database.connections.mysql.database') === 'nbpdcl_billing') {
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite.database' => ':memory:']);
        }

        return $app;
    }

    protected function tearDown(): void
    {
        SystemSetting::clearRuntimeCache();
        parent::tearDown();
    }
}
