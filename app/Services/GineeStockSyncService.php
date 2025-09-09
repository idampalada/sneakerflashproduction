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
        $sku = strtoupper(trim($sku));
        Log::info("🔍 [Stock Sync] Searching SKU using Warehouse Inventory API: {$sku}");
        
        $page = 0;
        $pageSize = 100;
        $totalChecked = 0;
        
        while (true) {
            Log::info("📄 Fetching warehouse inventory page {$page} (batch size: {$pageSize})...");
            
            $result = $this->gineeClient->getWarehouseInventory([
                'page' => $page,
                'size' => $pageSize
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::error("❌ Warehouse Inventory API failed on page {$page}: " . ($result['message'] ?? 'Unknown'));
                break;
            }

            $items = $result['data']['content'] ?? [];
            $itemCount = count($items);
            
            if ($itemCount === 0) {
                Log::info("📋 No more items on page {$page}, search complete");
                break;
            }
            
            $totalChecked += $itemCount;
            Log::info("🔍 Page {$page}: Checking {$itemCount} inventory items...");
            
            foreach ($items as $itemIndex => $item) {
                // ✅ STRUKTUR RESPONSE YANG BENAR (berdasarkan log):
                // $item['masterVariation']['masterSku']
                // $item['warehouseInventory']['availableStock'] ← INI YANG BENAR!
                
                $masterVariation = $item['masterVariation'] ?? [];
                $warehouseInventory = $item['warehouseInventory'] ?? [];
                $itemSku = strtoupper(trim($masterVariation['masterSku'] ?? ''));
                
                Log::debug("   Item " . ($itemIndex + 1) . "/{$itemCount}: SKU '{$itemSku}'");
                
                if ($itemSku === $sku) {
                    Log::info("🎯 MATCH FOUND! SKU: {$sku} in Warehouse Inventory", [
                        'raw_item_data' => $item,
                        'warehouse_inventory' => $warehouseInventory,
                        'master_variation' => $masterVariation
                    ]);
                    
                    // ✅ MAPPING FIELD YANG BENAR BERDASARKAN LOG RESPONSE
                    return [
                        'sku' => $itemSku,
                        'product_name' => $masterVariation['name'] ?? 'Unknown',
                        
                        // 🏭 Warehouse Stock (dari warehouseInventory object)
                        'warehouse_stock' => $warehouseInventory['warehouseStock'] ?? 0,
                        
                        // 🛒 Available Stock (INI YANG HARUS DIGUNAKAN!) 
                        'available_stock' => $warehouseInventory['availableStock'] ?? 0,
                        
                        // 🎯 Stock lainnya
                        'spare_stock' => $warehouseInventory['spareStock'] ?? 0,
                        'locked_stock' => $warehouseInventory['lockedStock'] ?? 0,
                        'transport_stock' => $warehouseInventory['transportStock'] ?? 0,
                        'promotion_stock' => $warehouseInventory['promotionStock'] ?? 0,
                        'safety_stock' => $warehouseInventory['safetyStock'] ?? 0,
                        'safety_alert' => $warehouseInventory['safetyAlert'] ?? false,
                        
                        // 🏪 Warehouse Info
                        'warehouse_id' => $warehouseInventory['warehouseId'] ?? 'Unknown',
                        
                        // 📊 Calculations
                        'total_physical_stock' => ($warehouseInventory['warehouseStock'] ?? 0) + 
                                                 ($warehouseInventory['transportStock'] ?? 0),
                        
                        'total_reserved_stock' => $warehouseInventory['lockedStock'] ?? 0,
                        'total_available_for_sale' => $warehouseInventory['availableStock'] ?? 0,
                        
                        // 🕒 Metadata
                        'last_updated' => $warehouseInventory['updateDatetime'] ?? now(),
                        'api_source' => 'warehouse_inventory',
                        'master_variation_id' => $masterVariation['id'] ?? 'Unknown',
                        'product_status' => 'ACTIVE'
                    ];
                }
            }
            
            $page++;
            
            if ($page > 50) {
                Log::warning("⚠️ Stopped search after 50 pages for safety");
                break;
            }
        }
        
        Log::warning("❌ SKU '{$sku}' NOT FOUND in Warehouse Inventory after searching {$totalChecked} items", [
            'total_pages_checked' => $page,
            'api_used' => 'warehouse_inventory'
        ]);
        
        return null;
        
    } catch (\Exception $e) {
        Log::error("💥 Exception during Warehouse Inventory search: " . $e->getMessage());
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
        $oldWarehouseStock = $product->warehouse_stock ?? 0;
        
        // ✅ GUNAKAN AVAILABLE STOCK YANG BENAR (1299 dari log)
        $newStock = $gineeStockData['available_stock'] ?? 0;
        $newWarehouseStock = $gineeStockData['warehouse_stock'] ?? 0;
        
        // ✅ DEBUG: Log values untuk memastikan mapping benar
        Log::info('🔍 [Debug] Stock values from Ginee data', [
            'sku' => $sku,
            'ginee_available_stock' => $gineeStockData['available_stock'] ?? 'NULL',
            'ginee_warehouse_stock' => $gineeStockData['warehouse_stock'] ?? 'NULL',
            'new_stock_to_set' => $newStock,
            'old_stock_current' => $oldStock,
            'raw_ginee_data_keys' => array_keys($gineeStockData)
        ]);
        
        if ($dryRun) {
            Log::info('🧪 [Ginee Stock Sync] DRY RUN - Would update stock', [
                'sku' => $sku,
                'product_id' => $product->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'old_warehouse_stock' => $oldWarehouseStock,
                'new_warehouse_stock' => $newWarehouseStock,
                'api_source' => 'warehouse_inventory',
                'stock_breakdown' => [
                    'available' => $gineeStockData['available_stock'] ?? 0,
                    'warehouse' => $gineeStockData['warehouse_stock'] ?? 0,
                    'spare' => $gineeStockData['spare_stock'] ?? 0,
                    'locked' => $gineeStockData['locked_stock'] ?? 0,
                ]
            ]);
            
            GineeSyncLog::create([
                'type' => 'stock_push',
                'status' => 'skipped',
                'operation_type' => 'sync',
                'sku' => $sku,
                'product_name' => $product->name,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'old_warehouse_stock' => $oldWarehouseStock,
                'new_warehouse_stock' => $newWarehouseStock,
                'message' => "Dry run - would update from {$oldStock} to {$newStock} (available_stock from warehouse_inventory)",
                'dry_run' => true,
                'ginee_response' => $gineeStockData,
                'session_id' => GineeSyncLog::generateSessionId()
            ]);
            
            return true;
        }

        // ✅ UPDATE STOCK dengan available_stock yang benar
        $product->stock_quantity = $newStock;
        $product->warehouse_stock = $newWarehouseStock;
        $product->ginee_last_sync = now();
        $product->ginee_sync_status = 'synced';
        $product->save();

        // Log success dengan detail lengkap
        GineeSyncLog::create([
            'type' => 'stock_push',
            'status' => 'success',
            'operation_type' => 'sync',
            'sku' => $sku,
            'product_name' => $product->name,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'old_warehouse_stock' => $oldWarehouseStock,
            'new_warehouse_stock' => $newWarehouseStock,
            'message' => "SUCCESS: Updated from {$oldStock} to {$newStock} (using availableStock: {$newStock} from warehouseInventory)",
            'ginee_response' => $gineeStockData,
            'dry_run' => false,
            'session_id' => GineeSyncLog::generateSessionId()
        ]);

        Log::info('✅ [Ginee Stock Sync] Updated product stock (Warehouse Inventory API)', [
            'sku' => $sku,
            'product_id' => $product->id,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'change' => $newStock - $oldStock,
            'api_source' => 'warehouse_inventory',
            'stock_breakdown' => [
                'available_used' => $newStock,
                'warehouse_reference' => $newWarehouseStock,
                'spare' => $gineeStockData['spare_stock'] ?? 0,
                'locked' => $gineeStockData['locked_stock'] ?? 0,
                'warehouse_id' => $gineeStockData['warehouse_id'] ?? 'Unknown'
            ]
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('❌ [Ginee Stock Sync] Failed to update local product', [
            'sku' => $sku,
            'error' => $e->getMessage()
        ]);
        
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
    // ✅ INCREASE EXECUTION TIME UNTUK BULK OPERATIONS
    set_time_limit(0); // Unlimited execution time
    ini_set('memory_limit', '1G'); // Increase memory limit
    
    Log::info('📥 Starting bulk sync FROM Ginee (READ-ONLY)', [
        'total_skus' => count($skus),
        'batch_size' => $options['batch_size'] ?? 20,
        'dry_run' => $options['dry_run'] ?? false,
        'execution_limit' => 'unlimited'
    ]);
    
    $sessionId = \Illuminate\Support\Str::uuid();
    $dryRun = $options['dry_run'] ?? false;
    $batchSize = min($options['batch_size'] ?? 20, 50); // Max 50 untuk keamanan
    
    $stats = [
        'session_id' => $sessionId,
        'total_requested' => count($skus),
        'successful' => 0,
        'failed' => 0,
        'not_found' => 0,
        'no_mapping' => 0,
        'details' => [],
        'errors' => [],
        'processed_skus' => []
    ];

    // Process dalam batch untuk menghindari timeout
    $chunks = array_chunk($skus, $batchSize);
    
    foreach ($chunks as $chunkIndex => $chunk) {
        Log::info("📦 Processing batch " . ($chunkIndex + 1) . "/" . count($chunks), [
            'chunk_size' => count($chunk),
            'session_id' => $sessionId
        ]);
        
        foreach ($chunk as $sku) {
            try {
                // HANYA SYNC FROM GINEE - READ ONLY
                $result = $this->syncSingleSku($sku, $dryRun);
                
                if ($result['success']) {
                    $stats['successful']++;
                    $stats['details'][] = [
                        'sku' => $sku,
                        'status' => 'success',
                        'message' => $result['message']
                    ];
                } else {
                    $stats['failed']++;
                    $errorCategory = $this->categorizeError($result['message']);
                    
                    switch ($errorCategory) {
                        case 'product_not_found':
                            $stats['not_found']++;
                            break;
                        case 'no_ginee_mapping':
                            $stats['no_mapping']++;
                            break;
                    }
                    
                    $stats['errors'][] = "SKU {$sku}: " . $result['message'];
                    $stats['details'][] = [
                        'sku' => $sku,
                        'status' => 'failed',
                        'message' => $result['message'],
                        'category' => $errorCategory
                    ];
                }
                
                $stats['processed_skus'][] = $sku;
                
                // Rate limiting - jeda kecil antar request
                usleep(100000); // 0.1 detik
                
            } catch (\Exception $e) {
                $stats['failed']++;
                $errorMessage = "Exception for SKU {$sku}: " . $e->getMessage();
                $stats['errors'][] = $errorMessage;
                $stats['details'][] = [
                    'sku' => $sku,
                    'status' => 'exception',
                    'message' => $errorMessage
                ];
                
                Log::error("❌ Exception in bulk sync for SKU: {$sku}", [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId
                ]);
            }
        }
        
        // Jeda antar batch
        if ($chunkIndex < count($chunks) - 1) {
            sleep(1); // 1 detik jeda antar batch
        }
    }

    // Log summary
    $summary = "📥 Bulk sync FROM Ginee completed - Session: {$sessionId}\n";
    $summary .= "✅ Successful: {$stats['successful']}\n";
    $summary .= "❌ Failed: {$stats['failed']}\n";
    $summary .= "🔍 Not Found: {$stats['not_found']}\n";
    $summary .= "🔗 No Mapping: {$stats['no_mapping']}\n";
    $summary .= "📊 Total: {$stats['total_requested']}";
    
    if ($dryRun) {
        $summary = "🧪 DRY RUN - " . $summary;
    }
    
    Log::info($summary, ['session_id' => $sessionId]);

    return [
        'success' => true,
        'message' => $summary,
        'data' => $stats
    ];
}

public function pushMultipleSkusIndividually(array $skus, array $options = [])
{
    // TETAP DISABLED UNTUK KEAMANAN - WRITING OPERATIONS
    Log::warning('🚫 Bulk PUSH still disabled for safety - use individual push only');
    
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
            'errors' => ['Bulk PUSH temporarily disabled for safety - use individual push only'],
            'processed_skus' => []
        ]
    ];
}

