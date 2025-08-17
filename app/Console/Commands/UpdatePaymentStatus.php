<?php

// FILE: app/Console/Commands/UpdatePaymentStatus.php
// Jalankan: php artisan make:command UpdatePaymentStatus

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

class UpdatePaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payment:update-status 
                            {order_number? : Order number to check} 
                            {--all : Check all pending orders}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     */
    protected $description = 'Update payment status by checking with Midtrans API';

    private $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        parent::__construct();
        $this->midtransService = $midtransService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting payment status update...');
        
        if (!$this->midtransService->isConfigured()) {
            $this->error('❌ Midtrans is not properly configured. Check your .env file.');
            return 1;
        }

        $orderNumber = $this->argument('order_number');
        $checkAll = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual updates will be made');
        }

        if ($orderNumber) {
            return $this->updateSingleOrder($orderNumber, $dryRun);
        } elseif ($checkAll) {
            return $this->updateAllPendingOrders($dryRun);
        } else {
            $this->error('❌ Please specify either an order number or use --all flag');
            return 1;
        }
    }

    /**
     * Update single order
     */
    private function updateSingleOrder($orderNumber, $dryRun = false)
    {
        $this->info("🔍 Checking order: {$orderNumber}");

        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            $this->error("❌ Order {$orderNumber} not found");
            return 1;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['Order Number', $order->order_number],
                ['Customer', $order->customer_name],
                ['Current Payment Status', $order->payment_status],
                ['Current Order Status', $order->status],
                ['Total Amount', 'Rp ' . number_format($order->total_amount, 0, ',', '.')],
                ['Payment Method', $order->payment_method],
                ['Created', $order->created_at->format('Y-m-d H:i:s')],
            ]
        );

        // Get status from Midtrans
        $this->info('🔄 Checking with Midtrans API...');
        $midtransStatus = $this->midtransService->getTransactionStatus($orderNumber);
        
        if (!$midtransStatus) {
            $this->error('❌ Failed to get status from Midtrans API');
            return 1;
        }

        $this->info('✅ Midtrans API response received');
        $this->table(
            ['Field', 'Value'],
            [
                ['Transaction Status', $midtransStatus['transaction_status'] ?? 'unknown'],
                ['Fraud Status', $midtransStatus['fraud_status'] ?? 'unknown'],
                ['Payment Type', $midtransStatus['payment_type'] ?? 'unknown'],
                ['Transaction Time', $midtransStatus['transaction_time'] ?? 'unknown'],
                ['Gross Amount', $midtransStatus['gross_amount'] ?? 'unknown'],
            ]
        );

        // Process notification
        $notification = $this->midtransService->handleNotification($midtransStatus);
        
        if (!$notification) {
            $this->error('❌ Failed to process Midtrans notification');
            return 1;
        }

        $newPaymentStatus = $notification['payment_status'];
        $oldPaymentStatus = $order->payment_status;

        if ($oldPaymentStatus === $newPaymentStatus) {
            $this->info("✅ Payment status is already up to date: {$newPaymentStatus}");
            return 0;
        }

        $this->warn("🔄 Payment status needs update:");
        $this->warn("   Old: {$oldPaymentStatus}");
        $this->warn("   New: {$newPaymentStatus}");

        if ($dryRun) {
            $this->info('🔍 DRY RUN: Would update payment status but no changes made');
            return 0;
        }

        // Confirm update
        if (!$this->confirm('Do you want to update the payment status?')) {
            $this->info('❌ Update cancelled by user');
            return 0;
        }

        // Update payment status
        $order->update([
            'payment_status' => $newPaymentStatus,
            'payment_response' => json_encode($midtransStatus)
        ]);

        // Update order status if needed
        if ($newPaymentStatus === 'paid' && $order->status === 'pending') {
            $order->update(['status' => 'confirmed']);
            $this->info('✅ Order status updated to: confirmed');
        } elseif (in_array($newPaymentStatus, ['failed', 'cancelled'])) {
            $order->update(['status' => 'cancelled']);
            $this->info('✅ Order status updated to: cancelled');
            
            // Restore stock
            $restoredItems = 0;
            foreach ($order->orderItems as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                    $restoredItems++;
                }
            }
            $this->info("📦 Stock restored for {$restoredItems} items");
        }

        $this->info("✅ Payment status updated successfully!");
        $this->info("   Order: {$order->order_number}");
        $this->info("   Payment Status: {$oldPaymentStatus} → {$newPaymentStatus}");
        $this->info("   Order Status: {$order->fresh()->status}");

        return 0;
    }

    /**
     * Update all pending orders
     */
    private function updateAllPendingOrders($dryRun = false)
    {
        $this->info('🔍 Checking all pending payment orders...');

        $pendingOrders = Order::where('payment_status', 'pending')
                             ->where('payment_method', '!=', 'cod')
                             ->orderBy('created_at', 'desc')
                             ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('✅ No pending payment orders found');
            return 0;
        }

        $this->info("📋 Found {$pendingOrders->count()} pending orders");

        $updated = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($pendingOrders as $order) {
            $this->info("🔄 Checking {$order->order_number}...");

            try {
                $midtransStatus = $this->midtransService->getTransactionStatus($order->order_number);
                
                if (!$midtransStatus) {
                    $this->warn("   ⚠️ Failed to get Midtrans status");
                    $failed++;
                    continue;
                }

                $notification = $this->midtransService->handleNotification($midtransStatus);
                
                if (!$notification) {
                    $this->warn("   ⚠️ Failed to process notification");
                    $failed++;
                    continue;
                }

                $newStatus = $notification['payment_status'];
                
                if ($order->payment_status === $newStatus) {
                    $this->info("   ✅ Status unchanged: {$newStatus}");
                    $unchanged++;
                    continue;
                }

                if (!$dryRun) {
                    // Update payment status
                    $order->update([
                        'payment_status' => $newStatus,
                        'payment_response' => json_encode($midtransStatus)
                    ]);

                    // Update order status
                    if ($newStatus === 'paid' && $order->status === 'pending') {
                        $order->update(['status' => 'confirmed']);
                    } elseif (in_array($newStatus, ['failed', 'cancelled'])) {
                        $order->update(['status' => 'cancelled']);
                    }
                }

                $this->info("   ✅ Updated: {$order->payment_status} → {$newStatus}");
                $updated++;

            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                $failed++;
            }
        }

        // Summary
        $this->info('');
        $this->info('📊 SUMMARY:');
        $this->info("   Total Orders: {$pendingOrders->count()}");
        $this->info("   Updated: {$updated}");
        $this->info("   Unchanged: {$unchanged}");
        $this->info("   Failed: {$failed}");

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual updates were made');
        }

        return 0;
    }
}