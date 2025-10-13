<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VoucherController extends Controller
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Apply voucher to cart
     */
    public function apply(Request $request)
{
    try {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));

        Log::info('Applying voucher', [
            'code' => $code,
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
        ]);

        // Ambil data cart saat ini
        $cartData = $this->cartService->getCartData();
        if (empty($cartData['items'])) {
            return response()->json([
                'success'   => false,
                'message'   => 'Your cart is empty. Add items to cart before applying voucher.',
                'error_type'=> 'empty_cart',
            ]);
        }

        // Cek apakah voucher yang sama sudah terpasang
        $currentVoucher = Session::get('applied_voucher');
        if ($currentVoucher && ($currentVoucher['voucher_code'] ?? null) === $code) {
            return response()->json([
                'success'   => false,
                'message'   => 'This voucher is already applied to your cart.',
                'error_type'=> 'already_applied',
            ]);
        }

        // Cari voucher valid
        $voucher = Voucher::valid()->where('voucher_code', $code)->first();
        if (!$voucher) {
            return response()->json([
                'success'   => false,
                'message'   => 'Voucher not found or expired.',
                'error_type'=> 'invalid_voucher',
            ]);
        }

        // Validasi eligibility user dan subtotal
        $validation = $voucher->isValidForUser(auth()->id(), $cartData['subtotal']);
        $valid = is_array($validation) ? ($validation['valid'] ?? false) : (bool) $validation;

        if (!$valid) {
            return response()->json([
                'success'   => false,
                'message'   => is_array($validation)
                                ? ($validation['message'] ?? 'Voucher is not valid for your account or order.')
                                : 'Voucher is not valid for your account or order.',
                'error_type'=> 'user_not_eligible',
            ]);
        }

        // Hitung diskon
        $discount = is_array($validation) && isset($validation['discount'])
            ? (float) $validation['discount']
            : (float) $voucher->calculateDiscount($cartData['subtotal']);

        // Simpan voucher terpasang di session
        Session::put('applied_voucher', [
            'id'              => $voucher->id,
            'voucher_code'    => $voucher->voucher_code,
            'name'            => $voucher->name_voucher,
            'voucher_type'    => $voucher->voucher_type,
            'value'           => $voucher->value,
            'discount_amount' => $discount,
            'start_date'      => $voucher->start_date,
            'end_date'        => $voucher->end_date,
            'min_purchase'    => $voucher->min_purchase,
            'is_active'       => $voucher->is_active,
            'applied_at'      => now()->toISOString(),
            'summary'         => method_exists($voucher, 'getSummary') ? $voucher->getSummary() : null,
        ]);

        // Hitung ulang total
        $newTotals = $this->calculateTotalsWithVoucher($cartData, $discount);

        Log::info('Voucher applied successfully', [
            'voucher_code'    => $voucher->voucher_code,
            'voucher_id'      => $voucher->id,
            'discount_amount' => $discount,
            'new_total'       => $newTotals['total'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher applied successfully!',
            'voucher' => [
                'voucher_code'       => $voucher->voucher_code,
                'name'               => $voucher->name_voucher,
                'voucher_type'       => $voucher->voucher_type,
                'discount_amount'    => $discount,
                'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
                'start_date'         => $voucher->start_date,
                'end_date'           => $voucher->end_date,
            ],
            'totals'  => $newTotals,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Please enter a valid voucher code.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('Error applying voucher', [
            'code'  => $request->code ?? null,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success'    => false,
            'message'    => 'Failed to apply voucher. Please try again.',
            'error_type' => 'server_error',
        ], 500);
    }
}

    /**
     * Remove applied voucher from cart
     */
    public function remove(Request $request)
    {
        try {
            $appliedVoucher = Session::get('applied_voucher');
            
            if (!$appliedVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'No voucher is currently applied.',
                    'error_type' => 'no_voucher'
                ]);
            }

            // Remove voucher from session
            Session::forget('applied_voucher');

            // Calculate new totals without voucher
            $cartData = $this->cartService->getCartData();
            $newTotals = $this->calculateTotalsWithoutVoucher($cartData);

            Log::info('Voucher removed successfully', [
                'voucher_code' => $appliedVoucher['voucher_code'],
                'session_id' => session()->getId()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voucher removed successfully.',
                'totals' => $newTotals
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing voucher', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove voucher. Please try again.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Get currently applied voucher information
     */
    public function current(Request $request)
    {
        try {
            $appliedVoucher = Session::get('applied_voucher');
            
            if (!$appliedVoucher) {
                return response()->json([
                    'success' => true,
                    'voucher' => null,
                    'message' => 'No voucher applied'
                ]);
            }

            // Verify voucher is still valid
            $voucher = Voucher::find($appliedVoucher['id']);
            
            if (!$voucher || !$voucher->isValid()) {
                // Remove invalid voucher
                Session::forget('applied_voucher');
                
                return response()->json([
                    'success' => false,
                    'voucher' => null,
                    'message' => 'Applied voucher is no longer valid and has been removed.',
                    'error_type' => 'voucher_invalid'
                ]);
            }

            return response()->json([
                'success' => true,
                'voucher' => $appliedVoucher,
                'message' => 'Voucher information retrieved'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting current voucher', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get voucher information.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Validate voucher without applying (for real-time validation)
     */
    public function validate(Request $request)
{
    try {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));

        // Ambil data cart
        $cartData = $this->cartService->getCartData();
        if (empty($cartData['items'])) {
            return response()->json([
                'valid'   => false,
                'message' => 'Cart is empty',
            ]);
        }

        // Cari voucher yang masih valid
        $voucher = Voucher::valid()->where('voucher_code', $code)->first();
        if (!$voucher) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid voucher code',
            ]);
        }

        // Validasi eligibility
        $validation = $voucher->isValidForUser(auth()->id(), $cartData['subtotal']);
        $valid = is_array($validation) ? ($validation['valid'] ?? false) : (bool) $validation;

        if (!$valid) {
            return response()->json([
                'valid'   => false,
                'message' => is_array($validation)
                                ? ($validation['message'] ?? 'Voucher is not valid for your account or order')
                                : 'Voucher is not valid for your account or order',
            ]);
        }

        // Hitung diskon estimasi
        $discount = is_array($validation) && isset($validation['discount'])
            ? (float) $validation['discount']
            : (float) $voucher->calculateDiscount($cartData['subtotal']);

        return response()->json([
            'valid'   => true,
            'message' => 'Voucher is valid',
            'voucher' => [
                'voucher_code'       => $voucher->voucher_code,
                'name'               => $voucher->name_voucher,
                'summary'            => method_exists($voucher, 'getSummary') ? $voucher->getSummary() : null,
                'estimated_discount' => $discount,
                'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            ],
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'valid'   => false,
            'message' => 'Please enter a valid voucher code.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('Error validating voucher', [
            'code'  => $request->code ?? null,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'valid'   => false,
            'message' => 'Failed to validate voucher',
        ], 500);
    }
}

    /**
     * Get available vouchers for user (promotional purposes)
     */
    public function available(Request $request)
{
    try {
        $cartData = $this->cartService->getCartData();

        // Ambil voucher valid lalu saring berdasarkan eligibility & subtotal saat ini
        $availableVouchers = Voucher::valid()
            ->where('is_active', true)
            ->get()
            ->filter(function ($voucher) use ($cartData) {
                if (empty($cartData['items'])) {
                    return false;
                }
                $validation = $voucher->isValidForUser(auth()->id(), $cartData['subtotal']);
                return is_array($validation) ? ($validation['valid'] ?? false) : (bool) $validation;
            })
            ->map(function ($voucher) use ($cartData) {
                // Estimasi diskon
                $validation = $voucher->isValidForUser(auth()->id(), $cartData['subtotal']);
                $discount = is_array($validation) && isset($validation['discount'])
                    ? (float) $validation['discount']
                    : (float) $voucher->calculateDiscount($cartData['subtotal']);

                // Hitung sisa kuota jika tidak ada kolom dedicated
                $remaining = method_exists($voucher, 'getAttribute') && $voucher->getAttribute('remaining_quota') !== null
                    ? (int) $voucher->remaining_quota
                    : max(0, (int) $voucher->quota - (int) $voucher->total_used);

                return [
                    'voucher_code'       => $voucher->voucher_code,
                    'name'               => $voucher->name_voucher,
                    'summary'            => method_exists($voucher, 'getSummary') ? $voucher->getSummary() : null,
                    'voucher_type'       => $voucher->voucher_type,
                    'estimated_discount' => $discount,
                    'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
                    'end_date'           => optional($voucher->end_date)->format('Y-m-d H:i:s'),
                    'min_purchase'       => (float) $voucher->min_purchase,
                    'remaining_quota'    => $remaining,
                ];
            })
            ->take(5)
            ->values();

        return response()->json([
            'success'  => true,
            'vouchers' => $availableVouchers,
            'message'  => 'Available vouchers retrieved',
        ]);

    } catch (\Exception $e) {
        Log::error('Error getting available vouchers', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success'  => false,
            'message'  => 'Failed to get available vouchers',
            'vouchers' => [],
        ], 500);
    }
}

    /**
     * Calculate totals with applied voucher
     */
    private function calculateTotalsWithVoucher($cartData, $discount): array
    {
        $subtotal = $cartData['subtotal'];
        $shippingCost = Session::get('shipping_cost', 0);
        
        $discountAmount = $discount;
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
     * Calculate totals without voucher
     */
    private function calculateTotalsWithoutVoucher($cartData): array
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
     * Clear voucher when cart is updated (called from CartController)
     */
    public static function clearVoucherOnCartChange()
    {
        $appliedVoucher = Session::get('applied_voucher');
        
        if ($appliedVoucher) {
            Session::forget('applied_voucher');
            
            Log::info('Voucher cleared due to cart change', [
                'voucher_code' => $appliedVoucher['voucher_code']
            ]);
        }
    }

    /**
     * Re-validate applied voucher (called when cart or shipping changes)
     */
    public static function revalidateAppliedVoucher(CartService $cartService)
    {
        $appliedVoucher = Session::get('applied_voucher');
        
        if (!$appliedVoucher) {
            return;
        }

        try {
            $voucher = Voucher::find($appliedVoucher['id']);
            
            if (!$voucher || !$voucher->isValid()) {
                Session::forget('applied_voucher');
                Log::info('Invalid voucher removed during revalidation', [
                    'voucher_code' => $appliedVoucher['voucher_code']
                ]);
                return;
            }

            $cartData = $cartService->getCartData();
            
            if (!$voucher->isValidForUser(auth()->id(), $cartData['subtotal'])) {
                Session::forget('applied_voucher');
                Log::info('Voucher removed during revalidation - no longer applicable', [
                    'voucher_code' => $appliedVoucher['voucher_code'],
                    'reason' => 'User not eligible or cart amount changed'
                ]);
                return;
            }

            // Recalculate discount with new cart data
            $discount = $voucher->calculateDiscount($cartData['subtotal']);

            // Update stored voucher data
            Session::put('applied_voucher', array_merge($appliedVoucher, [
                'discount_amount' => $discount,
                'revalidated_at' => now()->toISOString()
            ]));

            Log::info('Voucher revalidated successfully', [
                'voucher_code' => $appliedVoucher['voucher_code'],
                'new_discount' => $discount
            ]);

        } catch (\Exception $e) {
            Log::error('Error during voucher revalidation', [
                'voucher_code' => $appliedVoucher['voucher_code'],
                'error' => $e->getMessage()
            ]);
            
            // Remove voucher on error to be safe
            Session::forget('applied_voucher');
        }
    }

    /**
     * Get discount information for order creation
     */
    public static function getOrderDiscountData(): ?array
    {
        $appliedVoucher = Session::get('applied_voucher');
        
        if (!$appliedVoucher) {
            return null;
        }

        return [
            'voucher_id' => $appliedVoucher['id'],
            'voucher_code' => $appliedVoucher['voucher_code'],
            'discount_amount' => $appliedVoucher['discount_amount']
        ];
    }

    /**
     * Mark voucher as used (called after successful order)
     */
    public static function markVoucherAsUsed($voucherId)
    {
        try {
            $voucher = Voucher::find($voucherId);
            
            if ($voucher) {
                $voucher->increment('total_used');
                $voucher->decrement('remaining_quota');
                
                Log::info('Voucher usage incremented', [
                    'voucher_id' => $voucherId,
                    'voucher_code' => $voucher->voucher_code,
                    'new_usage_count' => $voucher->total_used,
                    'remaining_quota' => $voucher->remaining_quota
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error marking voucher as used', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Restore voucher usage (called when order is cancelled)
     */
    public static function restoreVoucherUsage($voucherId)
    {
        try {
            $voucher = Voucher::find($voucherId);
            
            if ($voucher) {
                $voucher->decrement('total_used');
                $voucher->increment('remaining_quota');
                
                Log::info('Voucher usage decremented', [
                    'voucher_id' => $voucherId,
                    'voucher_code' => $voucher->voucher_code,
                    'new_usage_count' => $voucher->total_used,
                    'remaining_quota' => $voucher->remaining_quota
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error restoring voucher usage', [
                'voucher_id' => $voucherId,
                'error' => $e->getMessage()
            ]);
        }
    }
}