public function bidirectionalSyncMultipleSkus(array $skus, array $options = [])
{
    // TETAP DISABLED karena ada komponen PUSH/WRITING
    Log::warning('🚫 Bidirectional sync still disabled for safety - contains PUSH operations');
    
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
            ],
            'errors' => ['Bidirectional sync disabled for safety - contains PUSH operations']
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
    Log::info('📥 Starting bulk sync stock FROM Ginee (READ-ONLY)');
    
    $onlyMapped = $options['only_active'] ?? true;
    $batchSize = $options['batch_size'] ?? 100;
    $dryRun = $options['dry_run'] ?? false;
    
    // ✅ LOGIC SAMA SEPERTI BULK ACTION YANG BERHASIL
    // LANGSUNG AMBIL SEMUA PRODUCTS (tanpa cek mapping)
    $query = Product::whereNotNull('sku')->where('sku', '!=', '');
    
    // TIDAK PERLU CEK MAPPING - langsung ambil semua SKU
    // if ($onlyMapped) {
    //     $query->whereHas('gineeMappings', function($q) {
    //         $q->where('sync_enabled', true);
    //     });
    // }
    
    $products = $query->get();
    $skus = $products->pluck('sku')->filter()->toArray();
    
    if (empty($skus)) {
        return [
            'success' => false,
            'message' => 'No products found to sync',
            'data' => [
                'total_requested' => 0,
                'successful' => 0,
                'failed' => 0,
                'not_found' => 0,
                'no_mapping' => 0,
                'errors' => ['No products found'],
                'session_id' => \Illuminate\Support\Str::uuid()
            ]
        ];
    }
    
    Log::info("🎯 Found {count} products to sync from Ginee (WITHOUT mapping check)", [
        'count' => count($skus),
        'sample_skus' => array_slice($skus, 0, 5)
    ]);
    
    // ✅ GUNAKAN LOGIC YANG SAMA DENGAN BULK ACTION
    return $this->syncMultipleSkusIndividually($skus, [
        'dry_run' => $dryRun,
        'batch_size' => $batchSize,
        'total_products' => count($skus)
    ]);
}

