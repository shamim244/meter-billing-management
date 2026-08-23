<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFailedNotificationController extends Controller
{
    /**
     * Display failed critical notifications queue.
     */
    public function index(Request $request): View
    {
        $failedCriticalDeliveries = NotificationDelivery::with(['notification.user', 'emailProviderInstance'])
            ->whereHas('notification', fn($q) => $q->where('priority', 'critical'))
            ->whereIn('status', ['failed', 'permanently_failed'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('admin.notifications.failed-queue', compact('failedCriticalDeliveries'));
    }
}
