<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmartAvgGuardrailsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_accounts_and_bill_records_have_guardrail_and_exclusion_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'custom_avg_units'));
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'is_avg_excluded'));
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'avg_exclude_reason'));

        $this->assertTrue(Schema::hasColumn('bill_records', 'custom_avg_units'));
        $this->assertTrue(Schema::hasColumn('bill_records', 'is_avg_excluded'));
        $this->assertTrue(Schema::hasColumn('bill_records', 'avg_exclude_reason'));
    }
}
