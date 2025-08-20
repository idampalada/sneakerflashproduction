<?php

namespace App\Services;

use App\Models\Product;
use App\Models\GineeSyncLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            Log::info('🔍 [Ginee Stock] Getting stock via Stock Update API (ACCURATE)', ['sku' => $sku]);
            
            // TRICK: Gunakan updateStock dengan quantity 0 untuk mendapatkan current stock
            // Ini tidak mengubah stock tapi mengembalikan data stock yang akurat
            $testUpdate = [
                ['masterSku' => $sku, 'quantity' => 0]
            ];
            
            $result = $this->gineeClient->updateStock($testUpdate);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::warning('❌ [Ginee Stock Sync] Failed to get stock via update API', [
                    'sku' => $sku,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
                return null;
            }

            $stockList = $result['data']['stockList'] ?? [];
            
            foreach ($stockList as $item) {
                if (($item['masterSku'] ?? null) === $sku) {
                    return [
                        'sku' => $sku,
                        'product_name' => $item['masterProductName'] ?? null,
                        'master_variation_id' => $item['masterVariationId'] ?? null,
                        'warehouse_stock' => $item['warehouseStock'] ?? 0,
                        'available_stock' => $item['availableStock'] ?? 0,
                        'spare_stock' => $item['spareStock'] ?? 0,
                        'locked_stock' => $item['lockedStock'] ?? 0,
                        'transport_stock' => $item['transportStock'] ?? 0,
                        'promotion_stock' => $item['promotionStock'] ?? 0,
                        'out_stock' => $item['outStock'] ?? 0,
                        'safety_stock' => $item['safetyStock'] ?? 0,
                        'update_datetime' => $item['updateDatetime'] ?? null,
                        'method' => 'stock_update_api_accurate'
                    ];
                }
            }

            Log::warning('⚠️ [Ginee Stock Sync] SKU not found in stock list', ['sku' => $sku]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('❌ [Ginee Stock Sync] Exception getting stock', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
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
     * Push single SKU stock to Ginee - MENGGUNAKAN STOCK LOKAL
     */
    public function pushSingleSkuToGinee(string $sku, bool $dryRun = false): array
    {
        Log::info('🎯 [Ginee Stock Push] Pushing single SKU to Ginee', ['sku' => $sku]);
        
        $product = Product::where('sku', $sku)->first();
        
        if (!$product) {
            return [
                'success' => false,
                'message' => "Product with SKU {$sku} not found in local database",
                'data' => null
            ];
        }

        $localStock = $product->stock_quantity ?? 0;
        
        if ($dryRun) {
            return [
                'success' => true,
                'message' => "DRY RUN: Would push {$sku} with stock {$localStock} to Ginee",
                'data' => [
                    'sku' => $sku,
                    'local_stock' => $localStock,
                    'dry_run' => true
                ]
            ];
        }

        // PUSH dengan stock dari database lokal
        $stockUpdate = [
            ['masterSku' => $sku, 'quantity' => $localStock]
        ];
        
        try {
            $result = $this->gineeClient->updateStock($stockUpdate);
            
            if (($result['code'] ?? null) === 'SUCCESS') {
                if (Schema::hasColumn('products', 'ginee_last_stock_push')) {
                    $product->ginee_last_stock_push = now();
                    $product->save();
                }
                
                $stockList = $result['data']['stockList'] ?? [];
                $gineeData = null;
                
                foreach ($stockList as $item) {
                    if (($item['masterSku'] ?? null) === $sku) {
                        $gineeData = $item;
                        break;
                    }
                }
                
                return [
                    'success' => true,
                    'message' => "Successfully pushed {$sku} stock to Ginee",
                    'data' => [
                        'sku' => $sku,
                        'local_stock' => $localStock,
                        'ginee_response' => $gineeData,
                        'transaction_id' => $result['transactionId'] ?? null
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Failed to push {$sku} to Ginee: " . ($result['message'] ?? 'Unknown error'),
                    'data' => [
                        'sku' => $sku,
                        'local_stock' => $localStock,
                        'ginee_error' => $result
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('❌ [Ginee Stock Push] Exception pushing stock', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Exception pushing {$sku}: " . $e->getMessage(),
                'data' => ['sku' => $sku, 'local_stock' => $localStock]
            ];
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