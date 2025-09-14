<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'street_address',
        'search_location', // Keep for backward compatibility
        'is_primary',
        
        // New hierarchical fields
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'sub_district_id',
        'sub_district_name',
        'postal_code_api'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer',
        'sub_district_id' => 'integer'
    ];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor for full address
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->street_address,
            $this->sub_district_name,
            $this->district_name,
            $this->city_name,
            $this->province_name,
            $this->postal_code_api
        ]);

        return implode(', ', $parts);
    }

    // Accessor for shipping destination ID (use the most specific available)
    public function getShippingDestinationIdAttribute()
    {
        return $this->sub_district_id ?? $this->district_id ?? $this->city_id ?? null;
    }

    // Accessor for destination type
    public function getDestinationTypeAttribute()
    {
        if ($this->sub_district_id) return 'sub_district';
        if ($this->district_id) return 'district';
        if ($this->city_id) return 'city';
        return null;
    }
}