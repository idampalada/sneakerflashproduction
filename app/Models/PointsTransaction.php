<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'amount',
        'description',
        'reference',
        'balance_before',
        'balance_after',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // =====================================
    // RELATIONSHIPS
    // =====================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeemed');
    }

    public function scopeExpired($query)
    {
        return $query->where('type', 'expired');
    }

    public function scopeAdjustment($query)
    {
        return $query->where('type', 'adjustment');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'earned' => '➕',
            'redeemed' => '➖',
            'expired' => '⏰',
            'adjustment' => '⚖️',
            default => '❓'
        };
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'earned' => 'text-green-600',
            'redeemed' => 'text-red-600',
            'expired' => 'text-yellow-600',
            'adjustment' => 'text-blue-600',
            default => 'text-gray-600'
        };
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'earned' => 'Points Earned',
            'redeemed' => 'Points Redeemed',
            'expired' => 'Points Expired',
            'adjustment' => 'Points Adjustment',
            default => 'Unknown'
        };
    }

    // =====================================
    // STATIC METHODS
    // =====================================

    /**
     * Create earned points transaction
     */
    public static function createEarned($userId, $amount, $orderId = null, $description = null)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $balanceBefore = $user->points_balance;
        $balanceAfter = $balanceBefore + $amount;

        return self::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'type' => 'earned',
            'amount' => $amount,
            'description' => $description ?: 'Points earned from purchase',
            'reference' => $orderId ? "ORDER_{$orderId}" : null,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }

    /**
     * Create redeemed points transaction
     */
    public static function createRedeemed($userId, $amount, $description = null, $reference = null)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($amount > $user->points_balance) {
            throw new \Exception('Insufficient points balance');
        }

        $balanceBefore = $user->points_balance;
        $balanceAfter = $balanceBefore - $amount;

        return self::create([
            'user_id' => $userId,
            'type' => 'redeemed',
            'amount' => $amount,
            'description' => $description ?: 'Points redeemed',
            'reference' => $reference,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }

    /**
     * Create adjustment transaction (admin use)
     */
    public static function createAdjustment($userId, $amount, $description, $reference = null)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $balanceBefore = $user->points_balance;
        $balanceAfter = $balanceBefore + $amount; // Can be negative for deductions

        return self::create([
            'user_id' => $userId,
            'type' => 'adjustment',
            'amount' => $amount,
            'description' => $description,
            'reference' => $reference,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }

    // =====================================
    // UTILITY METHODS
    // =====================================

    /**
     * Get transaction summary for user
     */
    public static function getUserSummary($userId)
    {
        return [
            'total_earned' => self::forUser($userId)->earned()->sum('amount'),
            'total_redeemed' => self::forUser($userId)->redeemed()->sum('amount'),
            'total_expired' => self::forUser($userId)->expired()->sum('amount'),
            'total_adjustments' => self::forUser($userId)->adjustment()->sum('amount'),
            'transaction_count' => self::forUser($userId)->count(),
            'last_transaction' => self::forUser($userId)->recent()->first(),
        ];
    }

    /**
     * Verify user balance integrity
     */
    public static function verifyUserBalance($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $calculatedBalance = self::forUser($userId)->earned()->sum('amount') 
                           - self::forUser($userId)->redeemed()->sum('amount')
                           + self::forUser($userId)->adjustment()->sum('amount');

        return abs($calculatedBalance - $user->points_balance) < 0.01; // Allow for small floating point differences
    }
    
}