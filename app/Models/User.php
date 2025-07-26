<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
                'google_id',        // ADD: Google OAuth ID
        'avatar',           // ADD: Profile picture from Google
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // PostgreSQL specific scopes
    public function scopeGoogleUsers($query)
    {
        return $query->whereNotNull('google_id');
    }

    public function scopeRegularUsers($query)
    {
        return $query->whereNull('google_id');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // E-commerce Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

        // Accessors
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return $this->avatar;
        }
        
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }

    public function getIsGoogleUserAttribute()
    {
        return !is_null($this->google_id);
    }
    


    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function couponUsage()
    {
        return $this->hasMany(CouponUsage::class);
    }

    // Helper methods
public function getCartCount()
{
    return $this->cartItems()
        ->whereHas('product', function ($query) {
            $query->where('is_active', true)
                  ->where('stock_quantity', '>', 0);
        })
        ->sum('quantity');
}

public function getCartTotal()
{
    return $this->cartItems()
        ->whereHas('product', function ($query) {
            $query->where('is_active', true)
                  ->where('stock_quantity', '>', 0);
        })
        ->get()
        ->sum(function ($item) {
            $price = $item->product->sale_price ?? $item->product->price;
            return $price * $item->quantity;
        });
}

    public function getTotalSpent()
    {
        return $this->orders()->where('payment_status', 'paid')->sum('total_amount');
    }

    public function getOrdersCount()
    {
        return $this->orders()->count();
    }
}