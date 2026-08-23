<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\Notifications\AgentPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected AgentPreferenceService $preferenceService;

    public function __construct(AgentPreferenceService $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * Agent Full Notifications History Page.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $filter = $request->get('filter', 'all');

        $query = Notification::where('user_id', $userId)
            ->with('deliveries')
            ->orderBy('id', 'desc');

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'critical') {
            $query->critical();
        } elseif ($filter === 'routine') {
            $query->routine();
        }

        $notifications = $query->paginate(20);
        $unreadCount = Notification::where('user_id', $userId)->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    /**
     * JSON endpoint for top-nav bell icon dropdown.
     */
    public function recent(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $unreadCount = Notification::where('user_id', $userId)->unread()->count();

        $recent = Notification::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'priority' => $n->priority,
                    'is_read' => $n->read_at !== null,
                    'created_at_human' => $n->created_at ? $n->created_at->diffForHumans() : '',
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $recent,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse|RedirectResponse
    {
        if ($notification->user_id === Auth::id()) {
            $notification->markAsRead();
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllRead(): JsonResponse|RedirectResponse
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Notification Preferences Page.
     */
    public function preferences(): View
    {
        $userId = Auth::id();
        $preferences = $this->preferenceService->getPreferences($userId);
        $categories = AgentPreferenceService::CATEGORIES;

        return view('notifications.preferences', compact('preferences', 'categories'));
    }

    /**
     * Save Notification Preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $categories = array_keys(AgentPreferenceService::CATEGORIES);
        $inputs = $request->input('preferences', []);

        foreach ($categories as $cat) {
            $emailEnabled = !empty($inputs[$cat]['email']);
            $pushEnabled = !empty($inputs[$cat]['push']);

            $this->preferenceService->updatePreference($userId, $cat, 'email', $emailEnabled);
            $this->preferenceService->updatePreference($userId, $cat, 'push', $pushEnabled);
        }

        return redirect()->route('notifications.preferences')
            ->with('success', 'Your notification preferences have been saved.');
    }
}
