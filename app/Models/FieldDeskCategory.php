<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldDeskCategory extends Model
{
    use HasFactory;

    protected $table = 'field_desk_categories';

    protected $fillable = [
        'name',
        'code',
        'icon',
        'color',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all actions under this category.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(FieldDeskAction::class, 'category_id');
    }

    /**
     * Scope: only active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: order by sort order ascending then name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Default system categories definition.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultCategories(): array
    {
        return [
            [
                'code' => 'payment_promise',
                'name' => 'Payment Commitment',
                'icon' => '💳',
                'color' => '#10b981',
                'description' => 'Consumer payment commitment with target date and amount',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'issue_correction',
                'name' => 'Technical / Grievance',
                'icon' => '🔧',
                'color' => '#f59e0b',
                'description' => 'Meter defect or billing grievance resolution target',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'scheduled_visit',
                'name' => 'Scheduled Visit',
                'icon' => '🚶',
                'color' => '#6366f1',
                'description' => 'Scheduled field inspection or access visit',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'general_note',
                'name' => 'Field Dossier Note',
                'icon' => '📝',
                'color' => '#64748b',
                'description' => 'Field observation or consumer contact dossier note',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * Ensure default system categories exist in the database.
     */
    public static function ensureDefaultsExist(): void
    {
        if (static::query()->count() === 0) {
            foreach (static::defaultCategories() as $cat) {
                static::query()->updateOrCreate(['code' => $cat['code']], $cat);
            }
        }
    }

    /**
     * Get all active categories, self-healing with defaults if table is empty.
     *
     * @return Collection<int, static>
     */
    public static function getActiveCategories()
    {
        static::ensureDefaultsExist();

        return static::query()->where('is_active', true)->orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();
    }
}
