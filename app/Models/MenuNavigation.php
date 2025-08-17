<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuNavigation extends Model
{
    use HasFactory;

    protected $table = 'menu_navigation';

    protected $fillable = [
        'menu_key',
        'menu_label',
        'menu_icon',
        'menu_description',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get menu items for navigation
     */
    public static function getNavigationItems()
    {
        return static::active()->ordered()->get();
    }

    /**
     * Get specific menu by key
     */
    public static function getByKey(string $key)
    {
        return static::where('menu_key', $key)->first();
    }

    /**
     * Check if menu is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get menu URL
     */
    public function getUrlAttribute(): string
    {
        return route("products.{$this->menu_key}");
    }
}