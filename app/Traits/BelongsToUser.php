<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToUser
 *
 * Automatically scopes all queries to the authenticated user/agent.
 * This is the core multi-user isolation mechanism — every model that
 * stores user-specific data should use this trait.
 *
 * Usage: add `use BelongsToUser;` to any model with a `user_id` column.
 */
trait BelongsToUser
{
    /**
     * Boot the trait — adds a global scope that filters by user_id.
     */
    protected static function bootBelongsToUser(): void
    {
        // Only apply the scope when a user is authenticated
        // and the request is NOT from an admin context (e.g. Filament panel)
        static::addGlobalScope('belongs_to_user', function (Builder $builder) {
            if (Auth::check() && !static::isAdminContext()) {
                $builder->where(
                    static::resolveUserColumn(),
                    Auth::id()
                );
            }
        });

        // Auto-set user_id on creation if not explicitly provided
        static::creating(function ($model) {
            $column = static::resolveUserColumn();
            if (Auth::check() && empty($model->{$column})) {
                $model->{$column} = Auth::id();
            }
        });
    }

    /**
     * Resolve the user_id column name.
     * Override in the model if the column is named differently.
     */
    protected static function resolveUserColumn(): string
    {
        return 'user_id';
    }

    /**
     * Check if the current request is in an admin context.
     * Admin users (via Filament or API) should see all data.
     */
    protected static function isAdminContext(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            // In CLI / test environment without active web routing, admin bypasses scope
            if (!request() || !request()->route()) {
                return true;
            }
            // On web requests, only bypass scope on admin routes
            return request()->is('admin*');
        }

        return false;
    }

    /**
     * Scope to query without the user filter (for admin operations).
     */
    public function scopeWithoutUserScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('belongs_to_user');
    }

    /**
     * Backward-compatible alias for withoutUserScope.
     */
    public function scopeWithoutTenantScope(Builder $builder): Builder
    {
        return $this->scopeWithoutUserScope($builder);
    }

    /**
     * Scope to query for a specific user.
     */
    public function scopeForUser(Builder $builder, int $userId): Builder
    {
        return $builder->withoutGlobalScope('belongs_to_user')
                       ->where(static::resolveUserColumn(), $userId);
    }

    /**
     * Relationship: the owning user.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, static::resolveUserColumn());
    }
}
