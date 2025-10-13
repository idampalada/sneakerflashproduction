<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeClient;
use Exception;

class DetailedStockAnalysisCommand extends Command
{
    protected $signature = 'ginee:detailed-stock-analysis {--sample-size=10 : Number of items to analyze from each endpoint}';
    protected $description = 'Analyze detailed stock data from all 4 Ginee endpoints to identify discrepancies';

    private $gineeClient;

    public function handle()
    {
        $this->gineeClient = new GineeClient();
        $sampleSize = (int) $this->option('sample-size');

        $this->info('🔍 DETAILED STOCK ANALYSIS - ALL 4 ENDPOINTS');
        $this->info('='.str_repeat('=', 60));
        $this->warn('🛡️ SAFETY: This is READ-ONLY analysis - no data will be modified');
        $this->newLine();

        // Analyze each endpoint
        $this->analyzeWarehouseInventory($sampleSize);
        $this->analyzeMasterProducts($sampleSize);
        $this->analyzeShops($sampleSize);
        $this->analyzeWarehouses($sampleSize);

        // Summary & recommendations
        $this->provideSummaryRecommendations();

        return Command::SUCCESS;
    }

    private function analyzeWarehouseInventory(int $sampleSize)
    {
        $this->info('📦 ENDPOINT 1: Warehouse Inventory Analysis');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getWarehouseInventory([
                'page' => 0,
                'size' => $sampleSize
            ]);

            if (($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $items = $data['content'] ?? $data['list'] ?? $data;

                if (is_array($items) && count($items) > 0) {
                    $this->info("✅ Found " . count($items) . " items in warehouse inventory");
                    
                    $stockSummary = [];
                    $detailedData = [];

                    foreach ($items as $index => $item) {
                        $masterVariation = $item['masterVariation'] ?? [];
                        $warehouseInventory = $item['warehouseInventory'] ?? $item;
                        
                        $sku = $masterVariation['masterSku'] ?? $item['masterSku'] ?? "Item-{$index}";
                        $productName = $masterVariation['name'] ?? $item['productName'] ?? 'Unknown';

                        // Extract all possible stock fields
                        $stockFields = [
                            'warehouseStock' => $warehouseInventory['warehouseStock'] ?? $item['warehouseStock'] ?? 0,
                            'availableStock' => $warehouseInventory['availableStock'] ?? $item['availableStock'] ?? 0,
                            'lockedStock' => $warehouseInventory['lockedStock'] ?? $item['lockedStock'] ?? 0,
                            'spareStock' => $warehouseInventory['spareStock'] ?? $item['spareStock'] ?? 0,
                            'transportStock' => $warehouseInventory['transportStock'] ?? $item['transportStock'] ?? 0,
                            'promotionStock' => $warehouseInventory['promotionStock'] ?? $item['promotionStock'] ?? 0,
                            'safetyStock' => $warehouseInventory['safetyStock'] ?? $item['safetyStock'] ?? 0,
                            'stockQuantity' => $item['stockQuantity'] ?? 0,
                            'stock' => $item['stock'] ?? 0
                        ];

                        $totalStock = array_sum($stockFields);
                        
                        $detailedData[] = [
                            'SKU' => $sku,
                            'Product' => substr($productName, 0, 25),
                            'Warehouse' => $stockFields['warehouseStock'],
                            'Available' => $stockFields['availableStock'],
                            'Locked' => $stockFields['lockedStock'],
                            'Total' => $totalStock
                        ];

                        $stockSummary[] = $totalStock;
                    }

                    // Display table
                    $this->table(['SKU', 'Product', 'Warehouse', 'Available', 'Locked', 'Total'], 
                                array_slice($detailedData, 0, 5));

                    // Statistics
                    $totalProducts = count($stockSummary);
                    $productsWithStock = count(array_filter($stockSummary, fn($s) => $s > 0));
                    $totalStockValue = array_sum($stockSummary);
                    $maxStock = max($stockSummary);
                    $avgStock = $totalProducts > 0 ? round(array_sum($stockSummary) / $totalProducts, 2) : 0;

                    $this->info("📊 Warehouse Inventory Statistics:");
                    $this->line("   - Total Products: {$totalProducts}");
                    $this->line("   - Products with Stock: {$productsWithStock} (" . round($productsWithStock/$totalProducts*100, 1) . "%)");
                    $this->line("   - Total Stock Units: {$totalStockValue}");
                    $this->line("   - Average Stock per Product: {$avgStock}");
                    $this->line("   - Maximum Stock: {$maxStock}");

                } else {
                    $this->warn("⚠️ No items found in warehouse inventory response");
                    $this->line("Response structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ Warehouse Inventory API failed");
                $this->line("Error: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("💥 Exception in warehouse inventory: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function analyzeMasterProducts(int $sampleSize)
    {
        $this->info('📋 ENDPOINT 2: Master Products Analysis');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getMasterProducts([
                'page' => 0,
                'size' => $sampleSize
            ]);

            if (($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $items = $data['list'] ?? $data['content'] ?? $data;

                if (is_array($items) && count($items) > 0) {
                    $this->info("✅ Found " . count($items) . " products in master products");
                    
                    $stockSummary = [];
                    $detailedData = [];

                    foreach ($items as $index => $item) {
                        $sku = $item['masterSku'] ?? $item['sku'] ?? "Product-{$index}";
                        $productName = $item['name'] ?? $item['productName'] ?? 'Unknown';

                        // Extract stock fields from master products
                        $stockFields = [
                            'stockQuantity' => $item['stockQuantity'] ?? 0,
                            'inventoryQuantity' => $item['inventoryQuantity'] ?? 0,
                            'availableQuantity' => $item['availableQuantity'] ?? 0,
                            'reservedQuantity' => $item['reservedQuantity'] ?? 0,
                            'warehouseStock' => $item['warehouseStock'] ?? 0,
                            'totalStock' => $item['totalStock'] ?? 0
                        ];

                        $maxStock = max($stockFields);
                        
                        $detailedData[] = [
                            'SKU' => $sku,
                            'Product' => substr($productName, 0, 25),
                            'Stock Qty' => $stockFields['stockQuantity'],
                            'Inventory' => $stockFields['inventoryQuantity'],
                            'Available' => $stockFields['availableQuantity'],
                            'Max Stock' => $maxStock
                        ];

                        $stockSummary[] = $maxStock;
                    }

                    // Display table
                    $this->table(['SKU', 'Product', 'Stock Qty', 'Inventory', 'Available', 'Max Stock'], 
                                array_slice($detailedData, 0, 5));

                    // Statistics
                    $totalProducts = count($stockSummary);
                    $productsWithStock = count(array_filter($stockSummary, fn($s) => $s > 0));
                    $totalStockValue = array_sum($stockSummary);
                    $maxStock = max($stockSummary);
                    $avgStock = $totalProducts > 0 ? round(array_sum($stockSummary) / $totalProducts, 2) : 0;

                    $this->info("📊 Master Products Statistics:");
                    $this->line("   - Total Products: {$totalProducts}");
                    $this->line("   - Products with Stock: {$productsWithStock} (" . round($productsWithStock/$totalProducts*100, 1) . "%)");
                    $this->line("   - Total Stock Units: {$totalStockValue}");
                    $this->line("   - Average Stock per Product: {$avgStock}");
                    $this->line("   - Maximum Stock: {$maxStock}");

                } else {
                    $this->warn("⚠️ No products found in master products response");
                    $this->line("Response structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT));
                }
            } else {
                $this->error("❌ Master Products API failed");
                $this->line("Error: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("💥 Exception in master products: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function analyzeShops(int $sampleSize)
    {
        $this->info('🏪 ENDPOINT 3: Shops Analysis');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getShops([
                'page' => 0,
                'size' => $sampleSize
            ]);

            if (($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $items = $data['list'] ?? $data['content'] ?? $data;

                if (is_array($items) && count($items) > 0) {
                    $this->info("✅ Found " . count($items) . " shops");
                    
                    $detailedData = [];

                    foreach ($items as $index => $item) {
                        $shopId = $item['id'] ?? $item['shopId'] ?? "Shop-{$index}";
                        $shopName = $item['name'] ?? $item['shopName'] ?? 'Unknown';
                        $platform = $item['platform'] ?? $item['platformName'] ?? 'N/A';
                        $status = $item['status'] ?? $item['isActive'] ?? 'Unknown';
                        
                        $detailedData[] = [
                            'Shop ID' => $shopId,
                            'Shop Name' => substr($shopName, 0, 20),
                            'Platform' => $platform,
                            'Status' => $status
                        ];
                    }

                    // Display table
                    $this->table(['Shop ID', 'Shop Name', 'Platform', 'Status'], 
                                array_slice($detailedData, 0, 5));

                    $this->info("📊 Shops Statistics:");
                    $this->line("   - Total Shops: " . count($items));
                    $this->line("   - This endpoint typically contains shop/store info, not direct stock data");

                } else {
                    $this->warn("⚠️ No shops found in response");
                }
            } else {
                $this->error("❌ Shops API failed");
                $this->line("Error: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("💥 Exception in shops: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function analyzeWarehouses(int $sampleSize)
    {
        $this->info('🏭 ENDPOINT 4: Warehouses Analysis');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getWarehouses([
                'page' => 0,
                'size' => $sampleSize
            ]);

            if (($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $items = $data['list'] ?? $data['content'] ?? $data;

                if (is_array($items) && count($items) > 0) {
                    $this->info("✅ Found " . count($items) . " warehouses");
                    
                    $detailedData = [];

                    foreach ($items as $index => $item) {
                        $warehouseId = $item['id'] ?? $item['warehouseId'] ?? "WH-{$index}";
                        $warehouseName = $item['name'] ?? $item['warehouseName'] ?? 'Unknown';
                        $location = $item['location'] ?? $item['address'] ?? 'N/A';
                        $status = $item['status'] ?? $item['isActive'] ?? 'Unknown';
                        
                        $detailedData[] = [
                            'Warehouse ID' => $warehouseId,
                            'Name' => substr($warehouseName, 0, 20),
                            'Location' => substr($location, 0, 15),
                            'Status' => $status
                        ];
                    }

                    // Display table
                    $this->table(['Warehouse ID', 'Name', 'Location', 'Status'], 
                                array_slice($detailedData, 0, 5));

                    $this->info("📊 Warehouses Statistics:");
                    $this->line("   - Total Warehouses: " . count($items));
                    $this->line("   - This endpoint contains warehouse info, not direct stock quantities");

                } else {
                    $this->warn("⚠️ No warehouses found in response");
                }
            } else {
                $this->error("❌ Warehouses API failed");
                $this->line("Error: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("💥 Exception in warehouses: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function provideSummaryRecommendations()
    {
        $this->info('💡 SUMMARY & RECOMMENDATIONS');
        $this->line(str_repeat('=', 60));

        $this->warn('🔍 KEY FINDINGS:');
        $this->line('1. WAREHOUSE INVENTORY: Primary source for stock quantities');
        $this->line('   - Contains: warehouseStock, availableStock, lockedStock');
        $this->line('   - Best for: Real-time stock sync operations');
        $this->newLine();

        $this->line('2. MASTER PRODUCTS: Product catalog with basic stock info');
        $this->line('   - Contains: stockQuantity (may be outdated)');
        $this->line('   - Best for: Product discovery and basic inventory check');
        $this->newLine();

        $this->line('3. SHOPS: Store/platform information');
        $this->line('   - Contains: Shop details, no direct stock data');
        $this->line('   - Best for: Multi-store management');
        $this->newLine();

        $this->line('4. WAREHOUSES: Warehouse configuration');
        $this->line('   - Contains: Warehouse details, no stock quantities');
        $this->line('   - Best for: Warehouse ID mapping');
        $this->newLine();

        $this->warn('🎯 RECOMMENDATIONS FOR STOCK SYNC:');
        $this->line('✅ PRIMARY: Use Warehouse Inventory for stock synchronization');
        $this->line('✅ FALLBACK: Use Master Products if warehouse inventory fails');
        $this->line('⚠️ AVOID: Using Shops or Warehouses for stock data');
        $this->line('🔄 STRATEGY: Check both sources to identify discrepancies');
        $this->newLine();

        $this->warn('🚨 TROUBLESHOOTING FAILED SKUs:');
        $this->line('1. Check if SKU exists in Master Products first');
        $this->line('2. If in Master Products but not Warehouse Inventory: SKU may be inactive');
        $this->line('3. If not in either: SKU may be deleted or never existed');
        $this->line('4. Cross-reference with Ginee dashboard manually');
        $this->newLine();
    }
}