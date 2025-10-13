<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'image_paths',      // Keep for backward compatibility
        'desktop_images',   // New field
        'mobile_images',    // New field
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'image_paths' => 'array',
        'desktop_images' => 'array',  // New cast
        'mobile_images' => 'array',   // New cast
        'is_active' => 'boolean'
    ];

    // Scope untuk banner aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk sorting
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}