<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'last_used_at',
        'last_ip',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new secure API key for the given user.
     * Returns an array containing the Eloquent model and the plain-text key (shown only once).
     *
     * @return array{apiKey: self, plainTextToken: string}
     */
    public static function generate(User $user, string $name, ?array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): array
    {
        $plainKey = 'nbp_live_'.Str::random(40);
        $prefix = substr($plainKey, 0, 16);
        $hash = hash('sha256', $plainKey);

        $apiKey = self::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => $hash,
            'abilities' => $abilities ?? ['*'],
            'expires_at' => $expiresAt,
        ]);

        return [
            'apiKey' => $apiKey,
            'plainTextToken' => $plainKey,
        ];
    }

    /**
     * Find and validate a plain text API key.
     */
    public static function findAndValidate(string $plainKey): ?self
    {
        $plainKey = trim($plainKey);
        if (empty($plainKey)) {
            return null;
        }

        $hash = hash('sha256', $plainKey);
        $apiKey = self::with('user')->where('key_hash', $hash)->first();

        if (! $apiKey) {
            return null;
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return null;
        }

        if (! $apiKey->user || $apiKey->user->status === 'inactive' || $apiKey->user->status === 'suspended') {
            return null;
        }

        return $apiKey;
    }

    /**
     * Check if the API key has a specific ability.
     */
    public function can(string $ability): bool
    {
        if (empty($this->abilities)) {
            return true;
        }

        if (in_array('*', $this->abilities, true)) {
            return true;
        }

        return in_array($ability, $this->abilities, true);
    }

    /**
     * Record usage timestamp and remote IP address.
     */
    public function recordUsage(?string $ip = null): void
    {
        $this->updateQuietly([
            'last_used_at' => now(),
            'last_ip' => $ip,
        ]);
    }
}
