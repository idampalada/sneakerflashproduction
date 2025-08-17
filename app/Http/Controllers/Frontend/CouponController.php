<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CouponController extends Controller
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Apply coupon/voucher to cart
     */
    public function apply(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:50',
            ]);

            $code = strtoupper(trim($request->code));
            
            Log::info('Applying coupon', [
                'code' => $code,
                'session_id' => session()->getId(),
                'user_id' => auth()->id()
            ]);

            // Get current cart data
            $cartData = $this->cartService->getCartData();
            
            if (empty($cartData['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty. Add items to cart before applying coupon.',
                    'error_type' => 'empty_cart'
                ]);
            }

            // Check if coupon is already applied
            $currentCoupon = Session::get('applied_coupon');
            if ($currentCoupon && $currentCoupon['code'] === $code) {
                return response()->json([
                    'success' => false,
                    'message' => 'This coupon is already applied to your cart.',
                    'error_type' => 'already_applied'
                ]);
            }

            // Validate and apply coupon
            $result = Coupon::validateAndApply(
                $code,
                $cartData['items'],
                $cartData['subtotal'],
                Session::get('shipping_cost', 0)
            );

            if (!$result['success']) {
                Log::warning('Coupon validation failed', [
                    'code' => $code,
                    'message' => $result['message'],
                    'cart_subtotal' => $cartData['subtotal']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_type' => 'validation_failed'
                ]);
            }

            $coupon = $result['coupon'];
            $discount = $result['discount'];

            // Store applied coupon in session
            Session::put('applied_coupon', [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount_amount' => $discount['discount_amount'],
                'free_shipping' => $discount['free_shipping'],
                'applied_at' => now()->toISOString(),
                'summary' => $coupon->getSummary()
            ]);

            // Calculate new totals
            $newTotals = $this->calculateTotalsWithCoupon($cartData, $discount);

            Log::info('Coupon applied successfully', [
                'code' => $code,
                'coupon_id' => $coupon->id,
                'discount_amount' => $discount['discount_amount'],
                'free_shipping' => $discount['free_shipping'],
                'new_total' => $newTotals['total']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon applied successfully!',
                'coupon' => [
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'summary' => $coupon->getSummary(),
                    'type' => $coupon->type,
                    'discount_amount' => $discount['discount_amount'],
                    'free_shipping' => $discount['free_shipping'],
                    'formatted_discount' => 'Rp ' . number_format($discount['discount_amount'], 0, ',', '.')
                ],
                'totals' => $newTotals
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid coupon code.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error applying coupon', [
                'code' => $request->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply coupon. Please try again.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Remove applied coupon from cart
     */
    public function remove(Request $request)
    {
        try {
            $appliedCoupon = Session::get('applied_coupon');
            
            if (!$appliedCoupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'No coupon is currently applied.',
                    'error_type' => 'no_coupon'
                ]);
            }

            // Remove coupon from session
            Session::forget('applied_coupon');

            // Calculate new totals without coupon
            $cartData = $this->cartService->getCartData();
            $newTotals = $this->calculateTotalsWithoutCoupon($cartData);

            Log::info('Coupon removed successfully', [
                'code' => $appliedCoupon['code'],
                'session_id' => session()->getId()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon removed successfully.',
                'totals' => $newTotals
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing coupon', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove coupon. Please try again.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Get currently applied coupon information
     */
    public function current(Request $request)
    {
        try {
            $appliedCoupon = Session::get('applied_coupon');
            
            if (!$appliedCoupon) {
                return response()->json([
                    'success' => true,
                    'coupon' => null,
                    'message' => 'No coupon applied'
                ]);
            }

            // Verify coupon is still valid
            $coupon = Coupon::find($appliedCoupon['id']);
            
            if (!$coupon || !$coupon->isValid()) {
                // Remove invalid coupon
                Session::forget('applied_coupon');
                
                return response()->json([
                    'success' => false,
                    'coupon' => null,
                    'message' => 'Applied coupon is no longer valid and has been removed.',
                    'error_type' => 'coupon_invalid'
                ]);
            }

            return response()->json([
                'success' => true,
                'coupon' => $appliedCoupon,
                'message' => 'Coupon information retrieved'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting current coupon', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get coupon information.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Validate coupon without applying (for real-time validation)
     */
    public function validate(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|max:50',
            ]);

            $code = strtoupper(trim($request->code));
            
            // Get current cart data
            $cartData = $this->cartService->getCartData();
            
            if (empty($cartData['items'])) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Cart is empty'
                ]);
            }

            // Find coupon
            $coupon = Coupon::findByCode($code);
            
            if (!$coupon) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid coupon code'
                ]);
            }

            // Check if can be applied
            $validation = $coupon->canBeAppliedToCart($cartData['items'], $cartData['subtotal']);

            if ($validation['valid']) {
                $discount = $coupon->calculateDiscount(
                    $cartData['items'],
                    $cartData['subtotal'],
                    Session::get('shipping_cost', 0)
                );

                return response()->json([
                    'valid' => true,
                    'message' => 'Coupon is valid',
                    'coupon' => [
                        'code' => $coupon->code,
                        'name' => $coupon->name,
                        'summary' => $coupon->getSummary(),
                        'estimated_discount' => $discount['discount_amount'],
                        'formatted_discount' => 'Rp ' . number_format($discount['discount_amount'], 0, ',', '.')
                    ]
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => $validation['message']
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating coupon', [
                'code' => $request->code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Failed to validate coupon'
            ], 500);
        }
    }

    /**
     * Get available coupons for user (promotional purposes)
     */
    public function available(Request $request)
    {
        try {
            $cartData = $this->cartService->getCartData();
            
            // Get currently valid coupons that can be applied to cart
            $availableCoupons = Coupon::valid()
                ->where('is_active', true)
                ->get()
                ->filter(function ($coupon) use ($cartData) {
                    if (empty($cartData['items'])) {
                        return false;
                    }
                    
                    $validation = $coupon->canBeAppliedToCart($cartData['items'], $cartData['subtotal']);
                    return $validation['valid'];
                })
                ->map(function ($coupon) use ($cartData) {
                    $discount = $coupon->calculateDiscount(
                        $cartData['items'],
                        $cartData['subtotal'],
                        Session::get('shipping_cost', 0)
                    );

                    return [
                        'code' => $coupon->code,
                        'name' => $coupon->name,
                        'summary' => $coupon->getSummary(),
                        'type' => $coupon->type,
                        'estimated_discount' => $discount['discount_amount'],
                        'formatted_discount' => 'Rp ' . number_format($discount['discount_amount'], 0, ',', '.'),
                        'expires_at' => $coupon->expires_at?->format('Y-m-d H:i:s'),
                        'is_expiring_soon' => $coupon->is_expiring_soon
                    ];
                })
                ->take(5) // Limit to 5 suggestions
                ->values();

            return response()->json([
                'success' => true,
                'coupons' => $availableCoupons,
                'message' => 'Available coupons retrieved'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available coupons', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get available coupons',
                'coupons' => []
            ], 500);
        }
    }

    /**
     * Calculate totals with applied coupon
     */
    private function calculateTotalsWithCoupon($cartData, $discount): array
    {
        $subtotal = $cartData['subtotal'];
        $shippingCost = Session::get('shipping_cost', 0);
        
        // Apply free shipping if applicable
        if ($discount['free_shipping']) {
            $shippingCost = 0;
            Session::put('shipping_cost', 0);
        }
        
        $discountAmount = $discount['discount_amount'];
        $total = $subtotal + $shippingCost - $discountAmount;
        
        // Ensure total doesn't go negative
        $total = max(0, $total);

        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'formatted' => [
                'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                'shipping_cost' => $shippingCost > 0 ? 'Rp ' . number_format($shippingCost, 0, ',', '.') : 'Free',
                'discount_amount' => 'Rp ' . number_format($discountAmount, 0, ',', '.'),
                'total' => 'Rp ' . number_format($total, 0, ',', '.')
            ]
        ];
    }

    /**
     * Calculate totals without coupon
     */
    private function calculateTotalsWithoutCoupon($cartData): array
    {
        $subtotal = $cartData['subtotal'];
        $shippingCost = Session::get('original_shipping_cost', Session::get('shipping_cost', 0));
        
        // Restore original shipping cost
        Session::put('shipping_cost', $shippingCost);
        
        $total = $subtotal + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => 0,
            'total' => $total,
            'formatted' => [
                'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                'shipping_cost' => $shippingCost > 0 ? 'Rp ' . number_format($shippingCost, 0, ',', '.') : 'Free',
                'discount_amount' => 'Rp 0',
                'total' => 'Rp ' . number_format($total, 0, ',', '.')
            ]
        ];
    }

    /**
     * Clear coupon when cart is updated (called from CartController)
     */
    public static function clearCouponOnCartChange()
    {
        $appliedCoupon = Session::get('applied_coupon');
        
        if ($appliedCoupon) {
            Session::forget('applied_coupon');
            
            Log::info('Coupon cleared due to cart change', [
                'code' => $appliedCoupon['code']
            ]);
        }
    }

    /**
     * Re-validate applied coupon (called when cart or shipping changes)
     */
    public static function revalidateAppliedCoupon(CartService $cartService)
    {
        $appliedCoupon = Session::get('applied_coupon');
        
        if (!$appliedCoupon) {
            return;
        }

        try {
            $coupon = Coupon::find($appliedCoupon['id']);
            
            if (!$coupon || !$coupon->isValid()) {
                Session::forget('applied_coupon');
                Log::info('Invalid coupon removed during revalidation', [
                    'code' => $appliedCoupon['code']
                ]);
                return;
            }

            $cartData = $cartService->getCartData();
            $validation = $coupon->canBeAppliedToCart($cartData['items'], $cartData['subtotal']);
            
            if (!$validation['valid']) {
                Session::forget('applied_coupon');
                Log::info('Coupon removed during revalidation - no longer applicable', [
                    'code' => $appliedCoupon['code'],
                    'reason' => $validation['message']
                ]);
                return;
            }

            // Recalculate discount with new cart data
            $discount = $coupon->calculateDiscount(
                $cartData['items'],
                $cartData['subtotal'],
                Session::get('shipping_cost', 0)
            );

            // Update stored coupon data
            Session::put('applied_coupon', array_merge($appliedCoupon, [
                'discount_amount' => $discount['discount_amount'],
                'free_shipping' => $discount['free_shipping'],
                'revalidated_at' => now()->toISOString()
            ]));

            Log::info('Coupon revalidated successfully', [
                'code' => $appliedCoupon['code'],
                'new_discount' => $discount['discount_amount']
            ]);

        } catch (\Exception $e) {
            Log::error('Error during coupon revalidation', [
                'code' => $appliedCoupon['code'],
                'error' => $e->getMessage()
            ]);
            
            // Remove coupon on error to be safe
            Session::forget('applied_coupon');
        }
    }

    /**
     * Get discount information for order creation
     */
    public static function getOrderDiscountData(): ?array
    {
        $appliedCoupon = Session::get('applied_coupon');
        
        if (!$appliedCoupon) {
            return null;
        }

        return [
            'coupon_id' => $appliedCoupon['id'],
            'coupon_code' => $appliedCoupon['code'],
            'discount_amount' => $appliedCoupon['discount_amount'],
            'free_shipping' => $appliedCoupon['free_shipping'] ?? false
        ];
    }

    /**
     * Mark coupon as used (called after successful order)
     */
    public static function markCouponAsUsed($couponId)
    {
        try {
            $coupon = Coupon::find($couponId);
            
            if ($coupon) {
                $coupon->incrementUsage();
                
                Log::info('Coupon usage incremented', [
                    'coupon_id' => $couponId,
                    'code' => $coupon->code,
                    'new_usage_count' => $coupon->used_count
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error marking coupon as used', [
                'coupon_id' => $couponId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Restore coupon usage (called when order is cancelled)
     */
    public static function restoreCouponUsage($couponId)
    {
        try {
            $coupon = Coupon::find($couponId);
            
            if ($coupon) {
                $coupon->decrementUsage();
                
                Log::info('Coupon usage decremented', [
                    'coupon_id' => $couponId,
                    'code' => $coupon->code,
                    'new_usage_count' => $coupon->used_count
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error restoring coupon usage', [
                'coupon_id' => $couponId,
                'error' => $e->getMessage()
            ]);
        }
    }
}