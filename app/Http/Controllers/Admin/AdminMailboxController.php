<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Notifications\HostingerMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMailboxController extends Controller
{
    protected HostingerMailService $mailService;

    public function __construct(HostingerMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Display live Hostinger mailboxes & recent incoming messages.
     */
    public function index(Request $request): View
    {
        $selectedAddress = $request->get('address', 'agent@nexgenhub.site');
        $mailboxes = $this->mailService->getMailboxes();
        $inboxData = $this->mailService->listInboxMessages($selectedAddress, 1, 20);

        $messages = $inboxData['data'] ?? [];
        $pagination = $inboxData['pagination'] ?? null;

        return view('admin.notifications.mailbox', compact('mailboxes', 'selectedAddress', 'messages', 'pagination'));
    }

    /**
     * Get single message rendered content (AJAX modal).
     */
    public function showMessage(Request $request, int $uid): JsonResponse
    {
        $address = $request->get('address', 'agent@nexgenhub.site');
        $content = $this->mailService->getMessageText($address, $uid);

        if (!$content) {
            return response()->json(['error' => 'Message not found or could not be loaded.'], 404);
        }

        return response()->json($content);
    }

    /**
     * Send direct outgoing email via Hostinger API.
     */
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'from_address' => ['nullable', 'email'],
        ]);

        $fromAddress = $validated['from_address'] ?? 'agent@nexgenhub.site';

        try {
            $this->mailService->send(
                $validated['to'],
                $validated['subject'],
                $validated['body'],
                $fromAddress
            );

            return back()->with('success', "Email sent successfully to {$validated['to']} via Hostinger Mail API.");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to send email: " . $e->getMessage());
        }
    }
}
