<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotificationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationTemplateController extends Controller
{
    protected NotificationTemplateService $templateService;

    public function __construct(NotificationTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display all notification templates.
     */
    public function index(): View
    {
        $templates = $this->templateService->getAllTemplates();

        return view('admin.notifications.templates', compact('templates'));
    }

    /**
     * Update a specific template.
     */
    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'priority' => ['required', 'in:critical,routine'],
            'dispatch_mode' => ['required', 'in:sync,queued'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->templateService->updateTemplate($template->id, $validated);

        return redirect()->route('admin.notifications.templates.index')
            ->with('success', "Template for [{$template->event_type}] ({$template->channel}) updated successfully.");
    }

    /**
     * Render template preview with sample data.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string'],
            'body_template' => ['required', 'string'],
        ]);

        $sampleData = [
            'agent_name' => 'John Doe (Operator)',
            'email' => 'agent@example.com',
            'amount' => '1,500.00',
            'balance' => '4,250.00',
            'threshold' => '500.00',
            'plan_name' => 'Standard Operator Plan',
            'old_plan' => 'Basic Plan',
            'new_plan' => 'Pro Plan',
            'prorated_charge' => '450.00',
            'prorated_credit' => '200.00',
            'mru_code' => 'MRU_PAT_01',
            'days_remaining' => '3',
            'grace_period_ends_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'reason' => 'Overage threshold reached',
            'admin_name' => 'System Administrator',
            'transaction_id' => 'TXN_99887766',
            'gateway' => 'Cashfree',
            'payment_method' => 'UPI_QR',
            'utr_number' => '423156789012',
            'required_amount' => '1,200.00',
            'wallet_balance' => '350.00',
            'month' => (string) now()->month,
            'year' => (string) now()->year,
            'bills_processed' => '450',
            'mrus_active' => '3',
            'data_coverage' => '98.5',
            'extra_count' => '50',
            'included_mrus' => '2',
            'included_consumers' => '500',
            'method' => 'Self-service',
        ];

        $renderedSubject = $validated['subject'] ? $this->templateService->renderMergeFields($validated['subject'], $sampleData) : null;
        $renderedBody = $this->templateService->renderMergeFields($validated['body_template'], $sampleData);

        return response()->json([
            'subject' => $renderedSubject,
            'body' => $renderedBody,
            'formatted_html' => nl2br(e($renderedBody)),
        ]);
    }

    /**
     * Reset all or a specific template to factory defaults.
     */
    public function resetToDefaults(Request $request): RedirectResponse
    {
        $eventType = $request->get('event_type');
        $this->templateService->resetToDefaults($eventType);

        return redirect()->route('admin.notifications.templates.index')
            ->with('success', 'Notification templates successfully reset to factory defaults.');
    }
}
