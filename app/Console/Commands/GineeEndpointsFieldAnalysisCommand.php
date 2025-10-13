<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeClient;
use App\Services\OptimizedGineeStockSyncService;

class GineeEndpointsFieldAnalysisCommand extends Command
{
    protected $signature = 'ginee:analyze-endpoints {sku}';
    protected $description = 'Analyze field structure from all 4 Ginee endpoints for specific SKU';

    private $gineeClient;
    private $optimizedService;

    public function handle()
    {
        $sku = $this->argument('sku');
        $this->gineeClient = new GineeClient();
        $this->optimizedService = new OptimizedGineeStockSyncService();

        $this->info('🔍 GINEE 4 ENDPOINTS FIELD ANALYSIS');
        $this->info('📋 Target SKU: ' . $sku);
        $this->info('='.str_repeat('=', 60));
        $this->newLine();

        // Analyze all 4 endpoints
        $this->analyzeEndpoint1_Shops();
        $this->analyzeEndpoint2_Warehouses();  
        $this->analyzeEndpoint3_MasterProducts($sku);
        $this->analyzeEndpoint4_WarehouseInventory($sku);
        
        // Show which endpoint Ginee Optimization uses
        $this->showGineeOptimizationEndpoint($sku);

        return Command::SUCCESS;
    }

