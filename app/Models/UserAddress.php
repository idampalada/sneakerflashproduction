<?php
// app/Models/UserAddress.php

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
        'phone_recipient',
        'street_address',
        'notes',
        'is_primary',
        'is_active',
        'search_location',
        
        // Hierarchical fields sesuai struktur database yang sudah dibersihkan
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'sub_district_id',
        'sub_district_name',
        'postal_code',
        'destination_id'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer',
        'sub_district_id' => 'integer'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_primary' => false,
    ];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Method to set as primary address
    public function setPrimary()
    {
        // Remove primary from other addresses
        static::where('user_id', $this->user_id)
              ->where('id', '!=', $this->id)
              ->update(['is_primary' => false]);
        
        // Set this as primary
        $this->update(['is_primary' => true]);
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
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    // Accessor for location string (for display)
    public function getLocationStringAttribute()
    {
        $parts = array_filter([
            $this->sub_district_name,
            $this->district_name,
            $this->city_name,
            $this->province_name,
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    // Accessor for recipient info
    public function getRecipientInfoAttribute()
    {
        return "{$this->recipient_name} - {$this->phone_recipient}";
    }

    // Accessor for shipping destination ID (use the most specific available)
    public function getShippingDestinationIdAttribute()
    {
        return $this->destination_id ?? $this->sub_district_id ?? $this->district_id ?? $this->city_id;
    }

    // Scope for active addresses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for primary address
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Scope for user addresses
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}