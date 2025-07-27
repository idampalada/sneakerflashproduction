<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, [
            'admin@sneakerflash.com',
            'admin@sneaker.com',
        ]);
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