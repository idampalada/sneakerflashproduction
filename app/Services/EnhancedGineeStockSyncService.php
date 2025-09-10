<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EnhancedGineeStockSyncService extends GineeStockSyncService
{
    /**
     * Enhanced method with 3 fallback strategies
     * Method 1: Standard warehouse inventory search
     * Method 2: Master products search  
     * Method 3: Direct SKU update trick (NEW FALLBACK)
     */
    public function getStockFromGineeEnhanced(string $sku): ?array
    {
        $sku = strtoupper(trim($sku));
        
        Log::info("🔍 [Enhanced] Getting stock for SKU: {$sku}");
        
        // Method 1: Standard warehouse inventory search
        $result = $this->attemptMethod1_WarehouseInventory($sku);
        if ($result) {
            Log::info("✅ [Enhanced] Method 1 (Warehouse Inventory) SUCCESS for {$sku}");
            return $result;
        }
        
        // Method 2: Master products search
        $result = $this->attemptMethod2_MasterProducts($sku);
        if ($result) {
            Log::info("✅ [Enhanced] Method 2 (Master Products) SUCCESS for {$sku}");
            return $result;
        }
        
        // Method 3: Direct SKU update trick (NEW)
        $result = $this->attemptMethod3_UpdateTrick($sku);
        if ($result) {
            Log::info("✅ [Enhanced] Method 3 (Update Trick) SUCCESS for {$sku}");
            return $result;
        }
        
        Log::warning("❌ [Enhanced] ALL METHODS FAILED for SKU: {$sku}");
        return null;
    }
    
    /**
     * Method 1: Standard warehouse inventory search (existing)
     */
    private function attemptMethod1_WarehouseInventory(string $sku): ?array
    {
        Log::info("🔍 [Method 1] Searching warehouse inventory for: {$sku}");
        
        try {
            return $this->getStockFromGinee($sku); // Existing method
        } catch (\Exception $e) {
            Log::warning("❌ [Method 1] Failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Method 2: Master products search
     */
    private function attemptMethod2_MasterProducts(string $sku): ?array
    {
        Log::info("🔍 [Method 2] Searching master products for: {$sku}");
        
        try {
            $page = 0;
            $maxPages = 10;
            
            do {
                $result = $this->gineeClient->getMasterProducts([
                    'page' => $page,
                    'size' => 100
                ]);
                
                if (($result['code'] ?? null) !== 'SUCCESS') {
                    break;
                }
                
                $products = $result['data']['list'] ?? [];
                
                if (empty($products)) {
                    break;
                }
                
                foreach ($products as $product) {
                    if (($product['masterSku'] ?? '') === $sku) {
                        Log::info("🎯 [Method 2] Found SKU in master products: {$sku}");
                        
                        return [
                            'sku' => $sku,
                            'product_name' => $product['name'] ?? 'Unknown',
                            'warehouse_stock' => $product['stockQuantity'] ?? 0,
                            'available_stock' => $product['stockQuantity'] ?? 0,
                            'locked_stock' => 0,
                            'total_stock' => $product['stockQuantity'] ?? 0,
                            'last_updated' => now(),
                            'api_source' => 'master_products'
                        ];
                    }
                }
                
                $page++;
                
            } while ($page < $maxPages);
            
            Log::warning("❌ [Method 2] SKU not found in {$page} pages of master products");
            return null;
            
        } catch (\Exception $e) {
            Log::warning("❌ [Method 2] Exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Method 3: Direct SKU update trick (NEW FALLBACK)
     * Uses stock update endpoint with quantity 0 to get current stock info
     */
    private function attemptMethod3_UpdateTrick(string $sku): ?array
    {
        Log::info("🔍 [Method 3] Using update trick for: {$sku}");
        
        try {
            // Create a "fake" update with quantity 0 to get current stock info
            $stockUpdate = [
                'masterSku' => $sku,
                'quantity' => 0, // Zero quantity to avoid actual changes
                'remark' => 'Stock check via API - no actual update'
            ];
            
            $result = $this->gineeClient->updateStock([$stockUpdate]);
            
            if (($result['code'] ?? null) === 'SUCCESS') {
                $stockList = $result['data']['stockList'] ?? [];
                
                if (!empty($stockList)) {
                    $stockInfo = $stockList[0];
                    $currentStock = $stockInfo['availableStock'] ?? $stockInfo['warehouseStock'] ?? 0;
                    
                    Log::info("🎯 [Method 3] Update trick found stock for {$sku}: {$currentStock}");
                    
                    return [
                        'sku' => $sku,
                        'product_name' => $stockInfo['masterProductName'] ?? 'Unknown',
                        'warehouse_stock' => $stockInfo['warehouseStock'] ?? 0,
                        'available_stock' => $stockInfo['availableStock'] ?? 0,
                        'locked_stock' => $stockInfo['lockedStock'] ?? 0,
                        'total_stock' => $currentStock,
                        'last_updated' => $stockInfo['updateDatetime'] ?? now(),
                        'api_source' => 'update_trick',
                        'warehouse_id' => $stockInfo['warehouseId'] ?? 'Unknown'
                    ];
                }
            } else {
                // Check if failure message gives us info
                $message = $result['message'] ?? '';
                if (strpos($message, 'not found') !== false || strpos($message, 'not exist') !== false) {
                    Log::info("🎯 [Method 3] Update trick confirms SKU {$sku} does not exist");
                } else {
                    Log::warning("❌ [Method 3] Update trick failed: {$message}");
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::warning("❌ [Method 3] Exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Test single SKU with enhanced fallback methods
     */
    public function testSingleSkuEnhanced(string $sku, bool $dryRun = true): array
    {
        Log::info("🧪 [Enhanced Test] Testing SKU with all fallback methods: {$sku}");
        
        $results = [
            'sku' => $sku,
            'dry_run' => $dryRun,
            'methods_tested' => [],
            'success' => false,
            'stock_data' => null,
            'method_used' => null
        ];
        
        // Test Method 1: Warehouse Inventory
        $startTime = microtime(true);
        $method1Result = $this->attemptMethod1_WarehouseInventory($sku);
        $method1Duration = round(microtime(true) - $startTime, 2);
        
        $results['methods_tested']['method_1'] = [
            'name' => 'Warehouse Inventory Search',
            'success' => !is_null($method1Result),
            'duration_seconds' => $method1Duration,
            'data' => $method1Result
        ];
        
        if ($method1Result) {
            $results['success'] = true;
            $results['stock_data'] = $method1Result;
            $results['method_used'] = 'method_1';
            return $results;
        }
        
        // Test Method 2: Master Products
        $startTime = microtime(true);
        $method2Result = $this->attemptMethod2_MasterProducts($sku);
        $method2Duration = round(microtime(true) - $startTime, 2);
        
        $results['methods_tested']['method_2'] = [
            'name' => 'Master Products Search',
            'success' => !is_null($method2Result),
            'duration_seconds' => $method2Duration,
            'data' => $method2Result
        ];
        
        if ($method2Result) {
            $results['success'] = true;
            $results['stock_data'] = $method2Result;
            $results['method_used'] = 'method_2';
            return $results;
        }
        
        // Test Method 3: Update Trick
        $startTime = microtime(true);
        $method3Result = $this->attemptMethod3_UpdateTrick($sku);
        $method3Duration = round(microtime(true) - $startTime, 2);
        
        $results['methods_tested']['method_3'] = [
            'name' => 'Update Trick (Zero Quantity)',
            'success' => !is_null($method3Result),
            'duration_seconds' => $method3Duration,
            'data' => $method3Result
        ];
        
        if ($method3Result) {
            $results['success'] = true;
            $results['stock_data'] = $method3Result;
            $results['method_used'] = 'method_3';
            return $results;
        }
        
        // All methods failed
        $results['success'] = false;
        $results['message'] = "All 3 fallback methods failed to find SKU: {$sku}";
        
        return $results;
    }
    
    /**
     * Enhanced single SKU sync with fallback methods
     */
    public function syncSingleSkuEnhanced(string $sku, bool $dryRun = false): array
    {
        Log::info('🚀 [Enhanced Sync] Starting enhanced sync for SKU', ['sku' => $sku, 'dry_run' => $dryRun]);
        
        $gineeStock = $this->getStockFromGineeEnhanced($sku);
        
        if (!$gineeStock) {
            // Create detailed failure log
            \App\Models\GineeSyncLog::create([
                'type' => 'enhanced_sync',
                'status' => 'failed',
                'operation_type' => 'sync',
                'sku' => $sku,
                'product_name' => 'Unknown',
                'message' => "SKU {$sku} not found using any of the 3 enhanced methods",
                'dry_run' => $dryRun,
                'session_id' => \App\Models\GineeSyncLog::generateSessionId()
            ]);
            
            return [
                'success' => false,
                'message' => "SKU {$sku} not found using enhanced fallback methods",
                'data' => null
            ];
        }

        $updated = $this->updateLocalProductStock($sku, $gineeStock, $dryRun);
        
        if ($updated) {
            return [
                'success' => true,
                'message' => $dryRun ? 
                    "DRY RUN: Would update {$sku} with stock from {$gineeStock['api_source']}" : 
                    "Successfully updated {$sku} using {$gineeStock['api_source']} method",
                'data' => $gineeStock
            ];
        } else {
            return [
                'success' => false,
                'message' => "Failed to update local product for SKU {$sku}",
                'data' => ['ginee_stock' => $gineeStock]
            ];
        }
    }
}