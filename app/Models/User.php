<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Notifications\NotificationDispatchService;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Interfaces\WalletFloat;
use Bavix\Wallet\Traits\HasWalletFloat;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'status', 'is_wallet_frozen', 'wallet_frozen_reason', 'wallet_frozen_at', 'wallet_frozen_by', 'shortcuts', 'storage_limit_mb', 'plan_tier'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Wallet, WalletFloat
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasWalletFloat, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_wallet_frozen' => 'boolean',
            'wallet_frozen_at' => 'datetime',
            'shortcuts' => 'array',
            'storage_limit_mb' => 'integer',
        ];
    }

    /**
     * Calculate physical PDF storage used by this user in bytes.
     */
    public function getStorageUsedBytes(): int
    {
        $dir = "users/{$this->id}/pdfs";
        $bytes = 0;
        if (Storage::disk('local')->exists($dir)) {
            $files = Storage::disk('local')->allFiles($dir);
            foreach ($files as $f) {
                if (str_ends_with(strtolower($f), '.pdf')) {
                    $bytes += Storage::disk('local')->size($f);
                }
            }
        }

        return $bytes;
    }

    /**
     * Get user's allocated storage limit in bytes (0 = unlimited).
     */
    public function getStorageLimitBytes(): int
    {
        $mb = (int) ($this->storage_limit_mb ?? 100);

        return $mb * 1024 * 1024;
    }

    /**
     * Calculate percentage of storage used.
     */
    public function getStorageUsagePercent(): float
    {
        $limit = $this->getStorageLimitBytes();
        if ($limit <= 0) {
            return 0.0;
        }
        $used = $this->getStorageUsedBytes();

        return min(100.0, round(($used / $limit) * 100, 1));
    }

    /**
     * Check if user has exceeded their allocated storage limit.
     */
    public function isStorageLimitExceeded(): bool
    {
        $limit = $this->getStorageLimitBytes();
        if ($limit <= 0) {
            return false;
        } // unlimited

        return $this->getStorageUsedBytes() >= $limit;
    }

    /**
     * Get total number of stored PDF files.
     */
    public function getPdfCount(): int
    {
        $dir = "users/{$this->id}/pdfs";
        if (! Storage::disk('local')->exists($dir)) {
            return 0;
        }
        $files = Storage::disk('local')->allFiles($dir);
        $count = 0;
        foreach ($files as $f) {
            if (str_ends_with(strtolower($f), '.pdf')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the user's active shortcut key mappings merged with system defaults.
     */
    public function getShortcutMap(): array
    {
        $baseDefaults = config('shortcuts.default', [
            'copy_ca' => 'c',
            'focus_reading' => 'r',
            'auto_fill_reading' => 'a',
            'submit_ok' => 'Enter',
            'mark_doubt' => '2',
            'mark_critical' => '3',
            'next_card' => 'ArrowRight',
            'prev_card' => 'ArrowLeft',
            'open_remark' => 'm',
            'exit_box' => 'Escape',
        ]);

        $systemDefaults = SystemSetting::get('shortcuts_default', $baseDefaults);

        $merged = array_merge($systemDefaults, $this->shortcuts ?? []);

        // Gracefully migrate legacy card navigation defaults (ArrowDown/ArrowUp) so Up/Down scrolls
        if (($merged['next_card'] ?? null) === 'ArrowDown') {
            $merged['next_card'] = 'ArrowRight';
        }
        if (($merged['prev_card'] ?? null) === 'ArrowUp') {
            $merged['prev_card'] = 'ArrowLeft';
        }

        return $merged;
    }

    /**
     * Get descriptive human-readable labels for each shortcut action.
     */
    public function getShortcutLabels(): array
    {
        return config('shortcuts.labels', [
            'copy_ca' => 'Copy CA Number to Clipboard',
            'focus_reading' => 'Edit / Focus Working Reading Input',
            'auto_fill_reading' => 'Auto-Fill Reading (Prev + Avg)',
            'submit_ok' => 'Save & Mark as Submit / OK',
            'mark_doubt' => 'Mark as Doubt / Re-check',
            'mark_critical' => 'Mark as Critical / Issue',
            'next_card' => 'Navigate to Next Consumer Card',
            'prev_card' => 'Navigate to Previous Consumer Card',
            'open_remark' => 'Open / Focus Remark Note Field',
            'exit_box' => 'Exit / Unfocus Input Box (Back to Navigation)',
        ]);
    }

    /**
     * Get all MRU workspaces belonging to this user.
     */
    public function mrus(): HasMany
    {
        return $this->hasMany(Mru::class);
    }

    /**
     * Get all consumer accounts belonging to this user.
     */
    public function consumerAccounts(): HasMany
    {
        return $this->hasMany(ConsumerAccount::class);
    }

    /**
     * Get all bill records belonging to this user.
     */
    public function billRecords(): HasMany
    {
        return $this->hasMany(BillRecord::class);
    }

    /**
     * Get all bill statuses belonging to this user.
     */
    public function billStatuses(): HasMany
    {
        return $this->hasMany(BillStatus::class);
    }

    /**
     * Get all payments belonging to this billing agent/user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    /**
     * Get all payment mandates belonging to this billing agent/user.
     */
    public function paymentMandates(): HasMany
    {
        return $this->hasMany(PaymentMandate::class, 'user_id');
    }

    /**
     * Admin who froze the wallet if applicable.
     */
    public function walletFrozenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wallet_frozen_by');
    }

    /**
     * Check if this user's wallet is currently frozen.
     */
    public function isWalletFrozen(): bool
    {
        return (bool) $this->is_wallet_frozen;
    }

    /**
     * All subscriptions for this user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgentSubscription::class);
    }

    /**
     * Current active subscription for this user (includes renewal_due and grace_period).
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(AgentSubscription::class)
            ->where('status', 'active')
            ->whereIn('lifecycle_status', ['active', 'renewal_due', 'grace_period'])
            ->latest('id');
    }

    /**
     * Billing cycles generated by this user.
     */
    public function billingCycles(): HasMany
    {
        return $this->hasMany(BillingCycle::class);
    }

    /**
     * Plan overage charges recorded for this user.
     */
    public function planOverageCharges(): HasMany
    {
        return $this->hasMany(PlanOverageCharge::class);
    }

    /**
     * Renewal attempts history for this user.
     */
    public function renewalAttempts(): HasMany
    {
        return $this->hasMany(RenewalAttempt::class);
    }

    /**
     * Issue reports submitted by this user.
     */
    public function issueReports(): HasMany
    {
        return $this->hasMany(IssueReport::class);
    }

    /**
     * Meter reading history records for this user's consumers.
     */
    public function meterReadingHistories(): HasMany
    {
        return $this->hasMany(MeterReadingHistory::class);
    }

    /**
     * Plan upgrade/downgrade logs for this user.
     */
    public function planUpgradeLogs(): HasMany
    {
        return $this->hasMany(PlanUpgradeLog::class);
    }

    /**
     * API keys issued to this user.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Send password reset notification via platform NotificationDispatchService and standard notifier.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));

        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        app(NotificationDispatchService::class)->dispatch(
            'auth.password_reset',
            $this,
            [
                'reset_url' => $url,
                'token' => $token,
            ],
            'critical'
        );
    }

    /**
     * Get user's active plan display name (e.g. Starter, Business Pro, Free Starter).
     */
    public function getCurrentPlanName(): string
    {
        $sub = $this->relationLoaded('activeSubscription')
            ? $this->activeSubscription
            : $this->activeSubscription()->with('plan')->first();

        if ($sub && $sub->plan) {
            return $sub->plan->name;
        }

        return ! empty($this->plan_tier) ? ucfirst($this->plan_tier) : 'Free Starter';
    }

    public function getCurrentPlanNameAttribute(): string
    {
        return $this->getCurrentPlanName();
    }
}
