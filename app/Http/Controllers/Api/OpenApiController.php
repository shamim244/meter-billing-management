<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OpenApiController extends Controller
{
    protected static ?string $cachedJson = null;

    protected static ?string $cachedEtag = null;

    /**
     * Return OpenAPI 3.0.3 machine-readable specification for AI agents and Swagger tooling.
     */
    public function schema(Request $request): Response
    {
        $baseUrl = url('/api/v1');

        if (static::$cachedJson === null) {
            $spec = $this->buildSpecification($baseUrl);
            static::$cachedJson = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            static::$cachedEtag = '"'.md5((string) static::$cachedJson).'"';
        }

        $etag = (string) static::$cachedEtag;
        $clientEtag = $request->header('If-None-Match');
        if ($clientEtag && trim($clientEtag) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=3600, must-revalidate',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        return response(static::$cachedJson, 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600, must-revalidate',
            'ETag' => $etag,
        ]);
    }

    /**
     * Build the raw OpenAPI 3.0.3 definition array.
     */
    protected function buildSpecification(string $baseUrl): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'NBPDCL / BSPHCL Electricity Billing & Automation REST API',
                'description' => 'Universal REST API powering field meter reading scripts (Python ADB), mobile clients (Flutter/Android), and AI tool-calling agents for Bihar Power Distribution.',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'NBPDCL SaaS Platform Engineering',
                    'url' => url('/docs/api'),
                ],
            ],
            'servers' => [
                [
                    'url' => $baseUrl,
                    'description' => 'Current Active API Server',
                ],
            ],
            'security' => [
                ['BearerAuth' => []],
                ['ApiKeyAuth' => []],
            ],
            'paths' => [
                '/auth/me' => [
                    'get' => [
                        'summary' => 'Get Authenticated Agent Profile & Active Stats',
                        'description' => 'Returns operator profile, active subscription plan, shortcut keys, and record statistics.',
                        'operationId' => 'getAuthMe',
                        'responses' => [
                            '200' => [
                                'description' => 'Authenticated operator profile.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'user' => [
                                                'id' => 9,
                                                'name' => 'Shamim Akhtar',
                                                'email' => 'shamim244d@gmail.com',
                                                'plan' => 'Free Starter',
                                                'stats' => ['mrus' => 7, 'consumers' => 667, 'bills' => 1141],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        ],
                    ],
                ],
                '/mrus' => [
                    'get' => [
                        'summary' => 'List All MRU Workspaces',
                        'description' => 'Returns all village/block MRUs belonging to the authenticated tenant.',
                        'operationId' => 'listMrus',
                        'responses' => [
                            '200' => [
                                'description' => 'Array of active MRU workspaces.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'mrus' => [
                                                ['id' => 23, 'code' => '0244', 'name' => 'NISARBHATI', 'consumer_count' => 152],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        ],
                    ],
                ],
                '/bills' => [
                    'get' => [
                        'summary' => 'Query Filtered & Sorted Monthly Bills',
                        'description' => 'Provides exact web dashboard parity: filter by MRU, month, year, review status, search string, column sort, and priority sequence (pdcs).',
                        'operationId' => 'listBills',
                        'parameters' => [
                            ['name' => 'mru_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'description' => 'MRU ID or unique Code (e.g. 0244)'],
                            ['name' => 'month', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12], 'description' => 'Billing month (1-12)'],
                            ['name' => 'year', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer'], 'description' => 'Billing year (e.g. 2026)'],
                            ['name' => 'status', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['all', 'pending', 'submitted', 'doubt', 'critical']], 'description' => 'Review status filter'],
                            ['name' => 'sort_col', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['ca_number', 'amount', 'units', 'current_reading', 'previous_reading']], 'description' => 'Sort column'],
                            ['name' => 'sort_asc', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'boolean'], 'description' => 'Ascending (true) or Descending (false)'],
                            ['name' => 'status_sort', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['default', 'pdcs', 'dcps', 'cdps', 'spdc']], 'description' => 'Priority grouping sequence'],
                            ['name' => 'search', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'description' => 'Search by CA, Name, or Meter Number'],
                            ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 50, 'maximum' => 250]],
                            ['name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 1]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Filtered bills with calculated 4-box readings and counts.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'period' => '04/2026',
                                            'counts' => ['all' => 244, 'pending' => 41, 'submitted' => 189, 'doubt' => 10, 'critical' => 4],
                                            'data' => [
                                                [
                                                    'id' => 1042,
                                                    'ca_number' => '10230063090',
                                                    'consumer_name' => 'SURESH DAS',
                                                    'total_amount' => 32794.00,
                                                    'units_consumed' => 300,
                                                    'db_prev_reading' => '500',
                                                    'working_reading' => '800',
                                                    'review_status' => 'pending',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        ],
                    ],
                ],
                '/bills/review' => [
                    'patch' => [
                        'summary' => 'Submit Human Field Review & Reading Verdict',
                        'description' => 'Atomic update endpoint for field readers, automation scripts, or AI tools to register a human inspection decision with reasons and readings.',
                        'operationId' => 'submitReview',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['ca_number', 'billing_month', 'billing_year', 'status'],
                                        'properties' => [
                                            'ca_number' => ['type' => 'string', 'example' => '10230063090'],
                                            'billing_month' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 12, 'example' => 4],
                                            'billing_year' => ['type' => 'integer', 'example' => 2026],
                                            'status' => ['type' => 'string', 'enum' => ['submitted', 'doubt', 'critical', 'pending'], 'example' => 'submitted'],
                                            'reason_code' => ['type' => 'string', 'nullable' => true, 'example' => 'PREMISES_LOCKED'],
                                            'remark' => ['type' => 'string', 'nullable' => true, 'example' => 'House closed'],
                                            'working_reading' => ['type' => 'string', 'nullable' => true, 'example' => '850'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Bill updated successfully.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'message' => 'Bill for CA 10230063090 updated to submitted.',
                                            'data' => [
                                                'ca_number' => '10230063090',
                                                'review_status' => 'submitted',
                                                'working_reading' => '850',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Bill record not found for CA and period.'],
                            '422' => ['description' => 'Validation error.'],
                        ],
                    ],
                ],
                '/bills/batch-sync' => [
                    'post' => [
                        'summary' => 'Offline-to-Online Bulk Synchronization',
                        'description' => 'Uploads an entire route of offline field decisions in one single atomic transaction.',
                        'operationId' => 'batchSyncBills',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['billing_month', 'billing_year', 'reviews'],
                                        'properties' => [
                                            'billing_month' => ['type' => 'integer', 'example' => 4],
                                            'billing_year' => ['type' => 'integer', 'example' => 2026],
                                            'reviews' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'object',
                                                    'required' => ['ca_number', 'status'],
                                                    'properties' => [
                                                        'ca_number' => ['type' => 'string', 'example' => '10230063090'],
                                                        'status' => ['type' => 'string', 'enum' => ['submitted', 'doubt', 'critical', 'pending']],
                                                        'working_reading' => ['type' => 'string', 'nullable' => true],
                                                        'reason_code' => ['type' => 'string', 'nullable' => true],
                                                        'remark' => ['type' => 'string', 'nullable' => true],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Batch sync completed.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'total_received' => 2,
                                            'synced_count' => 2,
                                            'failed_count' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/automation/queue' => [
                    'get' => [
                        'summary' => 'Fetch Next Unprocessed Accounts Queue',
                        'description' => 'High-speed FIFO queue designed for autonomous bots (ADB/Termux) to grab the next batch of unsubmitted consumers with pre-calculated suggested readings.',
                        'operationId' => 'getAutomationQueue',
                        'parameters' => [
                            ['name' => 'mru_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                            ['name' => 'cycle', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'example' => 'SEP-2026']],
                            ['name' => 'limit', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 50]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Queue items with suggested readings.',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'total_in_queue' => 41,
                                            'consumers' => [
                                                [
                                                    'ca_number' => '10230040377',
                                                    'consumer_name' => 'BATTA TAFAS ALI',
                                                    'meter_number' => '3805340',
                                                    'previous_reading' => 3400,
                                                    'suggested_reading' => 3446,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'nbp_live_*',
                        'description' => 'Standard Bearer Token authentication via Laravel Sanctum / ApiKey.',
                    ],
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                        'description' => 'Direct API Key header (ideal for Python ADB automation scripts).',
                    ],
                ],
                'responses' => [
                    'UnauthorizedError' => [
                        'description' => 'Missing, revoked, or expired API Key / Token.',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    'success' => false,
                                    'error' => 'Unauthenticated',
                                    'message' => "A valid API key must be provided via the 'X-API-Key' header or 'Authorization: Bearer <key>' header.",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
