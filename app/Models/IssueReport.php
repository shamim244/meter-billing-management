<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IssueReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'issue_code',
        'user_id',
        'title',
        'description',
        'category',
        'severity',
        'status',
        'page_url',
        'route_name',
        'ca_number',
        'mru_id',
        'billing_month',
        'billing_year',
        'client_context',
        'server_context',
        'admin_notes',
        'verified_at',
        'verified_by',
        'ai_resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'client_context' => 'array',
        'server_context' => 'array',
        'verified_at' => 'datetime',
        'resolved_at' => 'datetime',
        'billing_month' => 'integer',
        'billing_year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (IssueReport $report) {
            if (empty($report->issue_code)) {
                $report->issue_code = static::generateIssueCode();
            }
        });
    }

    /**
     * Generate a unique human-friendly issue code e.g. BUG-20260918-7F3A
     */
    public static function generateIssueCode(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        $code = "BUG-{$date}-{$random}";

        while (static::where('issue_code', $code)->exists()) {
            $random = strtoupper(Str::random(4));
            $code = "BUG-{$date}-{$random}";
        }

        return $code;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mru(): BelongsTo
    {
        return $this->belongsTo(Mru::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Get human-friendly label for current status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'verified' => 'Verified (Queued)',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'spam' => 'Invalid / Discarded',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get Tailwind color family for current status.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'verified' => 'blue',
            'in_progress' => 'indigo',
            'resolved' => 'emerald',
            'spam' => 'rose',
            'closed' => 'slate',
            default => 'slate',
        };
    }

    /**
     * Get human-friendly label for category.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'calculation' => '⚡ Calculation / Units',
            'bill_download' => '📑 Bill Download / PDF',
            'mru_sync' => '🗂️ MRU / Cycles',
            'ui_display' => '🖥️ UI / Display Error',
            'wallet_payment' => '👛 Wallet / Billing',
            'other' => '❓ Other',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }

    /**
     * Generate a structured diagnostic markdown prompt ready for the AI Agent (Antigravity).
     */
    public function toAiPrompt(): string
    {
        $userName = $this->user ? "{$this->user->name} (ID: {$this->user->id}, Email: {$this->user->email})" : 'Guest / System';
        $mruName = $this->mru ? "{$this->mru->code} - {$this->mru->name} (ID: {$this->mru->id})" : ($this->mru_id ? "MRU ID {$this->mru_id}" : 'N/A');
        $period = ($this->billing_month && $this->billing_year) ? "{$this->billing_month}/{$this->billing_year}" : 'N/A';
        $ca = $this->ca_number ?: 'N/A';
        $page = $this->page_url ?: ($this->route_name ?: 'Unknown');

        $clientInfo = ! empty($this->client_context) ? json_encode($this->client_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'None';
        $serverInfo = ! empty($this->server_context) ? json_encode($this->server_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'None';

        return <<<EOT
### 🐞 Verified Bug Report: [{$this->issue_code}] - {$this->title}
- **Status:** {$this->status}
- **Severity:** {$this->severity}
- **Category:** {$this->category}
- **Reported By:** {$userName}
- **Created At:** {$this->created_at}

#### 📍 Operational Context
- **Page URL:** `{$page}`
- **Active MRU:** {$mruName}
- **Billing Period:** {$period}
- **CA Number:** `{$ca}`

#### 📝 Issue Description
> {$this->description}

#### 🛡️ Admin Triage Notes
{$this->admin_notes}

#### 💻 Client Diagnostic Context
```json
{$clientInfo}
```

#### ⚙️ Server Diagnostic Context
```json
{$serverInfo}
```

#### 🎯 Action Instructions for AI Agent
1. Inspect the codebase for the reported problem in category `{$this->category}`.
2. Check recent data or models relevant to MRU `{$mruName}` and CA `{$ca}`.
3. Write/run tests in SQLite `:memory:` ensuring no regression.
4. Execute `php artisan issue:resolve {$this->issue_code} --notes="<Summary of code changes>"` once fixed and verified.
EOT;
    }
}
