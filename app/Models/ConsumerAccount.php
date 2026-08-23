<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumerAccount extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'ca_number',
        'mru_id',
        'consumer_name',
        'father_name',
        'meter_no',
        'tariff_category',
        'mobile',
        'address',
        'status',
        'last_working_reading',
        'last_working_month',
        'last_working_year',
        'baseline_previous_reading',
    ];

    /**
     * Get the parent MRU this consumer belongs to.
     */
    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }

    /**
     * Get all bill records for this consumer account.
     */
    public function billRecords(): HasMany
    {
        return $this->hasMany(BillRecord::class, 'ca_number', 'ca_number')
                    ->where('bill_records.user_id', $this->user_id);
    }

    /**
     * Get all bill statuses for this consumer account.
     */
    public function billStatuses(): HasMany
    {
        return $this->hasMany(BillStatus::class, 'ca_number', 'ca_number')
                    ->where('bill_statuses.user_id', $this->user_id);
    }

    /**
     * Scope: active accounts only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: search by CA number, name, or meter number.
     */
    public function scopeSearch($query, string $search)
    {
        $escaped = addcslashes($search, '%_\\');
        return $query->where(function ($q) use ($escaped) {
            $q->where('ca_number', 'like', "%{$escaped}%")
              ->orWhere('consumer_name', 'like', "%{$escaped}%")
              ->orWhere('meter_no', 'like', "%{$escaped}%");
        });
    }
}
