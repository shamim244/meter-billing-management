<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminIssueController extends Controller
{
    /**
     * Display listing of issue reports with filtering & KPI metrics.
     */
    public function index(Request $request): View
    {
        $query = IssueReport::with(['user', 'mru', 'verifier', 'resolver'])
            ->latest('id');

        // Status filter
        $status = $request->get('status', 'all');
        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        // Severity filter
        $severity = $request->get('severity', 'all');
        if (! empty($severity) && $severity !== 'all') {
            $query->where('severity', $severity);
        }

        // Category filter
        $category = $request->get('category', 'all');
        if (! empty($category) && $category !== 'all') {
            $query->where('category', $category);
        }

        // Search (code, title, CA, user email)
        $search = trim((string) $request->get('search', ''));
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('issue_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ca_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $issues = $query->paginate(20)->withQueryString();

        // High-level KPI counts
        $counts = [
            'all' => IssueReport::count(),
            'pending' => IssueReport::where('status', 'pending')->count(),
            'verified' => IssueReport::where('status', 'verified')->count(),
            'spam' => IssueReport::where('status', 'spam')->count(),
            'resolved' => IssueReport::where('status', 'resolved')->count(),
        ];

        return view('admin.issues.index', compact('issues', 'counts', 'status', 'severity', 'category', 'search'));
    }

    /**
     * Show detailed view of an issue report.
     */
    public function show(IssueReport $issue): View
    {
        $issue->load(['user', 'mru', 'verifier', 'resolver']);
        $aiPrompt = $issue->toAiPrompt();

        return view('admin.issues.show', compact('issue', 'aiPrompt'));
    }

    /**
     * Verify an issue report as a genuine bug for AI resolution.
     */
    public function verify(Request $request, IssueReport $issue): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'severity' => 'nullable|string|in:low,medium,high,critical',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $issue->update([
            'status' => 'verified',
            'severity' => $validated['severity'] ?? $issue->severity,
            'admin_notes' => $validated['admin_notes'] ?? $issue->admin_notes,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Issue {$issue->issue_code} verified and queued for AI agent.",
                'ai_prompt' => $issue->toAiPrompt(),
            ]);
        }

        return redirect()->back()->with('status', "Issue {$issue->issue_code} verified as a real bug.");
    }

    /**
     * Mark an issue report as spam or false alarm.
     */
    public function markSpam(Request $request, IssueReport $issue): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $issue->update([
            'status' => 'spam',
            'admin_notes' => $validated['admin_notes'] ?? 'Marked as spam / false alarm by admin.',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Issue {$issue->issue_code} marked as spam.",
            ]);
        }

        return redirect()->back()->with('status', "Issue {$issue->issue_code} marked as spam.");
    }

    /**
     * Mark an issue report as resolved with fix notes.
     */
    public function resolve(Request $request, IssueReport $issue): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ai_resolution_notes' => 'required|string|max:5000',
        ]);

        $issue->update([
            'status' => 'resolved',
            'ai_resolution_notes' => trim($validated['ai_resolution_notes']),
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Issue {$issue->issue_code} marked as resolved.",
            ]);
        }

        return redirect()->back()->with('status', "Issue {$issue->issue_code} marked as resolved.");
    }

    /**
     * Get the formatted AI prompt for an issue report.
     */
    public function aiPrompt(IssueReport $issue): JsonResponse
    {
        return response()->json([
            'success' => true,
            'issue_code' => $issue->issue_code,
            'prompt' => $issue->toAiPrompt(),
        ]);
    }
}
