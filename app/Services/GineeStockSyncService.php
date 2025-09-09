<?php

namespace App\Services;

use App\Models\Product;
use App\Models\GineeSyncLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GineeStockSyncService
{
    private GineeClient $gineeClient;
    
    public function __construct()
    {
        $this->gineeClient = new GineeClient();
    }

    /**
     * Get stock untuk specific SKU dari Ginee - MENGGUNAKAN STOCK UPDATE API
     * Ini method paling akurat karena sesuai dengan dashboard Ginee
     */
public function getStockFromGinee(string $sku): ?array
{
    try {
        $sku = strtoupper(trim($sku)); // Normalize search term
        Log::info("🔍 Starting comprehensive search for SKU: {$sku}");
        
        $page = 0;
        $pageSize = 100;
        $totalChecked = 0;
        $totalVariations = 0;
        $foundSkus = []; // Track all SKUs we find
        
        while (true) {
            Log::info("📄 Fetching page {$page} (batch size: {$pageSize})...");
            
            $result = $this->gineeClient->getMasterProducts([
                'page' => $page,
                'size' => $pageSize
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::error("❌ API failed on page {$page}: " . ($result['message'] ?? 'Unknown'));
                break;
            }

            $items = $result['data']['content'] ?? [];
            $itemCount = count($items);
            
            if ($itemCount === 0) {
                Log::info("📋 No more items on page {$page}, search complete");
                break;
            }
            
            $totalChecked += $itemCount;
            
            // Process each product in this page
            foreach ($items as $itemIndex => $item) {
                $variations = $item['variationBriefs'] ?? [];
                $variationCount = count($variations);
                $totalVariations += $variationCount;
                
                Log::info("🔍 Page {$page}, Product " . ($itemIndex + 1) . "/{$itemCount}: '{$item['name']}' ({$variationCount} variations)");
                
                foreach ($variations as $varIndex => $variation) {
                    $variationSku = strtoupper(trim($variation['sku'] ?? ''));
                    $foundSkus[] = $variationSku; // Track all SKUs
                    
                    Log::info("   Variation " . ($varIndex + 1) . "/{$variationCount}: SKU '{$variationSku}'");
                    
                    if ($variationSku === $sku) {
                        $stock = $variation['stock'] ?? [];
                        
                        Log::info("🎯 MATCH FOUND! SKU '{$sku}' found on page {$page}");
                        Log::info("📊 Final stats: Checked {$totalChecked} products, {$totalVariations} variations total");
                        
                        return [
                            'sku' => $sku,
                            'product_name' => $item['name'] ?? 'Unknown',
                            'warehouse_stock' => $stock['warehouseStock'] ?? 0,
                            'available_stock' => $stock['availableStock'] ?? 0,
                            'spare_stock' => $stock['spareStock'] ?? 0,
                            'locked_stock' => $stock['lockedStock'] ?? 0,
                            'product_status' => $item['status'] ?? 'unknown',
                            'found_on_page' => $page,
                            'total_products_checked' => $totalChecked,
                            'total_variations_checked' => $totalVariations,
                            'method' => 'comprehensive_scan'
                        ];
                    }
                }
            }
            
            Log::info("📊 Page {$page} complete: {$itemCount} products, running total: {$totalChecked} products, {$totalVariations} variations");
            $page++;
            
            // Safety break - prevent infinite loops
            if ($page > 50) {
                Log::warning("⚠️ Reached safety limit of 50 pages");
                break;
            }
        }

        Log::warning("❌ SKU '{$sku}' NOT FOUND");
        Log::info("📊 Search complete: {$totalChecked} products, {$totalVariations} variations checked across {$page} pages");
        
        // Log first 20 SKUs found for debugging
        Log::info("🔍 Sample SKUs found: " . implode(', ', array_slice($foundSkus, 0, 20)));
        
        return null;
        
    } catch (\Exception $e) {
        Log::error("💥 Exception during comprehensive search: " . $e->getMessage());
        return null;
    }
}
    /**
     * Sync stock untuk single SKU - AMAN (Ginee → Local)
     */
    public function syncSingleSku(string $sku, bool $dryRun = false): array
    {
        Log::info('🎯 [Ginee Stock Sync] Syncing single SKU (ACCURATE MODE)', ['sku' => $sku]);
        
        $gineeStock = $this->getStockFromGinee($sku);
        
        if (!$gineeStock) {
            // Log failure
            GineeSyncLog::create([
                'type' => 'stock_push',
                'status' => 'failed',
                'operation_type' => 'sync',
                'sku' => $sku,
                'product_name' => 'Unknown',
                'message' => "SKU {$sku} not found in Ginee",
                'dry_run' => $dryRun,
                'session_id' => GineeSyncLog::generateSessionId()
            ]);
            
            return [
                'success' => false,
                'message' => "SKU {$sku} not found in Ginee",
                'data' => null
            ];
        }

        $updated = $this->updateLocalProductStock($sku, $gineeStock, $dryRun);
        
        if ($updated) {
            return [
                'success' => true,
                'message' => $dryRun ? "DRY RUN: Would update {$sku}" : "Successfully updated {$sku}",
                'data' => [
                    'sku' => $sku,
                    'ginee_stock' => $gineeStock,
                    'dry_run' => $dryRun
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to update local product for SKU {$sku}",
                'data' => ['ginee_stock' => $gineeStock]
            ];
        }
    }

    /**
     * Update stock produk lokal berdasarkan data dari Ginee
     */
    public function updateLocalProductStock(string $sku, array $gineeStockData, bool $dryRun = false): bool
    {
        try {
            $product = Product::where('sku', $sku)->first();
            
            if (!$product) {
                Log::warning('⚠️ [Ginee Stock Sync] Product not found locally', ['sku' => $sku]);
                
                // Log not found
                GineeSyncLog::create([
                    'type' => 'stock_push',
                    'status' => 'failed',
                    'operation_type' => 'sync',
                    'sku' => $sku,
                    'product_name' => $gineeStockData['product_name'] ?? 'Unknown',
                    'message' => "Product not found in local database",
                    'dry_run' => $dryRun,
                    'session_id' => GineeSyncLog::generateSessionId()
                ]);
                
                return false;
            }

            $oldStock = $product->stock_quantity ?? 0;
            
            // GUNAKAN AVAILABLE STOCK (yang ditampilkan di dashboard)
            $newStock = $gineeStockData['available_stock'] ?? 0;
            
            if ($dryRun) {
                Log::info('🧪 [Ginee Stock Sync] DRY RUN - Would update stock', [
                    'sku' => $sku,
                    'product_id' => $product->id,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'ginee_data' => $gineeStockData
                ]);
                
                // Log dry run
                GineeSyncLog::create([
                    'type' => 'stock_push',
                    'status' => 'skipped',
                    'operation_type' => 'sync',
                    'sku' => $sku,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'message' => "Dry run - would update from {$oldStock} to {$newStock}",
                    'dry_run' => true,
                    'session_id' => GineeSyncLog::generateSessionId()
                ]);
                
                return true;
            }

            // UPDATE STOCK
            $product->stock_quantity = $newStock;
            
            if (isset($gineeStockData['warehouse_stock'])) {
                $product->warehouse_stock = $gineeStockData['warehouse_stock'];
            }
            
            $product->ginee_last_sync = now();
            $product->ginee_sync_status = 'synced';
            $product->save();

            // Log success
            GineeSyncLog::create([
                'type' => 'stock_push',
                'status' => 'success',
                'operation_type' => 'sync',
                'sku' => $sku,
                'product_name' => $product->name,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'message' => "Successfully updated from {$oldStock} to {$newStock}",
                'ginee_response' => $gineeStockData,
                'dry_run' => false,
                'session_id' => GineeSyncLog::generateSessionId()
            ]);

            Log::info('✅ [Ginee Stock Sync] Updated product stock', [
                'sku' => $sku,
                'product_id' => $product->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $newStock - $oldStock
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ [Ginee Stock Sync] Failed to update local product', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            
            // Log error
            GineeSyncLog::create([
                'type' => 'stock_push',
                'status' => 'failed',
                'operation_type' => 'sync',
                'sku' => $sku,
                'product_name' => $gineeStockData['product_name'] ?? 'Unknown',
                'message' => "Exception: " . $e->getMessage(),
                'dry_run' => $dryRun,
                'session_id' => GineeSyncLog::generateSessionId()
            ]);
            
            return false;
        }
    }

    /**
     * Push single SKU to Ginee with force parameter
     */
    public function pushSingleSkuToGinee(string $sku, bool $dryRun = false, bool $forceUpdate = false)
    {
        try {
            Log::info("🎯 Starting individual push for SKU: {$sku}", [
                'dry_run' => $dryRun,
                'force_update' => $forceUpdate
            ]);

            // Validate product exists
            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                return [
                    'success' => false,
                    'message' => "Product with SKU {$sku} not found in database"
                ];
            }

            // Check for Ginee mapping
            $gineeMapping = $product->gineeMappings()->first();
            if (!$gineeMapping) {
                return [
                    'success' => false,
                    'message' => "Product {$sku} has no Ginee mapping configured"
                ];
            }

            // Check if sync is enabled
            if (!$gineeMapping->sync_enabled || !$gineeMapping->stock_sync_enabled) {
                return [
                    'success' => false,
                    'message' => "Sync is disabled for product {$sku}"
                ];
            }

            // Force update logic
            if (!$forceUpdate) {
                // Check if already in sync
                if ($gineeMapping->stock_quantity_ginee == $product->stock_quantity) {
                    if ($gineeMapping->last_stock_sync && $gineeMapping->last_stock_sync > now()->subHours(1)) {
                        return [
                            'success' => false,
                            'message' => "Stock already in sync (Local: {$product->stock_quantity}, Ginee: {$gineeMapping->stock_quantity_ginee})"
                        ];
                    }
                }
            }

            // Prepare stock update for Ginee
            $stockUpdate = [
                'masterSku' => $gineeMapping->ginee_master_sku,
                'warehouseId' => $gineeMapping->ginee_warehouse_id ?? config('services.ginee.default_warehouse_id'),
                'quantity' => $product->stock_quantity ?? 0,
                'notes' => "Updated from SneakerFlash at " . now()->format('Y-m-d H:i:s')
            ];

            if ($dryRun) {
                // Dry run - just log what would happen
                Log::info("🧪 DRY RUN - Would push to Ginee", [
                    'sku' => $sku,
                    'stock_update' => $stockUpdate
                ]);

                return [
                    'success' => true,
                    'message' => "DRY RUN - Would push stock {$product->stock_quantity} to Ginee for {$sku}",
                    'data' => [
                        'local_stock' => $product->stock_quantity,
                        'ginee_msku' => $gineeMapping->ginee_master_sku,
                        'warehouse_id' => $stockUpdate['warehouseId'],
                        'dry_run' => true
                    ]
                ];
            }

            // Actual push to Ginee
            $ginee = new \App\Services\GineeClient();
            $result = $ginee->adjustInventory($stockUpdate['warehouseId'], [$stockUpdate]);

            if (($result['code'] ?? null) === 'SUCCESS') {
                // Update mapping with new sync info
                $gineeMapping->update([
                    'stock_quantity_ginee' => $product->stock_quantity,
                    'last_stock_sync' => now(),
                ]);

                // Update product's last sync timestamp if column exists
                if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'ginee_last_sync')) {
                    $product->update(['ginee_last_sync' => now()]);
                }

                Log::info("✅ Successfully pushed stock to Ginee", [
                    'sku' => $sku,
                    'stock' => $product->stock_quantity,
                    'ginee_response' => $result
                ]);

                return [
                    'success' => true,
                    'message' => "Successfully pushed stock {$product->stock_quantity} to Ginee for {$sku}",
                    'data' => [
                        'local_stock' => $product->stock_quantity,
                        'ginee_response' => $result,
                        'ginee_msku' => $gineeMapping->ginee_master_sku,
                        'transaction_id' => $result['transactionId'] ?? null,
                        'updated_at' => now()
                    ]
                ];

            } else {
                $errorMessage = $result['message'] ?? 'Unknown Ginee API error';
                
                Log::error("❌ Failed to push stock to Ginee", [
                    'sku' => $sku,
                    'error' => $errorMessage,
                    'ginee_response' => $result
                ]);

                return [
                    'success' => false,
                    'message' => "Failed to push to Ginee: {$errorMessage}",
                    'data' => [
                        'ginee_response' => $result,
                        'error_code' => $result['code'] ?? null
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error("❌ Exception in pushSingleSkuToGinee", [
                'sku' => $sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => "Exception: {$e->getMessage()}"
            ];
        }
    }

    /**
     * Sync multiple SKUs individually
     */
public function syncMultipleSkusIndividually(array $skus, array $options = [])
{
    // SAFETY: Disable bulk operations until fix is verified
    Log::warning('🚫 Bulk sync temporarily disabled for safety');
    
    return [
        'success' => false,
        'data' => [
            'session_id' => \Illuminate\Support\Str::uuid(),
            'total_requested' => count($skus),
            'successful' => 0,
            'failed' => count($skus),
            'not_found' => 0,
            'no_mapping' => 0,
            'details' => [],
            'errors' => ['Bulk sync temporarily disabled for safety - use individual sync only'],
            'processed_skus' => []
        ]
    ];
}

public function pushMultipleSkusIndividually(array $skus, array $options = [])
{
    // SAFETY: Disable bulk operations until fix is verified
    Log::warning('🚫 Bulk push temporarily disabled for safety');
    
    return [
        'success' => false,
        'data' => [
            'session_id' => \Illuminate\Support\Str::uuid(),
            'total_requested' => count($skus),
            'successful' => 0,
            'failed' => count($skus),
            'skipped' => 0,
            'not_found' => 0,
            'no_mapping' => 0,
            'details' => [],
            'errors' => ['Bulk push temporarily disabled for safety - use individual actions only'],
            'processed_skus' => []
        ]
    ];
}

public function bidirectionalSyncMultipleSkus(array $skus, array $options = [])
{
    // SAFETY: Disable bulk operations until fix is verified
    Log::warning('🚫 Bidirectional sync temporarily disabled for safety');
    
    return [
        'success' => false,
        'data' => [
            'session_id' => \Illuminate\Support\Str::uuid(),
            'sync_phase' => ['successful' => 0, 'failed' => count($skus)],
            'push_phase' => ['successful' => 0, 'failed' => count($skus), 'skipped' => 0],
            'summary' => [
                'total_requested' => count($skus),
                'sync_successful' => 0,
                'sync_failed' => count($skus),
                'push_successful' => 0,
                'push_failed' => count($skus),
                'push_skipped' => 0,
            ]
        ]
    ];
}

    /**
     * Get sync statistics for dashboard
     */
    public function getSyncStatistics(array $skus = null): array
    {
        $query = Product::query();
        
        if ($skus) {
            $query->whereIn('sku', $skus);
        }
        
        $products = $query->with('gineeMappings')->get();
        
        return [
            'total_products' => $products->count(),
            'mapped_products' => $products->filter(fn($p) => $p->gineeMappings && $p->gineeMappings->isNotEmpty())->count(),
            'sync_enabled' => $products->filter(fn($p) => $p->gineeMappings && $p->gineeMappings->where('sync_enabled', true)->isNotEmpty())->count(),
            'never_synced' => $products->filter(fn($p) => !$p->ginee_last_sync)->count(),
            'synced_last_24h' => $products->filter(fn($p) => $p->ginee_last_sync && $p->ginee_last_sync > now()->subDay())->count(),
            'stale_sync' => $products->filter(fn($p) => $p->ginee_last_sync && $p->ginee_last_sync < now()->subDay())->count(),
        ];
    }

    /**
     * Categorize error messages for better reporting
     */
    private function categorizeError(string $message): string
    {
        if (str_contains($message, 'not found in database')) {
            return 'product_not_found';
        } elseif (str_contains($message, 'no mapping') || str_contains($message, 'not mapped')) {
            return 'no_ginee_mapping';
        } elseif (str_contains($message, 'API error') || str_contains($message, 'HTTP')) {
            return 'api_error';
        } elseif (str_contains($message, 'already in sync')) {
            return 'already_synced';
        } elseif (str_contains($message, 'rate limit')) {
            return 'rate_limited';
        } elseif (str_contains($message, 'timeout')) {
            return 'timeout';
        } else {
            return 'unknown';
        }
    }

    // Bulk operations - simplified for safety
    public function syncStockFromGinee(array $options = []): array
    {
        return [
            'success' => true,
            'message' => 'Use single SKU sync for safety',
            'data' => []
        ];
    }

    public function pushStockToGinee(array $options = []): array
    {
        return [
            'success' => true,
            'message' => 'Use single SKU push for safety',
            'data' => []
        ];
    }
}

?>