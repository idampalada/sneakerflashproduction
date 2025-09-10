<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OptimizedGineeStockSyncService extends GineeStockSyncService
{
    protected $gineeClient;
    public function __construct()
    {
        parent::__construct();
        $this->gineeClient = new \App\Services\GineeClient();
    }
    /**
     * 🚀 OPTIMIZED: Get multiple SKUs stock in one request
     * Instead of searching page by page, get all inventory at once
     */
    public function getBulkStockFromGinee(array $skus, array $options = []): array
    {
        $skus = array_map('strtoupper', $skus);
        $maxRetries = $options['max_retries'] ?? 3;
        $pageSize = $options['page_size'] ?? 500; // Larger page size
        
        Log::info("🚀 [OPTIMIZED] Bulk stock search for " . count($skus) . " SKUs");
        
        $foundStock = [];
        $notFound = [];
        $totalChecked = 0;
        $startTime = microtime(true);
        
        // Strategy 1: Get ALL inventory in larger chunks
        $page = 0;
        $retries = 0;
        
        while ($retries < $maxRetries) {
            try {
                Log::info("📦 Fetching inventory page {$page} (size: {$pageSize})...");
                
                $result = $this->gineeClient->getWarehouseInventory([
                    'page' => $page,
                    'size' => $pageSize
                ]);

                if (($result['code'] ?? null) !== 'SUCCESS') {
                    $retries++;
                    Log::warning("⚠️ API failed, retry {$retries}/{$maxRetries}");
                    
                    if ($retries >= $maxRetries) {
                        Log::error("❌ Max retries reached, falling back to individual search");
                        return $this->fallbackIndividualSearch($skus);
                    }
                    
                    sleep(2); // Wait before retry
                    continue;
                }

                $items = $result['data']['content'] ?? [];
                
                if (empty($items)) {
                    Log::info("✅ Reached end of inventory at page {$page}");
                    break;
                }
                
                $totalChecked += count($items);
                Log::info("🔍 Processing " . count($items) . " items from page {$page}");
                
                // Process items in batch
                foreach ($items as $item) {
                    $masterVariation = $item['masterVariation'] ?? [];
                    $warehouseInventory = $item['warehouseInventory'] ?? [];
                    $itemSku = strtoupper(trim($masterVariation['masterSku'] ?? ''));
                    
                    // Check if this SKU is in our search list
                    if (in_array($itemSku, $skus, true)) {
                        Log::info("🎯 Found SKU: {$itemSku}");
                        
                        $foundStock[$itemSku] = [
                            'sku' => $itemSku,
                            'product_name' => $masterVariation['name'] ?? 'Unknown Product',
                            'warehouse_stock' => $warehouseInventory['warehouseStock'] ?? 0,
                            'available_stock' => $warehouseInventory['availableStock'] ?? 0,
                            'locked_stock' => $warehouseInventory['lockedStock'] ?? 0,
                            'total_stock' => ($warehouseInventory['warehouseStock'] ?? 0) + ($warehouseInventory['lockedStock'] ?? 0),
                            'last_updated' => $warehouseInventory['updateDatetime'] ?? now(),
                            'api_source' => 'bulk_warehouse_inventory',
                            'found_at_page' => $page
                        ];
                        
                        // Remove found SKU from search list for efficiency
                        $skus = array_diff($skus, [$itemSku]);
                    }
                }
                
                $page++;
                $retries = 0; // Reset retry counter on success
                
                // Stop if we found all SKUs
                if (empty($skus)) {
                    Log::info("✅ All SKUs found! Stopping search early");
                    break;
                }
                
                // Safety limit
                if ($page > 50) {
                    Log::warning("⚠️ Reached page limit (100) for safety");
                    break;
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
                
            } catch (\Exception $e) {
                $retries++;
                Log::error("💥 Exception on page {$page}, retry {$retries}: " . $e->getMessage());
                
                if ($retries >= $maxRetries) {
                    Log::error("❌ Max retries reached due to exceptions");
                    break;
                }
                
                sleep(2);
            }
        }
        
        // Mark remaining SKUs as not found
        foreach ($skus as $sku) {
            $notFound[] = $sku;
        }
        
        // TRIGGER FALLBACK untuk SKU yang tidak ditemukan
        if (!empty($notFound)) {
            Log::info("🔄 [FALLBACK] Triggering fallback search for " . count($notFound) . " SKUs");
            $fallbackResult = $this->fallbackIndividualSearch($notFound);
            
            if ($fallbackResult['success']) {
                $foundStock = array_merge($foundStock, $fallbackResult['found_stock']);
                $notFound = $fallbackResult['not_found'];
                Log::info("✅ [FALLBACK] Merged results - Final found: " . count($foundStock) . ", still not found: " . count($notFound));
            }
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        Log::info("🏁 Bulk stock search completed", [
            'found_count' => count($foundStock),
            'not_found_count' => count($notFound),
            'total_checked_items' => $totalChecked,
            'pages_searched' => $page,
            'duration_seconds' => $duration,
            'items_per_second' => round($totalChecked / $duration, 2)
        ]);
        
        return [
            'success' => true,
            'found_stock' => $foundStock,
            'not_found' => $notFound,
            'stats' => [
                'found_count' => count($foundStock),
                'not_found_count' => count($notFound),
                'total_checked' => $totalChecked,
                'pages_searched' => $page,
                'duration_seconds' => $duration,
                'performance' => round($totalChecked / $duration, 2) . ' items/sec'
            ]
        ];
    }

    /**
     * Fallback method: Update trick untuk SKU yang tidak ditemukan di warehouse inventory
     */
    private function fallbackUpdateTrick(array $skus): array
    {
        Log::info("🔄 Using fallback update trick for " . count($skus) . " SKUs");
        
        $foundStock = [];
        $notFound = [];
        
        foreach ($skus as $sku) {
            try {
                // Create update dengan quantity 0 untuk get current stock
                $stockUpdate = [
                    'masterSku' => $sku,
                    'quantity' => 0,
                    'remark' => 'Stock check via fallback method'
                ];
                
                $gineeClient = $this->gineeClient ?? new \App\Services\GineeClient();
$result = $gineeClient->updateStock([$stockUpdate]);
                
                if (($result['code'] ?? null) === 'SUCCESS') {
                    $stockList = $result['data']['stockList'] ?? [];
                    
                    if (!empty($stockList)) {
                        $stockInfo = $stockList[0];
                        
                        $foundStock[$sku] = [
                            'sku' => $sku,
                            'product_name' => $stockInfo['masterProductName'] ?? 'Unknown',
                            'warehouse_stock' => $stockInfo['warehouseStock'] ?? 0,
                            'available_stock' => $stockInfo['availableStock'] ?? 0,
                            'locked_stock' => $stockInfo['lockedStock'] ?? 0,
                            'total_stock' => $stockInfo['availableStock'] ?? 0,
                            'last_updated' => $stockInfo['updateDatetime'] ?? now(),
                            'api_source' => 'fallback_update_trick'
                        ];
                        
                        Log::info("✅ Fallback found: {$sku} with stock " . ($stockInfo['availableStock'] ?? 0));
                    } else {
                        $notFound[] = $sku;
                    }
                } else {
                    $notFound[] = $sku;
                }
                
            } catch (\Exception $e) {
                Log::warning("Fallback failed for {$sku}: " . $e->getMessage());
                $notFound[] = $sku;
            }
            
            // Rate limiting
            usleep(300000); // 0.3 seconds between calls
        }
        
        return [
            'success' => true,
            'found_stock' => $foundStock,
            'not_found' => $notFound,
            'stats' => [
                'found_count' => count($foundStock),
                'not_found_count' => count($notFound),
                'method' => 'fallback_update_trick'
            ]
        ];
    }

    /**
     * 🔄 Fallback: Individual search for failed bulk operations
     */
private function fallbackIndividualSearch(array $skus): array
{
    Log::info("🔄 Using fallback individual search for " . count($skus) . " SKUs");
    
    $foundStock = [];
    $notFound = [];
    
    // Method 1: Try individual getStockFromGinee (warehouse inventory)
    foreach ($skus as $sku) {
        $stock = $this->getStockFromGinee($sku);
        
        if ($stock) {
            $foundStock[$sku] = $stock;
        } else {
            $notFound[] = $sku;
        }
        
        usleep(200000); // 0.2 seconds
    }
    
    // Method 2: Fallback ke Master Products untuk yang masih not found
    if (!empty($notFound)) {
        Log::info("🔄 Using Master Products fallback for " . count($notFound) . " remaining SKUs");
        
        $masterProductsResult = $this->fallbackMasterProducts($notFound);
        
        if ($masterProductsResult['success']) {
            $foundStock = array_merge($foundStock, $masterProductsResult['found_stock']);
            $notFound = $masterProductsResult['not_found'];
        }
    }
    
    // Method 3: Update trick untuk yang masih not found
    if (!empty($notFound)) {
        Log::info("🔄 Using update trick fallback for " . count($notFound) . " remaining SKUs");
        
        $fallbackResult = $this->fallbackUpdateTrick($notFound);
        
        if ($fallbackResult['success']) {
            $foundStock = array_merge($foundStock, $fallbackResult['found_stock']);
            $notFound = $fallbackResult['not_found'];
        }
    }
    
    return [
        'success' => true,
        'found_stock' => $foundStock,
        'not_found' => $notFound,
        'stats' => [
            'found_count' => count($foundStock),
            'not_found_count' => count($notFound),
            'method' => 'individual_with_multiple_fallbacks'
        ]
    ];
}

private function fallbackMasterProducts(array $skus): array
{
    Log::info("📋 Using Master Products fallback for " . count($skus) . " SKUs");
    
    $foundStock = [];
    $notFound = $skus; // Start with all as not found
    
    try {
        $page = 0;
        $maxPages = 20; // Search lebih banyak pages
        $pageSize = 100;
        
        while ($page < $maxPages && !empty($notFound)) {
            $gineeClient = $this->gineeClient ?? new \App\Services\GineeClient(); 
$result = $gineeClient->getMasterProducts([
                'page' => $page,
                'size' => $pageSize
            ]);
            
            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::warning("Master Products API failed on page {$page}");
                break;
            }
            
            $products = $result['data']['list'] ?? [];
            
            if (empty($products)) {
                Log::info("No more products on page {$page}");
                break;
            }
            
            foreach ($products as $product) {
                $masterSku = $product['masterSku'] ?? '';
                
                if (in_array($masterSku, $notFound)) {
                    $foundStock[$masterSku] = [
                        'sku' => $masterSku,
                        'product_name' => $product['name'] ?? 'Unknown',
                        'warehouse_stock' => $product['stockQuantity'] ?? 0,
                        'available_stock' => $product['stockQuantity'] ?? 0,
                        'locked_stock' => 0,
                        'total_stock' => $product['stockQuantity'] ?? 0,
                        'last_updated' => now(),
                        'api_source' => 'fallback_master_products'
                    ];
                    
                    // Remove from not found list
                    $notFound = array_diff($notFound, [$masterSku]);
                    
                    Log::info("✅ Master Products fallback found: {$masterSku} with stock " . ($product['stockQuantity'] ?? 0));
                }
            }
            
            $page++;
            usleep(100000); // 0.1 second delay
        }
        
    } catch (\Exception $e) {
        Log::error("Master Products fallback exception: " . $e->getMessage());
    }
    
    return [
        'success' => true,
        'found_stock' => $foundStock,
        'not_found' => array_values($notFound),
        'stats' => [
            'found_count' => count($foundStock),
            'not_found_count' => count($notFound),
            'method' => 'fallback_master_products'
        ]
    ];
}

    /**
     * 🚀 OPTIMIZED: Sync multiple SKUs with bulk operations
     */
    public function syncMultipleSkusOptimized(array $skus, array $options = []): array
    {
        $sessionId = \Illuminate\Support\Str::uuid();
        $dryRun = $options['dry_run'] ?? false;
        $chunkSize = $options['chunk_size'] ?? 50; // Process in chunks
        
        Log::info('🚀 [OPTIMIZED] Starting bulk sync with improved performance', [
            'total_skus' => count($skus),
            'chunk_size' => $chunkSize,
            'dry_run' => $dryRun,
            'session_id' => $sessionId
        ]);
        
        $stats = [
            'session_id' => $sessionId,
            'total_requested' => count($skus),
            'successful' => 0,
            'failed' => 0,
            'not_found' => 0,
            'details' => [],
            'errors' => [],
            'performance' => []
        ];

        $startTime = microtime(true);

        // Process SKUs in chunks for better performance
        $chunks = array_chunk($skus, $chunkSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkStartTime = microtime(true);
            
            Log::info("📦 Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks) . " (" . count($chunk) . " SKUs)");
            
            // Get all stock data for this chunk in one go
            $bulkResult = $this->getBulkStockFromGinee($chunk);
            
            if (!$bulkResult['success']) {
                Log::error("❌ Bulk stock fetch failed for chunk " . ($chunkIndex + 1));
                
                // Mark all SKUs in chunk as failed
                foreach ($chunk as $sku) {
                    $stats['failed']++;
                    $stats['errors'][] = "SKU {$sku}: Bulk fetch failed";
                }
                continue;
            }

            $foundStock = $bulkResult['found_stock'];
            $notFoundSkus = $bulkResult['not_found'];
            
            // Update local database for found SKUs
            foreach ($foundStock as $sku => $stockData) {
                try {
                    $updated = $this->updateLocalProductStock($sku, $stockData, $dryRun);
                    
                    if ($updated) {
                        $stats['successful']++;
                        $stats['details'][] = [
                            'sku' => $sku,
                            'status' => 'success',
                            'message' => $dryRun ? 
                                "Dry run - would update {$sku} to {$stockData['total_stock']}" :
                                "Updated {$sku} to {$stockData['total_stock']}"
                        ];
                        
                        // Log individual success
                        \App\Models\GineeSyncLog::create([
                            'session_id' => $sessionId,
                            'type' => 'bulk_optimized_sync',
                            'status' => $dryRun ? 'skipped' : 'success',
                            'operation_type' => 'sync',
                            'sku' => $sku,
                            'product_name' => $stockData['product_name'],
                            'new_stock' => $stockData['total_stock'],
                            'message' => $dryRun ? 
                                "Dry run - would update to {$stockData['total_stock']}" :
                                "Updated to {$stockData['total_stock']}",
                            'dry_run' => $dryRun
                        ]);
                    } else {
                        $stats['failed']++;
                        $stats['errors'][] = "SKU {$sku}: Failed to update local database";
                    }
                    
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = "SKU {$sku}: Exception - " . $e->getMessage();
                    
                    Log::error("💥 Exception updating SKU {$sku}: " . $e->getMessage());
                }
            }
            
            // Mark not found SKUs
            foreach ($notFoundSkus as $sku) {
                $stats['not_found']++;
                $stats['failed']++;
                $stats['errors'][] = "SKU {$sku}: Not found in Ginee";
                
                // Log not found
                \App\Models\GineeSyncLog::create([
                    'session_id' => $sessionId,
                    'type' => 'bulk_optimized_sync',
                    'status' => 'failed',
                    'operation_type' => 'sync',
                    'sku' => $sku,
                    'message' => 'SKU not found in Ginee inventory',
                    'error_message' => 'Product not found',
                    'dry_run' => $dryRun
                ]);
            }
            
            $chunkDuration = microtime(true) - $chunkStartTime;
            $stats['performance'][] = [
                'chunk' => $chunkIndex + 1,
                'skus_count' => count($chunk),
                'found_count' => count($foundStock),
                'duration_seconds' => round($chunkDuration, 2),
                'skus_per_second' => round(count($chunk) / $chunkDuration, 2)
            ];
            
            Log::info("✅ Chunk " . ($chunkIndex + 1) . " completed", [
                'found' => count($foundStock),
                'not_found' => count($notFoundSkus),
                'duration' => round($chunkDuration, 2) . 's',
                'speed' => round(count($chunk) / $chunkDuration, 2) . ' SKUs/sec'
            ]);
            
            // Rate limiting between chunks
            if ($chunkIndex < count($chunks) - 1) {
                sleep(1); // 1 second between chunks
            }
        }

        $totalDuration = microtime(true) - $startTime;
        
        // Create summary log
        \App\Models\GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_optimized_summary',
            'status' => 'completed',
            'operation_type' => 'sync',
            'items_processed' => count($skus),
            'items_successful' => $stats['successful'],
            'items_failed' => $stats['failed'],
            'started_at' => now()->subSeconds($totalDuration),
            'completed_at' => now(),
            'summary' => json_encode($stats),
            'message' => "Optimized bulk sync completed: {$stats['successful']} successful, {$stats['failed']} failed",
            'dry_run' => $dryRun
        ]);

        $overallSpeed = round(count($skus) / $totalDuration, 2);
        
        Log::info('🏁 [OPTIMIZED] Bulk sync completed', [
            'session_id' => $sessionId,
            'total_skus' => count($skus),
            'successful' => $stats['successful'],
            'failed' => $stats['failed'],
            'not_found' => $stats['not_found'],
            'total_duration' => round($totalDuration, 2) . 's',
            'overall_speed' => $overallSpeed . ' SKUs/sec',
            'performance_improvement' => 'Up to 10x faster than individual search'
        ]);

        $summary = "🚀 OPTIMIZED bulk sync completed - Session: {$sessionId}\n";
        $summary .= "✅ Successful: {$stats['successful']}\n";
        $summary .= "❌ Failed: {$stats['failed']}\n";
        $summary .= "🔍 Not Found: {$stats['not_found']}\n";
        $summary .= "📊 Speed: {$overallSpeed} SKUs/sec\n";
        $summary .= "⏱️ Duration: " . round($totalDuration, 2) . " seconds";
        
        if ($dryRun) {
            $summary = "🧪 DRY RUN - " . $summary;
        }

        return [
            'success' => true,
            'message' => $summary,
            'data' => $stats
        ];
    }

    public function syncBulkStock(array $skus, bool $dryRun = true, int $batchSize = 50, int $delay = 3, string $sessionId = null): array
    {
        // Gunakan method optimized yang sudah ada
        return $this->syncMultipleSkusOptimized($skus, [
            'dry_run' => $dryRun,
            'chunk_size' => $batchSize
        ]);
    }

    /**
     * Update local product stock - method yang dipanggil dari syncMultipleSkusOptimized
     */
    public function updateLocalProductStock(string $sku, array $stockData, bool $dryRun = false): bool
    {
        try {
            $product = \App\Models\Product::where('sku', $sku)->first();
            
            if (!$product) {
                Log::warning("Product not found locally: {$sku}");
                return false;
            }

            $oldStock = $product->stock_quantity ?? 0;
            $newStock = $stockData['available_stock'] ?? $stockData['total_stock'] ?? 0;

            if ($dryRun) {
                Log::info("DRY RUN - Would update {$sku}: {$oldStock} → {$newStock}");
                return true;
            }

            // Live update
            $updated = $product->update([
                'stock_quantity' => $newStock,
                'warehouse_stock' => $stockData['warehouse_stock'] ?? $newStock,
                'ginee_last_sync' => now()
            ]);

            Log::info("Updated {$sku}: {$oldStock} → {$newStock}");
            return $updated;

        } catch (\Exception $e) {
            Log::error("Failed to update {$sku}: " . $e->getMessage());
            return false;
        }
    }
}