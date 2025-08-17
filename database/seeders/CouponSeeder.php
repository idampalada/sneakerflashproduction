<?php
// File: database/seeders/CouponSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run()
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome New Customer',
                'description' => 'Special 10% discount for new customers',
                'type' => 'percentage',
                'value' => 10,
                'minimum_amount' => 100000,
                'maximum_discount' => 200000,
                'usage_limit' => 1000,
                'is_active' => true,
                'expires_at' => now()->addMonths(6),
            ],
            [
                'code' => 'SAVE50K',
                'name' => 'Fixed Amount Discount',
                'description' => 'Get Rp 50,000 off your order',
                'type' => 'fixed_amount',
                'value' => 50000,
                'minimum_amount' => 250000,
                'usage_limit' => 500,
                'is_active' => true,
                'expires_at' => now()->addMonth(),
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping Promo',
                'description' => 'Get free shipping on orders above Rp 200,000',
                'type' => 'free_shipping',
                'value' => 0,
                'minimum_amount' => 200000,
                'usage_limit' => 200,
                'is_active' => true,
                'expires_at' => now()->addWeeks(2),
            ],
            [
                'code' => 'FLASH25',
                'name' => '24 Hour Flash Sale',
                'description' => '25% off everything - limited time only!',
                'type' => 'percentage',
                'value' => 25,
                'minimum_amount' => 150000,
                'maximum_discount' => 500000,
                'usage_limit' => 100,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDay(),
            ],
            [
                'code' => 'STUDENT15',
                'name' => 'Student Discount',
                'description' => '15% discount for students',
                'type' => 'percentage',
                'value' => 15,
                'minimum_amount' => 75000,
                'maximum_discount' => 150000,
                'is_active' => true,
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'EXPIRED',
                'name' => 'Expired Coupon (Test)',
                'description' => 'This coupon has expired - for testing',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 100000,
                'usage_limit' => 50,
                'is_active' => true,
                'expires_at' => now()->subDays(7), // Expired 7 days ago
            ],
            [
                'code' => 'FUTURE',
                'name' => 'Future Coupon (Test)',
                'description' => 'This coupon starts in the future - for testing',
                'type' => 'fixed_amount',
                'value' => 100000,
                'minimum_amount' => 300000,
                'usage_limit' => 50,
                'is_active' => true,
                'starts_at' => now()->addWeek(), // Starts next week
                'expires_at' => now()->addMonth(),
            ],
            [
                'code' => 'BIGSPENDER',
                'name' => 'High Value Customer Discount',
                'description' => 'Exclusive discount for orders above Rp 1,000,000',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 1000000,
                'maximum_discount' => 1000000,
                'usage_limit' => 50,
                'is_active' => true,
                'expires_at' => now()->addMonths(2),
            ],
            [
                'code' => 'WEEKEND',
                'name' => 'Weekend Special',
                'description' => 'Weekend-only discount',
                'type' => 'percentage',
                'value' => 12,
                'minimum_amount' => 120000,
                'maximum_discount' => 300000,
                'usage_limit' => 300,
                'is_active' => true,
                'expires_at' => now()->addWeeks(4),
            ],
            [
                'code' => 'LOYALCUSTOMER',
                'name' => 'Loyal Customer Reward',
                'description' => 'Thank you for being a loyal customer!',
                'type' => 'fixed_amount',
                'value' => 75000,
                'minimum_amount' => 200000,
                'usage_limit' => 1000,
                'is_active' => true,
                'expires_at' => now()->addMonths(4),
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }

        $this->command->info('Created ' . count($coupons) . ' test coupons');
    }
}