public function pushStockToGinee(array $options = []): array
{
    Log::warning('🚫 Bulk push to Ginee still disabled for safety');
    
    return [
        'success' => false,
        'message' => 'Bulk push temporarily disabled for safety - use individual SKU push only',
        'data' => []
    ];
}
public function syncStockFromGineeAll(array $options = []): array
{
    Log::info('🌍 Starting sync ALL products FROM Ginee (READ-ONLY)');
    
    $onlyMapped = $options['only_mapped'] ?? true;
    $dryRun = $options['dry_run'] ?? false;
    
    // Get ALL products (tidak pakai limit)
    $query = Product::query();
    
    if ($onlyMapped) {
        $query->whereHas('gineeMappings', function($q) {
            $q->where('sync_enabled', true);
        });
    }
    
    $products = $query->get();
    $skus = $products->pluck('sku')->filter()->toArray();
    
    if (empty($skus)) {
        return [
            'success' => false,
            'message' => 'No products found to sync',
            'data' => [
                'total_requested' => 0,
                'successful' => 0,
                'failed' => 0,
                'not_found' => 0,
                'no_mapping' => 0,
                'errors' => ['No products found'],
                'session_id' => \Illuminate\Support\Str::uuid()
            ]
        ];
    }
    
    Log::info("🎯 Found {count} products to sync from Ginee", ['count' => count($skus)]);
    
    // Process dengan batch internal kecil (untuk menghindari timeout)
    // Tapi dari UI terlihat seperti sync all
    $internalBatchSize = 50; // Internal batch size untuk API calls
    
    return $this->syncMultipleSkusIndividually($skus, [
        'dry_run' => $dryRun,
        'batch_size' => $internalBatchSize,
        'total_products' => count($skus)
    ]);
}
}

?>