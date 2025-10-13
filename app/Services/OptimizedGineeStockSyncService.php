<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\GineeMapping;
use App\Models\GineeSyncLog;
use Exception;

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
     */
    public function getBulkStockFromGinee(array $skus, array $options = []): array
    {
        $skus = array_map('strtoupper', $skus);
        $maxRetries = $options['max_retries'] ?? 3;
        $pageSize = $options['page_size'] ?? 500;

        Log::info("🚀 [OPTIMIZED] Bulk stock search for " . count($skus) . " SKUs");

        $foundStock = [];
        $notFound = [];
        $totalChecked = 0;
        $startTime = microtime(true);
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
                    sleep(2);
                    continue;
                }

                $items = $result['data']['content'] ?? [];
                if (empty($items)) {
                    Log::info("✅ Reached end of inventory at page {$page}");
                    break;
                }

                $totalChecked += count($items);
                Log::info("🔍 Processing " . count($items) . " items from page {$page}");

                foreach ($items as $item) {
                    $masterVariation = $item['masterVariation'] ?? [];
                    $warehouseInventory = $item['warehouseInventory'] ?? [];
                    $itemSku = strtoupper(trim($masterVariation['masterSku'] ?? ''));

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

                        $skus = array_diff($skus, [$itemSku]);
                    }
                }

                $page++;
                $retries = 0;

                if (empty($skus)) {
                    Log::info("✅ All SKUs found! Stopping search early");
                    break;
                }

                if ($page > 50) {
                    Log::warning("⚠️ Reached page limit (50) for safety");
                    break;
                }

                usleep(100000);

            } catch (Exception $e) {
                $retries++;
                Log::error("💥 Exception on page {$page}, retry {$retries}: " . $e->getMessage());
                if ($retries >= $maxRetries) {
                    Log::error("❌ Max retries reached due to exceptions");
                    break;
                }
                sleep(2);
            }
        }

        foreach ($skus as $sku) {
            $notFound[] = $sku;
        }

        if (!empty($notFound)) {
            Log::info("🔄 [FALLBACK] Triggering fallback search for " . count($notFound) . " SKUs");
            $fallbackResult = $this->fallbackIndividualSearch($notFound);
            if ($fallbackResult['success']) {
                $foundStock = array_merge($foundStock, $fallbackResult['found_stock']);
                $notFound = $fallbackResult['not_found'];
                Log::info("✅ [FALLBACK] Merged results - Final found: " . count($foundStock));
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
     * 🔁 Fallback update trick
     */
    private function fallbackUpdateTrick(array $skus): array
    {
        Log::info("🔄 Using fallback update trick for " . count($skus) . " SKUs");

        $foundStock = [];
        $notFound = [];

        foreach ($skus as $sku) {
            try {
                $stockUpdate = [
                    'masterSku' => $sku,
                    'quantity' => 0,
                    'remark' => 'Stock check via fallback method'
                ];

                $result = $this->gineeClient->updateStock([$stockUpdate]);
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
                        Log::info("✅ Fallback found: {$sku}");
                    } else {
                        $notFound[] = $sku;
                    }
                } else {
                    $notFound[] = $sku;
                }

            } catch (Exception $e) {
                Log::warning("Fallback failed for {$sku}: " . $e->getMessage());
                $notFound[] = $sku;
            }

            usleep(300000);
        }

        return [
            'success' => true,
            'found_stock' => $foundStock,
            'not_found' => $notFound,
        ];
    }

    /**
     * 🔍 Fallback individual search for SKUs that weren't found in bulk
     * Uses GineeClient::smartSkuSearch() for maximum reliability.
     */
    private function fallbackIndividualSearch(array $skus): array
    {
        Log::info("🔍 [FallbackIndividualSearch] Starting fallback for " . count($skus) . " SKUs");

        try {
            // Gunakan smartSkuSearch bawaan GineeClient
            $result = $this->gineeClient->smartSkuSearch($skus, [
                'strategies' => ['sku_filter', 'bulk_inventory', 'master_products'],
                'max_pages' => 20,
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::warning("⚠️ [FallbackIndividualSearch] API returned failure", [
                    'message' => $result['message'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'found_stock' => [],
                    'not_found' => $skus,
                ];
            }

            $data = $result['data'] ?? [];
            $foundItems = $data['found_items'] ?? [];
            $notFound = $data['not_found_skus'] ?? [];

            Log::info("✅ [FallbackIndividualSearch] Completed", [
                'found_count' => count($foundItems),
                'not_found_count' => count($notFound)
            ]);

            return [
                'success' => true,
                'found_stock' => $foundItems,
                'not_found' => $notFound,
            ];

        } catch (\Exception $e) {
            Log::error("💥 [FallbackIndividualSearch] Exception during fallback", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'found_stock' => [],
                'not_found' => $skus,
            ];
        }
    }

    /**
     * 🚀 Update local stock
     */
    public function updateLocalProductStock(string $sku, array $stockData, bool $dryRun = false, ?string $sessionId = null): bool
    {
        try {
            Log::info('📝 [Optimized Stock Update] Processing', [
                'sku' => $sku,
                'dry_run' => $dryRun,
                'session_id' => $sessionId
            ]);

            if (!$sessionId) {
                $sessionId = GineeSyncLog::generateSessionId();
            }

            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                GineeSyncLog::create([
                    'type' => 'optimized_sync',
                    'status' => 'failed',
                    'operation_type' => 'stock_push',
                    'method_used' => 'optimized_bulk',
                    'sku' => $sku,
                    'product_name' => 'Product Not Found',
                    'message' => "Product with SKU {$sku} not found in database",
                    'dry_run' => $dryRun,
                    'session_id' => $sessionId,
                    'created_at' => now()
                ]);
                return false;
            }

            $oldStock = $product->stock_quantity ?? 0;
            $oldWarehouseStock = $product->warehouse_stock ?? 0;

            // 🧾 Parse stock data dari API
            Log::debug('🧾 [Stock Data Parsed]', [
                'sku' => $sku,
                'warehouse_stock' => $stockData['warehouse_stock'] ?? null,
                'locked_stock' => $stockData['locked_stock'] ?? null,
                'available_stock_raw' => $stockData['available_stock'] ?? null,
                'total_stock' => $stockData['total_stock'] ?? null,
            ]);

            $warehouseStock = (int) ($stockData['warehouse_stock'] ?? 0);
            $lockedStock    = (int) ($stockData['locked_stock'] ?? 0);

            // 🧮 Rumus: available_stock = warehouse_stock - locked_stock
            $newStock = max($warehouseStock - $lockedStock, 0);
            $newWarehouseStock = $warehouseStock;
            $stockChange = $newStock - $oldStock;

            Log::debug('🧮 [Optimized] Calculated newStock from formula', [
                'sku' => $sku,
                'warehouse_stock' => $warehouseStock,
                'locked_stock' => $lockedStock,
                'calculated_available_stock' => $newStock,
                'old_stock' => $oldStock,
                'change' => $stockChange,
            ]);

            // ✅ DRY RUN mode
            if ($dryRun) {
                Log::info('🧪 [Optimized] Dry run - would update', [
                    'sku' => $sku,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $stockChange
                ]);

                GineeSyncLog::create([
                    'type' => 'optimized_sync',
                    'status' => 'skipped',
                    'operation_type' => 'stock_push',
                    'method_used' => 'optimized_bulk',
                    'sku' => $sku,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $stockChange,
                    'old_warehouse_stock' => $oldWarehouseStock,
                    'new_warehouse_stock' => $newWarehouseStock,
                    'message' => "DRY RUN - Would update from {$oldStock} to {$newStock} (formula: warehouse - locked)",
                    'ginee_response' => $stockData,
                    'dry_run' => true,
                    'session_id' => $sessionId,
                    'created_at' => now()
                ]);

                return true;
            }

            // ✅ Actual update
            $product->stock_quantity = $newStock;
            $product->warehouse_stock = $newWarehouseStock;
            $product->ginee_last_sync = now();
            $product->ginee_sync_status = 'synced';
            $saved = $product->save();

            if ($saved) {
                GineeSyncLog::create([
                    'type' => 'optimized_sync',
                    'status' => 'success',
                    'operation_type' => 'stock_push',
                    'method_used' => 'optimized_bulk',
                    'sku' => $sku,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $stockChange,
                    'old_warehouse_stock' => $oldWarehouseStock,
                    'new_warehouse_stock' => $newWarehouseStock,
                    'message' => "SUCCESS - Updated from {$oldStock} to {$newStock} (formula: warehouse - locked)",
                    'dry_run' => false,
                    'session_id' => $sessionId,
                    'created_at' => now()
                ]);

                Log::info('✅ [Optimized] Successfully updated product stock', [
                    'sku' => $sku,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $stockChange
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('❌ [Optimized] Exception during stock update', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

}
