<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRecord extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'ca_number',
        'mru_id',
        'billing_month',
        'billing_year',
        'bill_month_label',
        'consumer_name',
        'total_amount',
        'current_reading',
        'working_reading',
        'review_status',
        'remark',
        'tag',
        'previous_reading',
        'units_consumed',
        'calculated_avg_units',
        'meter_no',
        'tariff_category',
        'billing_basis',
        'bill_date',
        'due_date',
        'pdf_path',
        'pdf_filename',
        'download_status',
        'parse_status',
        'processing_date',
        'error_message',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'units_consumed' => 'integer',
        'calculated_avg_units' => 'integer',
        'billing_month' => 'integer',
        'billing_year' => 'integer',
        'bill_date' => 'date',
        'due_date' => 'date',
        'processing_date' => 'datetime',
    ];

    /**
     * Get the MRU linked to this bill record.
     */
    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }

    /**
     * Get the consumer account associated with this bill.
     */
    public function consumerAccount(): BelongsTo
    {
        return $this->belongsTo(ConsumerAccount::class, 'ca_number', 'ca_number')
                    ->where('consumer_accounts.user_id', $this->user_id);
    }

    /**
     * Scope: filter by billing month and year.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('billing_month', $month)->where('billing_year', $year);
    }

    /**
     * Scope: filter by MRU.
     */
    public function scopeForMru($query, int $mruId)
    {
        return $query->where('mru_id', $mruId);
    }

    /**
     * Scope: successful downloads.
     */
    public function scopeDownloaded($query)
    {
        return $query->where('download_status', 'downloaded');
    }

    /**
     * Scope: failed downloads.
     */
    public function scopeFailedDownload($query)
    {
        return $query->where('download_status', 'failed');
    }

    /**
     * Scope: successfully parsed.
     */
    public function scopeParsed($query)
    {
        return $query->where('parse_status', 'parsed');
    }
}
