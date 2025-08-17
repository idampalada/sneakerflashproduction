<?php

// FILE: app/Console/Commands/UpdateOrderStatus.php
// Jalankan: php artisan make:command UpdateOrderStatus

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'order:update-status 
                            {order_number? : Order number to check} 
                            {--all : Check all pending orders}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     */
    protected $description = 'Update order status by checking with Midtrans API (Single Status System)';

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
        $this->info('🚀 Starting order status update (Single Status System)...');
        
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
                ['Current Status', $order->status],
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

        // Map to order status
        $newStatus = $this->mapMidtransToOrderStatus($notification['payment_status'], $notification['transaction_status'], $notification['fraud_status']);
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            $this->info("✅ Order status is already up to date: {$newStatus}");
            return 0;
        }

        $this->warn("🔄 Order status needs update:");
        $this->warn("   Old: {$oldStatus}");
        $this->warn("   New: {$newStatus}");

        if ($dryRun) {
            $this->info('🔍 DRY RUN: Would update order status but no changes made');
            return 0;
        }

        // Confirm update
        if (!$this->confirm('Do you want to update the order status?')) {
            $this->info('❌ Update cancelled by user');
            return 0;
        }

        // Update order status
        $order->update([
            'status' => $newStatus,
            'payment_response' => json_encode($midtransStatus)
        ]);

        // Handle stock restoration for cancelled orders
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
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

        $this->info("✅ Order status updated successfully!");
        $this->info("   Order: {$order->order_number}");
        $this->info("   Status: {$oldStatus} → {$newStatus}");

        return 0;
    }

    /**
     * Update all pending orders
     */
    private function updateAllPendingOrders($dryRun = false)
    {
        $this->info('🔍 Checking all pending orders...');

        // Get all pending orders with online payment methods
        $pendingOrders = Order::where('status', 'pending')
                             ->where('payment_method', '!=', 'cod')
                             ->whereNotNull('snap_token') // Only orders with Midtrans tokens
                             ->orderBy('created_at', 'desc')
                             ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('✅ No pending orders with payment tokens found');
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

                $newStatus = $this->mapMidtransToOrderStatus($notification['payment_status'], $notification['transaction_status'], $notification['fraud_status']);
                
                if ($order->status === $newStatus) {
                    $this->info("   ✅ Status unchanged: {$newStatus}");
                    $unchanged++;
                    continue;
                }

                if (!$dryRun) {
                    // Update order status
                    $order->update([
                        'status' => $newStatus,
                        'payment_response' => json_encode($midtransStatus)
                    ]);

                    // Handle stock restoration for cancelled orders
                    if ($newStatus === 'cancelled') {
                        foreach ($order->orderItems as $item) {
                            $product = \App\Models\Product::find($item->product_id);
                            if ($product) {
                                $product->increment('stock_quantity', $item->quantity);
                            }
                        }
                    }
                }

                $this->info("   ✅ Updated: {$order->status} → {$newStatus}");
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

    /**
     * Map Midtrans payment status to order status
     */
    private function mapMidtransToOrderStatus($paymentStatus, $transactionStatus, $fraudStatus = 'accept')
    {
        // Handle fraud status first
        if ($fraudStatus === 'challenge') {
            return 'pending'; // Keep as pending for manual review
        }
        
        if ($fraudStatus === 'deny') {
            return 'cancelled';
        }

        // Map based on payment status from MidtransService
        switch ($paymentStatus) {
            case 'paid':
                return 'paid'; // Automatically mark as paid when payment successful
                
            case 'pending':
                return 'pending'; // Keep as pending
                
            case 'failed':
            case 'cancelled':
                return 'cancelled';
                
            case 'refunded':
                return 'refund';
                
            case 'challenge':
                return 'pending'; // Keep as pending for manual review
                
            default:
                $this->warn("⚠️ Unknown payment status: {$paymentStatus}");
                return 'pending'; // Default to pending for unknown status
        }
    }
}