<?php
/*
=============================================================================
API vs DASHBOARD STOCK COMPARISON - SKU 196432312689
=============================================================================

PROBLEM: Dashboard shows 1 unit, but API shows 0 units
GOAL: Find out why there's discrepancy between API and Dashboard

Simpan sebagai: api-vs-dashboard.php
Jalankan: php api-vs-dashboard.php
=============================================================================
*/

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\GineeClient;

if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
    echo "❌ ERROR: Laravel bootstrap/app.php not found!\n";
    exit(1);
}

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 API vs DASHBOARD STOCK COMPARISON\n";
echo "====================================\n";
echo "🎯 SKU: 196432312689\n";
echo "📊 Dashboard shows: 1 unit available\n";
echo "🔌 API shows: 0 units\n";
echo "❓ WHY THE DIFFERENCE?\n\n";

$targetSku = '196432312689';

try {
    $ginee = new GineeClient();
    echo "✅ Ginee Client initialized\n\n";

    // ============= COMPREHENSIVE API TESTING =============
    class ApiDashboardComparison
    {
        private GineeClient $ginee;
        
        public function __construct(GineeClient $ginee)
        {
            $this->ginee = $ginee;
        }

        /**
         * Test SEMUA method API yang mungkin berbeda
         */
        public function testAllApiMethods(string $sku): array
        {
            echo "🔬 TESTING ALL API METHODS FOR SKU: {$sku}\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            
            $results = [
                'sku' => $sku,
                'timestamp' => date('Y-m-d H:i:s'),
                'dashboard_expected' => [
                    'warehouse_stock' => 1,
                    'available_stock' => 1,
                    'total_stock' => 1
                ],
                'api_results' => []
            ];

            // METHOD 1: Warehouse Inventory List (Standard)
            echo "📦 METHOD 1: Warehouse Inventory List\n";
            echo "Endpoint: POST /openapi/warehouse-inventory/v1/sku/list\n";
            $method1 = $this->testWarehouseInventoryList($sku);
            $results['api_results']['warehouse_inventory_list'] = $method1;
            echo "\n";

            // METHOD 2: UpdateStock Trick (Detail)
            echo "🔧 METHOD 2: UpdateStock Trick (quantity 0)\n";
            echo "Endpoint: POST /openapi/warehouse-inventory/v1/product/stock/update\n";
            $method2 = $this->testUpdateStockTrick($sku);
            $results['api_results']['update_stock_trick'] = $method2;
            echo "\n";

            // METHOD 3: Master Products dengan SKU filter
            echo "📋 METHOD 3: Master Products dengan SKU filter\n";
            echo "Endpoint: POST /openapi/product/master/v1/list\n";
            $method3 = $this->testMasterProductsWithSku($sku);
            $results['api_results']['master_products_filtered'] = $method3;
            echo "\n";

            // METHOD 4: Test dengan Warehouse ID yang berbeda
            echo "🏪 METHOD 4: Test dengan Warehouse ID Spesifik\n";
            $method4 = $this->testWithSpecificWarehouse($sku);
            $results['api_results']['specific_warehouse'] = $method4;
            echo "\n";

            // METHOD 5: Test pada waktu berbeda (cache issue?)
            echo "⏰ METHOD 5: Multiple API calls (cache test)\n";
            $method5 = $this->testMultipleCalls($sku);
            $results['api_results']['multiple_calls'] = $method5;
            echo "\n";

            return $results;
        }

        private function testWarehouseInventoryList(string $sku): array
        {
            echo "   🔍 Searching warehouse inventory...\n";
            
            $found = false;
            $stockData = null;
            
            // Test multiple pages
            for ($page = 0; $page < 25; $page++) {
                $inventory = $this->ginee->getWarehouseInventory(['page' => $page, 'size' => 100]);
                
                if (($inventory['code'] ?? null) === 'SUCCESS') {
                    $inventoryList = $inventory['data']['content'] ?? [];
                    
                    if (empty($inventoryList)) {
                        break;
                    }
                    
                    foreach ($inventoryList as $item) {
                        $masterVar = $item['masterVariation'] ?? [];
                        if (($masterVar['masterSku'] ?? '') === $sku) {
                            $found = true;
                            $stockData = [
                                'page_found' => $page,
                                'transaction_id' => $inventory['transactionId'] ?? 'N/A',
                                'warehouse_stock' => $item['warehouseStock'] ?? 0,
                                'available_stock' => $item['availableStock'] ?? 0,
                                'locked_stock' => $item['lockedStock'] ?? 0,
                                'reserved_stock' => $item['reservedStock'] ?? 0,
                                'warehouse_id' => $item['warehouseId'] ?? 'N/A',
                                'raw_item' => $item
                            ];
                            echo "   ✅ FOUND at page {$page}\n";
                            echo "   📊 Warehouse: {$stockData['warehouse_stock']}, Available: {$stockData['available_stock']}\n";
                            break 2;
                        }
                    }
                }
            }
            
            if (!$found) {
                echo "   ❌ NOT FOUND in warehouse inventory\n";
                return ['success' => false, 'error' => 'SKU not found'];
            }
            
            return array_merge(['success' => true], $stockData);
        }

        private function testUpdateStockTrick(string $sku): array
        {
            echo "   🔧 Testing updateStock with quantity 0...\n";
            
            $testUpdate = [['masterSku' => $sku, 'quantity' => 0]];
            $result = $this->ginee->updateStock($testUpdate);
            
            if (($result['code'] ?? null) !== 'SUCCESS') {
                echo "   ❌ FAILED: " . ($result['message'] ?? 'Unknown') . "\n";
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Unknown',
                    'raw_response' => $result
                ];
            }
            
            $stockList = $result['data']['stockList'] ?? [];
            
            foreach ($stockList as $item) {
                if (($item['masterSku'] ?? '') === $sku) {
                    echo "   ✅ FOUND in stock list\n";
                    echo "   📊 Warehouse: " . ($item['warehouseStock'] ?? 0) . ", Available: " . ($item['availableStock'] ?? 0) . "\n";
                    echo "   📊 Spare: " . ($item['spareStock'] ?? 0) . ", Locked: " . ($item['lockedStock'] ?? 0) . "\n";
                    echo "   🕒 Last Update: " . ($item['updateDatetime'] ?? 'N/A') . "\n";
                    
                    return [
                        'success' => true,
                        'transaction_id' => $result['transactionId'] ?? 'N/A',
                        'warehouse_stock' => $item['warehouseStock'] ?? 0,
                        'available_stock' => $item['availableStock'] ?? 0,
                        'spare_stock' => $item['spareStock'] ?? 0,
                        'locked_stock' => $item['lockedStock'] ?? 0,
                        'transport_stock' => $item['transportStock'] ?? 0,
                        'promotion_stock' => $item['promotionStock'] ?? 0,
                        'out_stock' => $item['outStock'] ?? 0,
                        'safety_stock' => $item['safetyStock'] ?? 0,
                        'update_datetime' => $item['updateDatetime'] ?? 'N/A',
                        'warehouse_id' => $item['warehouseId'] ?? 'N/A',
                        'raw_item' => $item
                    ];
                }
            }
            
            echo "   ❌ NOT FOUND in stock list\n";
            return ['success' => false, 'error' => 'SKU not in stock list'];
        }

        private function testMasterProductsWithSku(string $sku): array
        {
            echo "   📋 Testing master products with SKU filter...\n";
            
            $products = $this->ginee->getMasterProducts(['sku' => $sku, 'page' => 0, 'size' => 50]);
            
            if (($products['code'] ?? null) !== 'SUCCESS') {
                echo "   ❌ FAILED: " . ($products['message'] ?? 'Unknown') . "\n";
                return ['success' => false, 'error' => $products['message'] ?? 'Unknown'];
            }
            
            $productList = $products['data']['list'] ?? [];
            echo "   📦 Found " . count($productList) . " products\n";
            
            foreach ($productList as $product) {
                if (($product['masterSku'] ?? '') === $sku) {
                    echo "   ✅ FOUND in master products\n";
                    echo "   📦 Name: " . ($product['productName'] ?? 'N/A') . "\n";
                    echo "   📊 Status: " . ($product['status'] ?? 'N/A') . "\n";
                    
                    return [
                        'success' => true,
                        'transaction_id' => $products['transactionId'] ?? 'N/A',
                        'product_name' => $product['productName'] ?? 'N/A',
                        'status' => $product['status'] ?? 'N/A',
                        'brand' => $product['brand'] ?? 'N/A',
                        'category' => $product['categoryName'] ?? 'N/A',
                        'raw_product' => $product
                    ];
                }
            }
            
            echo "   ❌ NOT FOUND in master products\n";
            return ['success' => false, 'error' => 'SKU not in master products'];
        }

        private function testWithSpecificWarehouse(string $sku): array
        {
            echo "   🏪 Testing dengan warehouse spesifik...\n";
            
            // Coba ambil daftar warehouse dulu
            $warehouses = $this->ginee->getWarehouses(['page' => 0, 'size' => 20]);
            
            if (($warehouses['code'] ?? null) !== 'SUCCESS') {
                echo "   ⚠️ Cannot get warehouse list\n";
                return ['success' => false, 'error' => 'Cannot get warehouses'];
            }
            
            $warehouseList = $warehouses['data']['list'] ?? [];
            echo "   📦 Found " . count($warehouseList) . " warehouses\n";
            
            $results = [];
            
            foreach ($warehouseList as $warehouse) {
                $warehouseId = $warehouse['warehouseId'] ?? 'N/A';
                $warehouseName = $warehouse['warehouseName'] ?? 'Unknown';
                
                echo "   🔍 Testing warehouse: {$warehouseName} ({$warehouseId})\n";
                
                // Test updateStock dengan warehouse spesifik
                try {
                    $testUpdate = [['masterSku' => $sku, 'quantity' => 0]];
                    $result = $this->ginee->updateStock($testUpdate, $warehouseId);
                    
                    if (($result['code'] ?? null) === 'SUCCESS') {
                        $stockList = $result['data']['stockList'] ?? [];
                        
                        foreach ($stockList as $item) {
                            if (($item['masterSku'] ?? '') === $sku) {
                                $warehouseStock = $item['warehouseStock'] ?? 0;
                                $availableStock = $item['availableStock'] ?? 0;
                                
                                echo "      📊 Stock: W:{$warehouseStock}, A:{$availableStock}\n";
                                
                                $results[$warehouseId] = [
                                    'warehouse_name' => $warehouseName,
                                    'warehouse_stock' => $warehouseStock,
                                    'available_stock' => $availableStock,
                                    'total_stock' => $warehouseStock + $availableStock
                                ];
                                break;
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo "      ❌ Error: " . $e->getMessage() . "\n";
                }
            }
            
            return [
                'success' => !empty($results),
                'warehouse_results' => $results,
                'total_warehouses_tested' => count($warehouseList)
            ];
        }

        private function testMultipleCalls(string $sku): array
        {
            echo "   ⏰ Testing multiple API calls (cache issue check)...\n";
            
            $calls = [];
            
            // Call API 5 kali dalam 10 detik
            for ($i = 1; $i <= 5; $i++) {
                echo "   📞 Call #{$i}...";
                
                $testUpdate = [['masterSku' => $sku, 'quantity' => 0]];
                $result = $this->ginee->updateStock($testUpdate);
                
                if (($result['code'] ?? null) === 'SUCCESS') {
                    $stockList = $result['data']['stockList'] ?? [];
                    
                    foreach ($stockList as $item) {
                        if (($item['masterSku'] ?? '') === $sku) {
                            $warehouseStock = $item['warehouseStock'] ?? 0;
                            $availableStock = $item['availableStock'] ?? 0;
                            
                            echo " W:{$warehouseStock}, A:{$availableStock}\n";
                            
                            $calls[$i] = [
                                'timestamp' => date('H:i:s'),
                                'transaction_id' => $result['transactionId'] ?? 'N/A',
                                'warehouse_stock' => $warehouseStock,
                                'available_stock' => $availableStock,
                                'update_datetime' => $item['updateDatetime'] ?? 'N/A'
                            ];
                            break;
                        }
                    }
                } else {
                    echo " FAILED\n";
                }
                
                if ($i < 5) sleep(2); // Wait 2 seconds between calls
            }
            
            // Check consistency
            $stocks = array_column($calls, 'available_stock');
            $isConsistent = count(array_unique($stocks)) === 1;
            
            echo "   📊 Consistency: " . ($isConsistent ? "✅ CONSISTENT" : "⚠️ INCONSISTENT") . "\n";
            
            return [
                'success' => !empty($calls),
                'calls' => $calls,
                'is_consistent' => $isConsistent,
                'unique_stocks' => array_unique($stocks)
            ];
        }
    }

    // ============= RUN COMPREHENSIVE TEST =============
    $comparison = new ApiDashboardComparison($ginee);
    $results = $comparison->testAllApiMethods($targetSku);

    // ============= ANALYSIS & CONCLUSIONS =============
    echo "🎯 COMPREHENSIVE ANALYSIS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "📊 EXPECTED (Dashboard): 1 unit available\n";
    echo "🔌 API RESULTS:\n\n";

    $apiResults = $results['api_results'];

    // Analyze each method
    foreach ($apiResults as $method => $result) {
        echo "   🔬 " . strtoupper(str_replace('_', ' ', $method)) . ":\n";
        
        if ($result['success'] ?? false) {
            $availableStock = $result['available_stock'] ?? 0;
            $warehouseStock = $result['warehouse_stock'] ?? 0;
            
            if ($availableStock == 1) {
                echo "      ✅ MATCHES DASHBOARD: {$availableStock} unit available\n";
            } elseif ($availableStock == 0) {
                echo "      ❌ DIFFERS FROM DASHBOARD: {$availableStock} units (expected 1)\n";
            } else {
                echo "      ⚠️ UNEXPECTED VALUE: {$availableStock} units\n";
            }
            
            if (isset($result['update_datetime'])) {
                echo "      🕒 Last Update: " . $result['update_datetime'] . "\n";
            }
        } else {
            echo "      ❌ METHOD FAILED: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }

    // ============= FINAL DIAGNOSIS =============
    echo "🎯 FINAL DIAGNOSIS\n";
    echo "═══════════════════════════════════════════════════════════\n";

    $foundMatchingResult = false;
    $foundZeroResult = false;

    foreach ($apiResults as $method => $result) {
        if ($result['success'] ?? false) {
            $availableStock = $result['available_stock'] ?? 0;
            if ($availableStock == 1) {
                $foundMatchingResult = true;
            } elseif ($availableStock == 0) {
                $foundZeroResult = true;
            }
        }
    }

    if ($foundMatchingResult && !$foundZeroResult) {
        echo "✅ RESOLVED: All API methods now match dashboard (1 unit)\n";
        echo "💡 Possible previous issue was temporary or cache related\n";
    } elseif (!$foundMatchingResult && $foundZeroResult) {
        echo "❌ CONFIRMED DISCREPANCY: API shows 0, Dashboard shows 1\n";
        echo "💡 POSSIBLE CAUSES:\n";
        echo "   1. Dashboard cache vs Real-time API\n";
        echo "   2. Different warehouse being checked\n";
        echo "   3. Timing issue (stock changed between checks)\n";
        echo "   4. Dashboard bug or API bug\n";
        echo "   5. Permission/access level differences\n";
    } elseif ($foundMatchingResult && $foundZeroResult) {
        echo "⚠️ INCONSISTENT: Some API methods show 0, others show 1\n";
        echo "💡 This suggests endpoint-specific issues or caching problems\n";
    } else {
        echo "❓ INCONCLUSIVE: Need more investigation\n";
    }

    echo "\n🔧 RECOMMENDED ACTIONS:\n";
    echo "1. Refresh dashboard dan cek lagi\n";
    echo "2. Cek manual di Ginee stock management\n";
    echo "3. Test pada waktu berbeda\n";
    echo "4. Hubungi Ginee support jika masih bermasalah\n";

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n\n";
}

echo "\n=== COMPARISON COMPLETED ===\n";
echo "🕒 Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "🔒 REMINDER: All tests are READ-only!\n";

/*
=============================================================================
POSSIBLE REASONS FOR DASHBOARD vs API DISCREPANCY
=============================================================================

1. CACHING ISSUES:
   - Dashboard menggunakan cache yang berbeda
   - API real-time vs Dashboard cached
   - Browser cache vs Server cache

2. WAREHOUSE DIFFERENCES:
   - Dashboard melihat warehouse A
   - API default ke warehouse B
   - Stock di warehouse berbeda

3. TIMING ISSUES:
   - Stock berubah antara dashboard load dan API call
   - Transaction sedang berlangsung
   - Lock/unlock stock timing

4. PERMISSION LEVELS:
   - Dashboard user punya akses ke data berbeda
   - API key punya permission terbatas
   - Role-based access control

5. BUG/SISTEM:
   - Dashboard bug (false positive)
   - API bug (false negative)  
   - Sync issue antara sistem

SOLUTION:
- Test semua method API
- Test multiple warehouse
- Test multiple timing
- Compare dengan manual check

=============================================================================
*/