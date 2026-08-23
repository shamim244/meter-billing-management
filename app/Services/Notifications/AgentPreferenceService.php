<?php

namespace App\Services\Notifications;

use App\Models\AgentNotificationPreference;

class AgentPreferenceService
{
    public const CATEGORIES = [
        'billing' => 'Billing & Subscription Alerts',
        'wallet' => 'Wallet & Balance Alerts',
        'usage_reports' => 'Usage Reports & Monthly Summaries',
    ];

    /**
     * Get preference map for user.
     *
     * @return array<string, array<string, bool>>
     */
    public function getPreferences(int $userId): array
    {
        $prefs = AgentNotificationPreference::where('user_id', $userId)->get();

        $result = [];
        foreach (array_keys(self::CATEGORIES) as $cat) {
            $result[$cat] = [
                'email' => true, // default enabled
                'push' => true,  // default enabled
            ];
        }

        foreach ($prefs as $p) {
            if (isset($result[$p->event_category])) {
                $result[$p->event_category][$p->channel] = (bool) $p->enabled;
            }
        }

        return $result;
    }

    /**
     * Update preference for an event category and channel.
     */
    public function updatePreference(int $userId, string $category, string $channel, bool $enabled): AgentNotificationPreference
    {
        return AgentNotificationPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'event_category' => $category,
                'channel' => $channel,
            ],
            [
                'enabled' => $enabled,
            ]
        );
    }

    /**
     * Check if a channel should be used for a notification.
     * Enforces: In-App delivery for CRITICAL events ALWAYS returns true regardless of agent preference.
     */
    public function isChannelEnabled(int $userId, string $category, string $channel, string $priority): bool
    {
        // In-App channel is ALWAYS enabled for CRITICAL events
        if ($channel === 'in_app') {
            if (strtolower($priority) === 'critical') {
                return true;
            }
            return true; // Routine in-app is also enabled by default
        }

        $pref = AgentNotificationPreference::where('user_id', $userId)
            ->where('event_category', $category)
            ->where('channel', $channel)
            ->first();

        if ($pref !== null) {
            return (bool) $pref->enabled;
        }

        // Default to enabled if no preference row exists
        return true;
    }
}
