<?php

namespace App\Services\Referral;

use App\Models\SystemSetting;

class ReferralSettingsService
{
    /**
     * Get all platform-wide referral settings with fallback to config defaults.
     *
     * @return array{
     *     is_enabled: bool,
     *     reward_trigger: string,
     *     reward_kind: string,
     *     reward_value: float,
     *     minimum_qualifying_amount: float,
     *     hold_period_days: int,
     *     referee_discount_kind: ?string,
     *     referee_discount_value: ?float
     * }
     */
    public function getSettings(): array
    {
        return [
            'is_enabled' => (bool) SystemSetting::get('referral_is_enabled', config('referral.is_enabled', true)),
            'reward_trigger' => (string) SystemSetting::get('referral_reward_trigger', config('referral.reward_trigger', 'subscription')),
            'reward_kind' => (string) SystemSetting::get('referral_reward_kind', config('referral.reward_kind', 'percentage')),
            'reward_value' => (float) SystemSetting::get('referral_reward_value', config('referral.reward_value', 10.0)),
            'minimum_qualifying_amount' => (float) SystemSetting::get('referral_minimum_qualifying_amount', config('referral.minimum_qualifying_amount', 100.0)),
            'hold_period_days' => (int) SystemSetting::get('referral_hold_period_days', config('referral.hold_period_days', 7)),
            'referee_discount_kind' => SystemSetting::get('referral_referee_discount_kind', config('referral.referee_discount_kind')),
            'referee_discount_value' => SystemSetting::get('referral_referee_discount_value') !== null
                ? (float) SystemSetting::get('referral_referee_discount_value')
                : (config('referral.referee_discount_value') !== null ? (float) config('referral.referee_discount_value') : null),
        ];
    }

    /**
     * Update referral configuration settings.
     */
    public function updateSettings(array $data): void
    {
        if (array_key_exists('is_enabled', $data)) {
            SystemSetting::set('referral_is_enabled', (bool) $data['is_enabled']);
        }
        if (array_key_exists('reward_trigger', $data)) {
            $trigger = in_array($data['reward_trigger'], ['subscription', 'topup'], true) ? $data['reward_trigger'] : 'subscription';
            SystemSetting::set('referral_reward_trigger', $trigger);
        }
        if (array_key_exists('reward_kind', $data)) {
            $kind = in_array($data['reward_kind'], ['percentage', 'flat'], true) ? $data['reward_kind'] : 'percentage';
            SystemSetting::set('referral_reward_kind', $kind);
        }
        if (array_key_exists('reward_value', $data)) {
            SystemSetting::set('referral_reward_value', max(0.00, (float) $data['reward_value']));
        }
        if (array_key_exists('minimum_qualifying_amount', $data)) {
            SystemSetting::set('referral_minimum_qualifying_amount', max(0.00, (float) $data['minimum_qualifying_amount']));
        }
        if (array_key_exists('hold_period_days', $data)) {
            SystemSetting::set('referral_hold_period_days', max(0, (int) $data['hold_period_days']));
        }
        if (array_key_exists('referee_discount_kind', $data)) {
            $refKind = in_array($data['referee_discount_kind'], ['percentage', 'flat'], true) ? $data['referee_discount_kind'] : null;
            SystemSetting::set('referral_referee_discount_kind', $refKind);
        }
        if (array_key_exists('referee_discount_value', $data)) {
            $refVal = $data['referee_discount_value'] !== null && $data['referee_discount_value'] !== '' ? max(0.00, (float) $data['referee_discount_value']) : null;
            SystemSetting::set('referral_referee_discount_value', $refVal);
        }
    }
}
