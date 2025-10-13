<?php

// File: app/Console/Commands/TestCartPersistence.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShoppingCart;
use App\Models\User;
use App\Models\Product;

class TestCartPersistence extends Command
{
    protected $signature = 'cart:test {user_id?}';
    protected $description = 'Test cart persistence functionality';

    public function handle()
    {
        $this->info('🛒 Testing Cart Persistence...');
        
        // Test 1: Check existing cart items
        $userId = $this->argument('user_id') ?? $this->ask('Enter user ID to test (or leave empty to see all cart data)');
        
        if ($userId) {
            $this->testUserCart($userId);
        } else {
            $this->showAllCartData();
        }
        
        return Command::SUCCESS;
    }

    private function testUserCart($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found!");
            return;
        }

        $this->info("👤 Testing cart for user: {$user->name} ({$user->email})");
        
        // Check database cart
        $cartItems = ShoppingCart::where('user_id', $userId)
            ->with('product')
            ->get();
            
        if ($cartItems->isEmpty()) {
            $this->warn("📦 No cart items found in database for this user.");
        } else {
            $this->info("📦 Found {$cartItems->count()} items in database cart:");
            
            $headers = ['Product ID', 'Product Name', 'Quantity', 'Options', 'Created At'];
            $rows = [];
            
            foreach ($cartItems as $item) {
                $rows[] = [
                    $item->product_id,
                    $item->product->name ?? 'Product not found',
                    $item->quantity,
                    json_encode($item->product_options ?? []),
                    $item->created_at->format('Y-m-d H:i:s')
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        // Test adding an item
        if ($this->confirm('Do you want to add a test product to cart?')) {
            $this->addTestProduct($userId);
        }
    }

    private function addTestProduct($userId)
    {
        $product = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->first();
            
        if (!$product) {
            $this->error("❌ No active products found to test with!");
            return;
        }

        $this->info("🔧 Adding test product: {$product->name}");
        
        try {
            ShoppingCart::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => 1,
                'product_options' => ['size' => 'M', 'test' => true]
            ]);
            
            $this->info("✅ Test product added successfully!");
            
            // Show updated cart
            $cartCount = ShoppingCart::where('user_id', $userId)->sum('quantity');
            $this->info("📊 User now has {$cartCount} items in cart.");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to add test product: " . $e->getMessage());
        }
    }

    private function showAllCartData()
    {
        $this->info("📊 All Cart Data Overview:");
        
        $totalUsers = ShoppingCart::distinct('user_id')->count('user_id');
        $totalItems = ShoppingCart::sum('quantity');
        $totalProducts = ShoppingCart::distinct('product_id')->count('product_id');
        
        $this->info("👥 Users with cart items: {$totalUsers}");
        $this->info("📦 Total items in all carts: {$totalItems}");
        $this->info("🏷️  Unique products in carts: {$totalProducts}");
        
        // Show recent cart activities
        $recentItems = ShoppingCart::with(['user', 'product'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        if ($recentItems->isNotEmpty()) {
            $this->info("\n🕒 Recent Cart Activities:");
            $headers = ['User', 'Product', 'Qty', 'Updated At'];
            $rows = [];
            
            foreach ($recentItems as $item) {
                $rows[] = [
                    $item->user->name ?? 'Unknown User',
                    Str::limit($item->product->name ?? 'Product not found', 30),
                    $item->quantity,
                    $item->updated_at->format('M j, H:i')
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        if ($this->confirm('Do you want to clean up old cart items (30+ days)?')) {
            $this->cleanupOldCarts();
        }
    }

    private function cleanupOldCarts()
    {
        $oldItems = ShoppingCart::where('created_at', '<', now()->subDays(30))->count();
        
        if ($oldItems > 0) {
            ShoppingCart::where('created_at', '<', now()->subDays(30))->delete();
            $this->info("🗑️  Cleaned up {$oldItems} old cart items (30+ days old)");
        } else {
            $this->info("✨ No old cart items to clean up.");
        }
    }
}