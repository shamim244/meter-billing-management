<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SystemBackup extends Model
{
    use HasFactory;

    protected $table = 'system_backups';

    protected $fillable = [
        'backup_code',
        'type',
        'filename',
        'disk',
        'size_bytes',
        'sha256_hash',
        'duration_seconds',
        'status',
        'error_message',
        'meta',
        'triggered_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'duration_seconds' => 'float',
        'meta' => 'array',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }

    public function existsOnDisk(): bool
    {
        try {
            return Storage::disk($this->disk)->exists($this->getStoragePath());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getStoragePath(): string
    {
        return 'backups/' . $this->filename;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'db_only' => 'Database SQL',
            'storage_only' => 'PDF Storage',
            'full' => 'Full Snapshot',
            'agent_export' => 'Agent Workspace',
            default => strtoupper($this->type),
        };
    }
}
