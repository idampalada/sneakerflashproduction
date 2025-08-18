<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use App\Http\Controllers\Frontend\GineeStockSyncController;
use Illuminate\Console\Command;

class GineeStockSyncCommand extends Command
{
    protected $signature = 'ginee:stock-sync 
                            {action : Action to perform (pull, push, test, status)}
                            {--batch-size=50 : Batch size for processing}
                            {--warehouse-id= : Specific warehouse ID}
                            {--force : Force update all items}
                            {--skus=* : Specific SKUs to sync}';

    protected $description = 'Synchronize stock between Laravel and Ginee';

    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info("🔄 Starting Ginee stock sync: {$action}");
        $this->newLine();

        try {
            switch ($action) {
                case 'pull':
                    return $this->pullProducts();
                
                case 'push':
                    return $this->pushStock();
                
                case 'test':
                    return $this->testEndpoints();
                
                case 'status':
                    return $this->showStatus();
                    
                default:
                    $this->error("Unknown action: {$action}");
                    $this->line('Available actions: pull, push, test, status');
                    return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function pullProducts()
    {
        $this->info('📥 Pulling products from Ginee...');
        
        $ginee = new GineeClient();
        $batchSize = (int)$this->option('batch-size');
        
        $result = $ginee->pullAllProducts($batchSize);
        
        if (($result['code'] ?? null) === 'SUCCESS') {
            $data = $result['data'];
            $this->line("✅ Successfully pulled {$data['total_count']} products");
            $this->line("📄 Fetched in {$data['pages_fetched']} pages");
            
            // Show sample products
            $products = array_slice($data['products'], 0, 5);
            if (!empty($products)) {
                $this->newLine();
                $this->line('📦 Sample products:');
                foreach ($products as $i => $product) {
                    $name = $product['productName'] ?? 'Unknown';
                    $sku = $product['masterSku'] ?? 'No SKU';
                    $this->line("   " . ($i+1) . ". {$name} ({$sku})");
                }
            }
        } else {
            $this->error('❌ Failed to pull products: ' . ($result['message'] ?? 'Unknown error'));
            return 1;
        }
        
        return 0;
    }

    private function pushStock()
    {
        $this->info('📤 Pushing stock to Ginee...');
        
        // This would integrate with the controller logic
        $this->line('💡 Use the web interface or API endpoint for stock push');
        $this->line('   POST /integrations/ginee/push-stock');
        
        return 0;
    }

    private function testEndpoints()
    {
        $this->info('🧪 Testing Ginee stock sync endpoints...');
        
        $ginee = new GineeClient();
        $result = $ginee->testStockSyncEndpoints();
        
        if (($result['code'] ?? null) === 'SUCCESS') {
            $summary = $result['data']['summary'];
            
            $tableData = [];
            foreach ($summary as $endpoint => $status) {
                $statusIcon = $status['success'] ? '✅' : '❌';
                $tableData[] = [
                    ucfirst(str_replace('_', ' ', $endpoint)),
                    $statusIcon . ' ' . ($status['success'] ? 'Success' : 'Failed'),
                    $status['message'],
                    $status['transaction_id'] ?? 'N/A'
                ];
            }
            
            $this->table(['Endpoint', 'Status', 'Message', 'Transaction ID'], $tableData);
            
            $successCount = count(array_filter($summary, fn($s) => $s['success']));
            $totalCount = count($summary);
            
            $this->newLine();
            if ($successCount === $totalCount) {
                $this->info("🎉 ALL ENDPOINTS WORKING! ({$successCount}/{$totalCount})");
            } else {
                $this->line("⚠️  {$successCount}/{$totalCount} endpoints working");
            }
        } else {
            $this->error('❌ Endpoint test failed');
            return 1;
        }
        
        return 0;
    }

    private function showStatus()
    {
        $this->info('📊 Ginee Stock Sync Status');
        
        // Get local database stats
        $stats = [
            'total_products' => \App\Models\Product::count(),
            'products_with_sku' => \App\Models\Product::whereNotNull('sku')->count(),
            'synced_products' => \App\Models\Product::whereNotNull('ginee_last_sync')->count(),
            'pending_sync' => \App\Models\Product::where(function ($q) {
                $q->whereNull('ginee_last_sync')
                  ->orWhere('ginee_sync_status', '!=', 'synced')
                  ->orWhere('updated_at', '>', \DB::raw('ginee_last_sync'));
            })->count(),
            'last_sync' => \App\Models\Product::max('ginee_last_sync'),
        ];
        
        $this->table(['Metric', 'Value'], [
            ['Total Products', number_format($stats['total_products'])],
            ['Products with SKU', number_format($stats['products_with_sku'])],
            ['Synced Products', number_format($stats['synced_products'])],
            ['Pending Sync', number_format($stats['pending_sync'])],
            ['Last Sync', $stats['last_sync'] ? $stats['last_sync']->format('Y-m-d H:i:s') : 'Never'],
        ]);
        
        return 0;
    }
}

// =============================================================================
// File: routes/web.php (add these routes to existing ginee section)
// =============================================================================

/*
// Add to existing Ginee routes section
Route::middleware(['auth'])->prefix('integrations/ginee')->name('ginee.')->group(function () {
    // Existing routes...
    
    // Stock Synchronization Routes
    Route::post('/pull-products', [GineeStockSyncController::class, 'pullProducts'])->name('pull.products');
    Route::post('/push-stock', [GineeStockSyncController::class, 'pushStock'])->name('push.stock');
    Route::get('/ginee-stock', [GineeStockSyncController::class, 'getGineeStock'])->name('ginee.stock');
    Route::get('/test-endpoints', [GineeStockSyncController::class, 'testEndpoints'])->name('test.endpoints');
    
    // Sync status and monitoring
    Route::get('/sync-status', [GineeStockSyncController::class, 'getSyncStatus'])->name('sync.status');
});
*/