    private function analyzeEndpoint1_Shops()
    {
        $this->info('🏪 ENDPOINT 1: SHOPS');
        $this->line('📍 URL: POST /openapi/shop/v1/list');
        $this->line('🎯 Purpose: Get shop/store information');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getShops(['page' => 0, 'size' => 2]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $shops = $response['data']['list'] ?? [];
                
                if (!empty($shops)) {
                    $sample = $shops[0];
                    $this->info('✅ Fields available in Shops endpoint:');
                    
                    $fields = [];
                    foreach ($sample as $key => $value) {
                        $type = gettype($value);
                        $preview = is_string($value) ? substr($value, 0, 20) : 
                                  (is_array($value) ? '[array]' : 
                                  (is_bool($value) ? ($value ? 'true' : 'false') : $value));
                        $fields[] = [$key, $type, $preview];
                    }
                    
                    $this->table(['Field Name', 'Type', 'Sample Value'], $fields);
                    
                    $this->warn('⚠️ SHOPS ENDPOINT: No stock data available');
                    $this->line('   This endpoint only contains shop configuration');
                } else {
                    $this->warn('⚠️ No shops found');
                }
            } else {
                $this->error('❌ Shops endpoint failed: ' . ($response['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('💥 Exception: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function analyzeEndpoint2_Warehouses()
    {
        $this->info('🏭 ENDPOINT 2: WAREHOUSES');
        $this->line('📍 URL: POST /openapi/warehouse/v1/list');
        $this->line('🎯 Purpose: Get warehouse configuration');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getWarehouses(['page' => 0, 'size' => 2]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $warehouses = $response['data']['content'] ?? $response['data']['list'] ?? [];
                
                if (!empty($warehouses)) {
                    $sample = $warehouses[0];
                    $this->info('✅ Fields available in Warehouses endpoint:');
                    
                    $fields = [];
                    foreach ($sample as $key => $value) {
                        $type = gettype($value);
                        $preview = is_string($value) ? substr($value, 0, 20) : 
                                  (is_array($value) ? '[array]' : 
                                  (is_bool($value) ? ($value ? 'true' : 'false') : $value));
                        $fields[] = [$key, $type, $preview];
                    }
                    
                    $this->table(['Field Name', 'Type', 'Sample Value'], $fields);
                    
                    $this->warn('⚠️ WAREHOUSES ENDPOINT: No stock quantities available');
                    $this->line('   This endpoint only contains warehouse info & settings');
                } else {
                    $this->warn('⚠️ No warehouses found');
                }
            } else {
                $this->error('❌ Warehouses endpoint failed: ' . ($response['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('💥 Exception: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function analyzeEndpoint3_MasterProducts($sku)
    {
        $this->info('📋 ENDPOINT 3: MASTER PRODUCTS');
        $this->line('📍 URL: POST /openapi/product/master/v1/list');
        $this->line('🎯 Purpose: Get product catalog with basic stock info');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getMasterProducts(['page' => 0, 'size' => 5]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $products = $response['data']['list'] ?? [];
                
                if (!empty($products)) {
                    $sample = $products[0];
                    $this->info('✅ Fields available in Master Products endpoint:');
                    
                    $fields = [];
                    foreach ($sample as $key => $value) {
                        $type = gettype($value);
                        $preview = is_string($value) ? substr($value, 0, 20) : 
                                  (is_array($value) ? '[array]' : 
                                  (is_bool($value) ? ($value ? 'true' : 'false') : $value));
                        $fields[] = [$key, $type, $preview];
                    }
                    
                    $this->table(['Field Name', 'Type', 'Sample Value'], $fields);
                    
                    // Check if target SKU exists in master products
                    $found = false;
                    foreach ($products as $product) {
                        if (($product['masterSku'] ?? '') === $sku) {
                            $found = true;
                            $this->info("🎯 TARGET SKU '{$sku}' FOUND in Master Products:");
                            $this->line("   - Stock Quantity: " . ($product['stockQuantity'] ?? 'N/A'));
                            $this->line("   - Product Name: " . ($product['name'] ?? 'N/A'));
                            $this->line("   - Product ID: " . ($product['id'] ?? 'N/A'));
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $this->warn("❌ TARGET SKU '{$sku}' NOT FOUND in Master Products");
                        $this->line("   This could mean:");
                        $this->line("   - SKU doesn't exist in system");
                        $this->line("   - SKU is in different page (need deeper search)");
                        $this->line("   - SKU was deleted from product catalog");
                    }
                    
                    $this->warn('⚠️ MASTER PRODUCTS: Contains basic stock info');
                    $this->line('   - stockQuantity: Overall product stock');
                    $this->line('   - May not reflect real-time warehouse availability');
                } else {
                    $this->warn('⚠️ No master products found');
                }
            } else {
                $this->error('❌ Master Products endpoint failed: ' . ($response['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('💥 Exception: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function analyzeEndpoint4_WarehouseInventory($sku)
    {
        $this->info('📦 ENDPOINT 4: WAREHOUSE INVENTORY (PRIMARY FOR STOCK SYNC)');
        $this->line('📍 URL: POST /openapi/warehouse-inventory/v1/sku/list');
        $this->line('🎯 Purpose: Get real-time warehouse stock data');
        $this->line(str_repeat('-', 60));

        try {
            $response = $this->gineeClient->getWarehouseInventory(['page' => 0, 'size' => 5]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $items = $response['data']['content'] ?? [];
                
                if (!empty($items)) {
                    $sample = $items[0];
                    $this->info('✅ TOP-LEVEL Fields in Warehouse Inventory:');
                    
                    $topFields = [];
                    foreach ($sample as $key => $value) {
                        $type = gettype($value);
                        $preview = is_array($value) ? '[object]' : 
                                  (is_string($value) ? substr($value, 0, 20) : $value);
                        $topFields[] = [$key, $type, $preview];
                    }
                    
                    $this->table(['Field Name', 'Type', 'Preview'], $topFields);
                    
                    // Analyze masterVariation structure
                    if (isset($sample['masterVariation'])) {
                        $masterVariation = $sample['masterVariation'];
                        $this->info('📋 MASTER VARIATION Fields (Product Info):');
                        
                        $mvFields = [];
                        foreach ($masterVariation as $key => $value) {
                            $type = gettype($value);
                            $preview = is_array($value) ? '[array]' : 
                                      (is_string($value) ? substr($value, 0, 20) : $value);
                            $mvFields[] = [$key, $type, $preview];
                        }
                        
                        $this->table(['MV Field', 'Type', 'Sample'], array_slice($mvFields, 0, 8));
                    }
                    
                    // Analyze warehouseInventory structure  
                    if (isset($sample['warehouseInventory'])) {
                        $warehouseInventory = $sample['warehouseInventory'];
                        $this->info('🏭 WAREHOUSE INVENTORY Fields (STOCK DATA):');
                        
                        $wiFields = [];
                        foreach ($warehouseInventory as $key => $value) {
                            $type = gettype($value);
                            $stockIndicator = in_array(strtolower($key), ['stock', 'available', 'locked', 'warehouse']) ? '📊' : '';
                            $wiFields[] = [$stockIndicator . $key, $type, $value];
                        }
                        
                        $this->table(['📊 = Stock Field', 'Type', 'Value'], $wiFields);
                    }
                    
                    // Search for target SKU
                    $found = false;
                    foreach ($items as $item) {
                        $masterVariation = $item['masterVariation'] ?? [];
                        $itemSku = $masterVariation['masterSku'] ?? '';
                        
                        if ($itemSku === $sku) {
                            $found = true;
                            $warehouseInventory = $item['warehouseInventory'] ?? [];
                            
                            $this->info("🎯 TARGET SKU '{$sku}' FOUND in Warehouse Inventory:");
                            $this->line("   - Product Name: " . ($masterVariation['name'] ?? 'N/A'));
                            $this->line("   - Warehouse Stock: " . ($warehouseInventory['warehouseStock'] ?? 'N/A'));
                            $this->line("   - Available Stock: " . ($warehouseInventory['availableStock'] ?? 'N/A'));
                            $this->line("   - Locked Stock: " . ($warehouseInventory['lockedStock'] ?? 'N/A'));
                            $this->line("   - Spare Stock: " . ($warehouseInventory['spareStock'] ?? 'N/A'));
                            $this->line("   - Transport Stock: " . ($warehouseInventory['transportStock'] ?? 'N/A'));
                            $this->line("   - Last Updated: " . ($warehouseInventory['updateDatetime'] ?? 'N/A'));
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $this->warn("❌ TARGET SKU '{$sku}' NOT FOUND in Warehouse Inventory (page 1)");
                        $this->line("   This could mean:");
                        $this->line("   - SKU doesn't exist in warehouse");
                        $this->line("   - SKU is in different page (need bulk search)");
                        $this->line("   - SKU was removed from inventory");
                    }
                    
                    $this->info('🎯 WAREHOUSE INVENTORY: Real-time stock data');
                    $this->line('   ✅ warehouseStock: Physical stock in warehouse');
                    $this->line('   ✅ availableStock: Available for sale (most important)');
                    $this->line('   ✅ lockedStock: Reserved/locked stock');
                    $this->line('   ✅ spareStock: Spare/backup stock');
                    $this->line('   ✅ transportStock: In-transit stock');
                    
                } else {
                    $this->warn('⚠️ No warehouse inventory items found');
                }
            } else {
                $this->error('❌ Warehouse Inventory endpoint failed: ' . ($response['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('💥 Exception: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function showGineeOptimizationEndpoint($sku)
    {
        $this->info('🚀 GINEE OPTIMIZATION ENDPOINT USAGE');
        $this->line(str_repeat('=', 60));
        
        $this->warn('📋 PRIMARY ENDPOINT: Warehouse Inventory');
        $this->line('   OptimizedGineeStockSyncService uses:');
        $this->line('   📍 URL: POST /openapi/warehouse-inventory/v1/sku/list');
        $this->line('   🎯 Method: getBulkStockFromGinee()');
        $this->line('   💡 Strategy: Bulk fetch with pagination');
        $this->newLine();
        
        $this->info('🔍 Testing Ginee Optimization for your SKU...');
        try {
            $result = $this->optimizedService->getBulkStockFromGinee([$sku]);
            
            if ($result['success']) {
                if (isset($result['found_stock'][$sku])) {
                    $stockData = $result['found_stock'][$sku];
                    $this->info("✅ GINEE OPTIMIZATION FOUND YOUR SKU:");
                    $this->line("   - SKU: " . ($stockData['sku'] ?? 'N/A'));
                    $this->line("   - Product Name: " . ($stockData['product_name'] ?? 'N/A'));
                    $this->line("   - Warehouse Stock: " . ($stockData['warehouse_stock'] ?? 'N/A'));
                    $this->line("   - Available Stock: " . ($stockData['available_stock'] ?? 'N/A'));
                    $this->line("   - Total Stock: " . ($stockData['total_stock'] ?? 'N/A'));
                } else {
                    $this->error("❌ GINEE OPTIMIZATION: SKU '{$sku}' NOT FOUND");
                    $this->line("   Method used: Bulk warehouse inventory search");
                    $this->line("   Pages searched: " . ($result['stats']['pages_searched'] ?? 'Unknown'));
                    $this->line("   Total items checked: " . ($result['stats']['total_checked'] ?? 'Unknown'));
                }
            } else {
                $this->error("❌ GINEE OPTIMIZATION: Bulk search failed");
                $this->line("   Error: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('💥 Ginee Optimization Exception: ' . $e->getMessage());
        }
        
        $this->newLine();
        $this->warn('📋 SUMMARY:');
        $this->line('1. 🏪 Shops: Shop configuration (no stock data)');
        $this->line('2. 🏭 Warehouses: Warehouse settings (no stock data)');
        $this->line('3. 📋 Master Products: Product catalog (basic stock)');
        $this->line('4. 📦 Warehouse Inventory: Real-time stock (USED BY OPTIMIZATION)');
        $this->newLine();
        $this->info('🎯 Ginee Optimization uses ENDPOINT 4 (Warehouse Inventory) for stock sync');
    }
}