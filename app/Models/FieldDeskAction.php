<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldDeskAction extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'field_desk_actions';

    protected $fillable = [
        'user_id',
        'ca_number',
        'consumer_account_id',
        'category_id',
        'priority',
        'target_date',
        'original_target_date',
        'target_amount',
        'collected_amount',
        'payment_mode',
        'private_note',
        'status',
        'reschedule_count',
        'billing_month',
        'billing_year',
        'mru_id',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'target_date' => 'date',
        'original_target_date' => 'date',
        'target_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'reschedule_count' => 'integer',
        'billing_month' => 'integer',
        'billing_year' => 'integer',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the owner user of this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of this action.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FieldDeskCategory::class, 'category_id');
    }

    /**
     * Get the consumer account associated with this action.
     */
    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'consumer_account_id');
    }

    /**
     * Get the MRU associated with this action.
     */
    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class, 'mru_id');
    }

    /**
     * Get all logged timeline activities for this action.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(FieldDeskActivity::class, 'action_id')->orderBy('created_at', 'desc');
    }

    /**
     * Scope: open or rescheduled actions.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'rescheduled']);
    }

    /**
     * Scope: actions due today.
     */
    public function scopeDueToday(Builder $query): Builder
    {
        return $query->open()->whereDate('target_date', Carbon::today());
    }

    /**
     * Scope: overdue actions.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereDate('target_date', '<', Carbon::today());
    }

    /**
     * Scope: upcoming actions.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->open()->whereDate('target_date', '>', Carbon::today());
    }

    /**
     * Scope: resolved actions.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: search by CA, note, or consumer name.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $escaped = addcslashes($search, '%_\\');

        return $query->where(function (Builder $q) use ($escaped) {
            $q->where('ca_number', 'like', "%{$escaped}%")
                ->orWhere('private_note', 'like', "%{$escaped}%")
                ->orWhere('resolution_note', 'like', "%{$escaped}%")
                ->orWhereHas('consumerAccount', function (Builder $caQ) use ($escaped) {
                    $caQ->where('consumer_name', 'like', "%{$escaped}%")
                        ->orWhere('mobile', 'like', "%{$escaped}%")
                        ->orWhere('meter_no', 'like', "%{$escaped}%");
                });
        });
    }

    /**
     * Check if the action is currently overdue.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['open', 'rescheduled'])
            && $this->target_date
            && $this->target_date->isPast()
            && ! $this->target_date->isToday();
    }

    /**
     * Check if the action is due today.
     */
    public function isDueToday(): bool
    {
        return in_array($this->status, ['open', 'rescheduled'])
            && $this->target_date
            && $this->target_date->isToday();
    }

    /**
     * Check if the action is upcoming in the future.
     */
    public function isUpcoming(): bool
    {
        return in_array($this->status, ['open', 'rescheduled'])
            && $this->target_date
            && $this->target_date->isFuture()
            && ! $this->target_date->isToday();
    }

    /**
     * Check if the action is resolved / completed.
     */
    public function isResolved(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Calculate remaining unpaid amount if target amount is defined.
     */
    public function getRemainingAmountAttribute(): ?float
    {
        if ($this->target_amount === null) {
            return null;
        }

        return max(0.00, (float) $this->target_amount - (float) $this->collected_amount);
    }
}
