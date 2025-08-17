<?php

// File: app/Models/UserAddress.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone_recipient',
        'province_name',
        'city_name',
        'subdistrict_name',
        'postal_code',
        'destination_id',
        'street_address',
        'notes',
        'is_primary',
        'is_active'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the address
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full address as string
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->street_address}, {$this->subdistrict_name}, {$this->city_name}, {$this->province_name} {$this->postal_code}";
    }

    /**
     * Get location string (Province, City, Subdistrict, Postal Code)
     */
    public function getLocationStringAttribute(): string
    {
        return "{$this->province_name}, {$this->city_name}, {$this->subdistrict_name}, {$this->postal_code}";
    }

    /**
     * Get formatted recipient info (name + phone)
     */
    public function getRecipientInfoAttribute(): string
    {
        return "{$this->recipient_name} ({$this->phone_recipient})";
    }

    /**
     * Scope for active addresses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary address
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Set this address as primary (and unset others)
     * Uses PostgreSQL transaction for atomicity
     */
    public function setPrimary(): void
    {
        DB::transaction(function () {
            // First, unset all other primary addresses for this user
            self::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->where('is_active', true)
                ->update(['is_primary' => false]);
            
            // Then set this address as primary
            $this->update(['is_primary' => true, 'is_active' => true]);
        });
    }

    /**
     * Soft delete address (set is_active = false)
     */
    public function softDelete(): bool
    {
        // Don't allow soft deleting the primary address if there are other addresses
        if ($this->is_primary && $this->user->addresses()->where('id', '!=', $this->id)->count() > 0) {
            return false;
        }

        return $this->update(['is_active' => false]);
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set as primary if it's the first address for user
        static::created(function ($address) {
            $userAddressCount = self::where('user_id', $address->user_id)
                                   ->where('is_active', true)
                                   ->count();
            
            if ($userAddressCount === 1) {
                $address->update(['is_primary' => true]);
            }
        });

        // If primary address is being soft deleted, set another as primary
        static::updated(function ($address) {
            if ($address->wasChanged('is_active') && !$address->is_active && $address->is_primary) {
                $nextAddress = self::where('user_id', $address->user_id)
                                  ->where('id', '!=', $address->id)
                                  ->where('is_active', true)
                                  ->oldest()
                                  ->first();
                
                if ($nextAddress) {
                    $nextAddress->update(['is_primary' => true]);
                }
            }
        });
    }
}