<?php

namespace App\Services;

use App\Models\Product;
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
     * Get stock untuk specific SKU dari Ginee - HANYA BACA (AMAN)
     */
    public function getStockFromGinee(string $sku): ?array
    {
        try {
            Log::info('🔍 [Ginee Stock] Getting stock (READ ONLY)', ['sku' => $sku]);
            
            // HANYA gunakan Master Products API - TIDAK MENGUBAH STOCK
            $result = $this->gineeClient->getMasterProducts([
                'page' => 0,
                'size' => 10,
                'sku' => $sku
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::warning('❌ [Ginee Stock Sync] Failed to search SKU', [
                    'sku' => $sku,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
                return null;
            }

            $items = $result['data']['content'] ?? [];
            
            foreach ($items as $item) {
                $variations = $item['variationBriefs'] ?? [];
                
                foreach ($variations as $variation) {
                    $varSku = $variation['sku'] ?? null;
                    
                    if ($varSku === $sku) {
                        $stock = $variation['stock'] ?? [];
                        
                        return [
                            'sku' => $sku,
                            'product_name' => $item['name'] ?? null,
                            'product_id' => $item['productId'] ?? null,
                            'variation_id' => $variation['id'] ?? null,
                            'warehouse_stock' => $stock['warehouseStock'] ?? 0,
                            'available_stock' => $stock['availableStock'] ?? 0,
                            'spare_stock' => $stock['spareStock'] ?? 0,
                            'locked_stock' => ($stock['warehouseStock'] ?? 0) - ($stock['availableStock'] ?? 0),
                            'safety_stock' => $stock['safetyStock'] ?? 0,
                            'bound_shops' => $variation['boundShopCount'] ?? 0,
                            'product_status' => $item['masterProductStatus'] ?? null,
                            'method' => 'master_products_safe'
                        ];
                    }
                }
            }

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
     * Sync stock untuk single SKU - AMAN
     */
    public function syncSingleSku(string $sku, bool $dryRun = false): array
    {
        Log::info('🎯 [Ginee Stock Sync] Syncing single SKU (SAFE MODE)', ['sku' => $sku]);
        
        $gineeStock = $this->getStockFromGinee($sku);
        
        if (!$gineeStock) {
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
                return false;
            }

            $oldStock = $product->stock_quantity ?? 0;
            $newStock = $gineeStockData['available_stock'] ?? 0;
            
            if ($dryRun) {
                Log::info('🧪 [Ginee Stock Sync] DRY RUN - Would update stock', [
                    'sku' => $sku,
                    'product_id' => $product->id,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'ginee_data' => $gineeStockData
                ]);
                return true;
            }

            $product->stock_quantity = $newStock;
            
            if (isset($gineeStockData['warehouse_stock'])) {
                $product->warehouse_stock = $gineeStockData['warehouse_stock'];
            }
            
            $product->ginee_last_stock_sync = now();
            $product->save();

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