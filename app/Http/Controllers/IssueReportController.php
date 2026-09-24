<?php

namespace App\Http\Controllers;

use App\Models\IssueReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IssueReportController extends Controller
{
    /**
     * Submit an issue report from the frontend widget.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $rateLimitKey = 'issue-report:'.($userId ?: $request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'message' => "Too many reports submitted. Please wait {$seconds} seconds before reporting again.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 600); // 10 attempts per 10 minutes

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'nullable|string|in:calculation,bill_download,mru_sync,ui_display,wallet_payment,other',
            'severity' => 'nullable|string|in:low,medium,high,critical',
            'page_url' => 'nullable|string|max:1000',
            'route_name' => 'nullable|string|max:255',
            'ca_number' => 'nullable|string|max:50',
            'mru_id' => 'nullable|integer',
            'billing_month' => 'nullable|integer|between:1,12',
            'billing_year' => 'nullable|integer|min:2020|max:2035',
            'client_context' => 'nullable|array',
            'server_context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a title and description for the issue.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $report = IssueReport::create([
            'user_id' => $userId,
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'category' => $validated['category'] ?? 'other',
            'severity' => $validated['severity'] ?? 'medium',
            'status' => 'pending',
            'page_url' => $validated['page_url'] ?? $request->header('referer'),
            'route_name' => $validated['route_name'] ?? null,
            'ca_number' => $validated['ca_number'] ?? null,
            'mru_id' => $validated['mru_id'] ?? null,
            'billing_month' => $validated['billing_month'] ?? null,
            'billing_year' => $validated['billing_year'] ?? null,
            'client_context' => $validated['client_context'] ?? [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ],
            'server_context' => $validated['server_context'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Issue reported successfully! Our team and AI agent are investigating. Ticket reference: {$report->issue_code}",
            'issue_code' => $report->issue_code,
            'id' => $report->id,
        ], 201);
    }

    /**
     * Check / Track the live status and resolution notes of any bug report by reference code.
     */
    public function track(Request $request, ?string $code = null): JsonResponse
    {
        $code = trim(strtoupper($code ?: $request->input('code', '')));

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a ticket reference number (e.g. BUG-20260918-XXXX).',
            ], 400);
        }

        $issue = IssueReport::with(['user', 'mru'])
            ->where('issue_code', $code)
            ->first();

        if (! $issue) {
            return response()->json([
                'success' => false,
                'message' => "No issue report found matching reference number '{$code}'. Please check the code and try again.",
            ], 404);
        }

        $currentUserId = Auth::id();
        $isOwner = $currentUserId && ($issue->user_id === $currentUserId);
        $isAdmin = Auth::user()?->hasRole('admin');

        return response()->json([
            'success' => true,
            'issue' => [
                'id' => $issue->id,
                'issue_code' => $issue->issue_code,
                'title' => $issue->title,
                'description' => ($isOwner || $isAdmin) ? $issue->description : Str::limit($issue->description, 120),
                'category' => $issue->category,
                'category_label' => $issue->getCategoryLabel(),
                'severity' => $issue->severity,
                'status' => $issue->status,
                'status_label' => $issue->getStatusLabel(),
                'status_color' => $issue->getStatusColor(),
                'admin_notes' => ($isOwner || $isAdmin) ? $issue->admin_notes : null,
                'ai_resolution_notes' => $issue->ai_resolution_notes,
                'has_resolution' => ! empty($issue->ai_resolution_notes),
                'created_at' => $issue->created_at ? $issue->created_at->format('M d, Y h:i A') : null,
                'created_at_human' => $issue->created_at ? $issue->created_at->diffForHumans() : null,
                'verified_at' => $issue->verified_at ? $issue->verified_at->format('M d, Y h:i A') : null,
                'resolved_at' => $issue->resolved_at ? $issue->resolved_at->format('M d, Y h:i A') : null,
                'resolved_at_human' => $issue->resolved_at ? $issue->resolved_at->diffForHumans() : null,
                'is_owner' => $isOwner,
                'reporter_name' => ($isOwner || $isAdmin) ? ($issue->user?->name ?? 'Anonymous') : Str::mask($issue->user?->name ?? 'Operator', '*', 2, 4),
                'mru_name' => $issue->mru ? "{$issue->mru->code} - {$issue->mru->name}" : null,
                'ca_number' => ($isOwner || $isAdmin) ? $issue->ca_number : ($issue->ca_number ? Str::mask($issue->ca_number, '*', 4, 4) : null),
            ],
        ]);
    }

    /**
     * Get recent issue reports submitted by the logged-in user.
     */
    public function myReports(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to view your reports.',
            ], 401);
        }

        $issues = IssueReport::where('user_id', $userId)
            ->latest('id')
            ->take(30)
            ->get()
            ->map(function (IssueReport $issue) {
                return [
                    'id' => $issue->id,
                    'issue_code' => $issue->issue_code,
                    'title' => $issue->title,
                    'category' => $issue->category,
                    'category_label' => $issue->getCategoryLabel(),
                    'severity' => $issue->severity,
                    'status' => $issue->status,
                    'status_label' => $issue->getStatusLabel(),
                    'status_color' => $issue->getStatusColor(),
                    'ai_resolution_notes' => $issue->ai_resolution_notes,
                    'created_at' => $issue->created_at ? $issue->created_at->format('M d, Y h:i A') : null,
                    'created_at_human' => $issue->created_at ? $issue->created_at->diffForHumans() : null,
                    'resolved_at' => $issue->resolved_at ? $issue->resolved_at->format('M d, Y h:i A') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'issues' => $issues,
            'count' => $issues->count(),
            'active_count' => $issues->whereIn('status', ['pending', 'verified', 'in_progress'])->count(),
            'resolved_count' => $issues->where('status', 'resolved')->count(),
        ]);
    }
}
