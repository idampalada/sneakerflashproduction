<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'image_paths', // Changed to multiple images
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'image_paths' => 'array', // Cast to array
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}