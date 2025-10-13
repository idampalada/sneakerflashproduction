<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GineeClient;

class DebugFieldMappingCommand extends Command
{
    protected $signature = 'ginee:debug-field-mapping {sku}';
    protected $description = 'Debug exact field mapping for specific SKU to find correct stock values';

    private $gineeClient;

    public function handle()
    {
        $targetSku = $this->argument('sku');
        $this->gineeClient = new GineeClient();

        $this->info('🔍 DEBUGGING EXACT FIELD MAPPING FOR SKU');
        $this->info('🎯 Target SKU: ' . $targetSku);
        $this->info('📊 Expected from Dashboard: Available Stock = 1');
        $this->info('='.str_repeat('=', 60));
        $this->newLine();

        $this->searchAllPages($targetSku);

        return Command::SUCCESS;
    }

    private function searchAllPages($targetSku)
    {
        $this->info('🔍 COMPREHENSIVE SEARCH - ALL PAGES');
        $this->line(str_repeat('-', 60));

        $found = false;
        $page = 0;
        $maxPages = 20; // Search more pages
        $pageSize = 50;

        while ($page < $maxPages && !$found) {
            $this->line("📦 Searching page {$page} (size: {$pageSize})...");

            try {
                $response = $this->gineeClient->getWarehouseInventory([
                    'page' => $page,
                    'size' => $pageSize
                ]);

                if (($response['code'] ?? null) !== 'SUCCESS') {
                    $this->error("❌ API failed on page {$page}: " . ($response['message'] ?? 'Unknown error'));
                    break;
                }

                $items = $response['data']['content'] ?? [];
                
                if (empty($items)) {
                    $this->warn("⚠️ No more items on page {$page} - reached end");
                    break;
                }

                $this->line("   📋 Found " . count($items) . " items on page {$page}");

                // Search for target SKU in this page
                foreach ($items as $index => $item) {
                    $masterVariation = $item['masterVariation'] ?? [];
                    $itemSku = $masterVariation['masterSku'] ?? '';
                    
                    if ($itemSku === $targetSku) {
                        $found = true;
                        $this->info("🎯 FOUND TARGET SKU ON PAGE {$page}, ITEM {$index}!");
                        $this->newLine();
                        
                        // Display complete item structure
                        $this->displayCompleteItemStructure($item, $targetSku);
                        break 2; // Break both loops
                    }
                }

                $page++;
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second

            } catch (\Exception $e) {
                $this->error("💥 Exception on page {$page}: " . $e->getMessage());
                break;
            }
        }

        if (!$found) {
            $this->error("❌ SKU '{$targetSku}' NOT FOUND in {$page} pages");
            $this->warn("Possible reasons:");
            $this->line("1. SKU exists but in different warehouse");
            $this->line("2. SKU was deleted/disabled after dashboard check");
            $this->line("3. API returns different data than dashboard");
            $this->line("4. Need to check different endpoint");
            $this->newLine();
            
            // Try alternative search methods
            $this->tryAlternativeSearchMethods($targetSku);
        }
    }

    private function displayCompleteItemStructure($item, $targetSku)
    {
        $this->info('📋 COMPLETE ITEM STRUCTURE ANALYSIS');
        $this->line(str_repeat('=', 60));
        
        // 1. Top-level structure
        $this->warn('🔧 TOP-LEVEL FIELDS:');
        foreach ($item as $key => $value) {
            $type = gettype($value);
            $preview = is_array($value) ? '[' . count($value) . ' items]' : 
                      (is_string($value) ? substr($value, 0, 30) : $value);
            $this->line("   {$key}: ({$type}) {$preview}");
        }
        $this->newLine();

        // 2. Master Variation details
        if (isset($item['masterVariation'])) {
            $masterVariation = $item['masterVariation'];
            $this->warn('📦 MASTER VARIATION FIELDS:');
            foreach ($masterVariation as $key => $value) {
                $type = gettype($value);
                $preview = is_array($value) ? '[' . count($value) . ' items]' : 
                          (is_string($value) ? substr($value, 0, 40) : $value);
                $this->line("   MV.{$key}: ({$type}) {$preview}");
            }
            $this->newLine();
        }

        // 3. Warehouse Inventory details (MOST IMPORTANT)
        if (isset($item['warehouseInventory'])) {
            $warehouseInventory = $item['warehouseInventory'];
            $this->warn('🏭 WAREHOUSE INVENTORY FIELDS (STOCK DATA):');
            
            $stockFields = [];
            $otherFields = [];
            
            foreach ($warehouseInventory as $key => $value) {
                $type = gettype($value);
                $isStockField = stripos($key, 'stock') !== false || 
                               stripos($key, 'available') !== false ||
                               in_array(strtolower($key), ['quantity', 'count']);
                
                if ($isStockField) {
                    $stockFields[] = "   📊 WI.{$key}: ({$type}) {$value} ← STOCK FIELD";
                } else {
                    $preview = is_string($value) ? substr($value, 0, 30) : $value;
                    $otherFields[] = "   WI.{$key}: ({$type}) {$preview}";
                }
            }
            
            // Display stock fields first (most important)
            foreach ($stockFields as $field) {
                $this->line($field);
            }
            $this->newLine();
            
            // Display other fields
            $this->warn('🔧 OTHER WAREHOUSE FIELDS:');
            foreach ($otherFields as $field) {
                $this->line($field);
            }
            $this->newLine();
        }

        // 4. Warehouse details
        if (isset($item['warehouse'])) {
            $warehouse = $item['warehouse'];
            $this->warn('🏪 WAREHOUSE DETAILS:');
            foreach ($warehouse as $key => $value) {
                $type = gettype($value);
                $preview = is_string($value) ? substr($value, 0, 30) : $value;
                $this->line("   WH.{$key}: ({$type}) {$preview}");
            }
            $this->newLine();
        }

        // 5. Analysis and recommendations
        $this->analyzeStockFields($item, $targetSku);
    }

    private function analyzeStockFields($item, $targetSku)
    {
        $this->info('🎯 STOCK FIELD ANALYSIS & MAPPING RECOMMENDATIONS');
        $this->line(str_repeat('=', 60));

        $warehouseInventory = $item['warehouseInventory'] ?? [];
        $masterVariation = $item['masterVariation'] ?? [];

        // Extract all possible stock values
        $stockData = [
            'masterVariation.stockQuantity' => $masterVariation['stockQuantity'] ?? 'N/A',
            'warehouseInventory.warehouseStock' => $warehouseInventory['warehouseStock'] ?? 'N/A',
            'warehouseInventory.availableStock' => $warehouseInventory['availableStock'] ?? 'N/A',
            'warehouseInventory.lockedStock' => $warehouseInventory['lockedStock'] ?? 'N/A',
            'warehouseInventory.spareStock' => $warehouseInventory['spareStock'] ?? 'N/A',
            'warehouseInventory.transportStock' => $warehouseInventory['transportStock'] ?? 'N/A',
            'warehouseInventory.promotionStock' => $warehouseInventory['promotionStock'] ?? 'N/A',
            'warehouseInventory.safetyStock' => $warehouseInventory['safetyStock'] ?? 'N/A',
        ];

        $this->warn('📊 ALL STOCK VALUES COMPARISON:');
        $this->line('Dashboard shows: Available Stock = 1');
        $this->newLine();
        
        $stockTable = [];
        foreach ($stockData as $field => $value) {
            $match = $value == 1 ? '🎯 MATCH!' : ($value == 0 ? '❌ Zero' : '⚠️ Different');
            $stockTable[] = [$field, $value, $match];
        }
        
        $this->table(['Field Path', 'API Value', 'vs Dashboard'], $stockTable);

        // Find the correct field
        $correctFields = [];
        foreach ($stockData as $field => $value) {
            if ($value == 1) {
                $correctFields[] = $field;
            }
        }

        if (!empty($correctFields)) {
            $this->info('🎯 CORRECT FIELD MAPPING FOUND:');
            foreach ($correctFields as $field) {
                $this->line("   ✅ Use: {$field} = {$stockData[$field]}");
            }
            $this->newLine();
            
            $this->generateFixedCode($correctFields);
        } else {
            $this->error('❌ NO MATCHING FIELD FOUND!');
            $this->warn('Possible issues:');
            $this->line('1. API data is stale/cached');
            $this->line('2. Different warehouse being queried');
            $this->line('3. Dashboard shows different view than API');
            $this->line('4. Real-time vs batch update timing');
        }
    }

    private function generateFixedCode($correctFields)
    {
        $this->info('🛠️ FIXED CODE RECOMMENDATIONS:');
        $this->line(str_repeat('=', 60));

        foreach ($correctFields as $field) {
            if (strpos($field, 'warehouseInventory.') === 0) {
                $apiField = str_replace('warehouseInventory.', '', $field);
                $this->warn("Fix for OptimizedGineeStockSyncService:");
                $this->line("// In getBulkStockFromGinee() method:");
                $this->line("'available_stock' => \$warehouseInventory['{$apiField}'] ?? 0,");
                $this->line("'warehouse_stock' => \$warehouseInventory['{$apiField}'] ?? 0,");
                $this->newLine();
                
                $this->warn("Fix for GineeStockSyncService:");
                $this->line("// In updateLocalProductStock() method:");
                $this->line("\$newStock = \$gineeStockData['{$apiField}'] ?? 0;");
                $this->newLine();
            }
        }

        $this->info('💡 IMMEDIATE TEST COMMAND:');
        $this->line("After applying fix, test with:");
        $this->line("php artisan ginee:debug-optimization {$this->argument('sku')}");
    }

    private function tryAlternativeSearchMethods($targetSku)
    {
        $this->info('🔄 TRYING ALTERNATIVE SEARCH METHODS');
        $this->line(str_repeat('-', 60));

        // Method 1: Search in Master Products
        $this->line('📋 Method 1: Searching in Master Products...');
        try {
            $response = $this->gineeClient->getMasterProducts(['page' => 0, 'size' => 100]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $products = $response['data']['list'] ?? [];
                $found = false;
                
                foreach ($products as $product) {
                    if (($product['masterSku'] ?? '') === $targetSku) {
                        $found = true;
                        $this->info("✅ Found in Master Products:");
                        $this->line("   SKU: " . ($product['masterSku'] ?? 'N/A'));
                        $this->line("   Name: " . ($product['name'] ?? 'N/A'));
                        $this->line("   Stock Quantity: " . ($product['stockQuantity'] ?? 'N/A'));
                        break;
                    }
                }
                
                if (!$found) {
                    $this->warn("❌ Not found in Master Products either");
                }
            } else {
                $this->error("❌ Master Products API failed");
            }
        } catch (\Exception $e) {
            $this->error("💥 Master Products exception: " . $e->getMessage());
        }

        $this->newLine();

        // Method 2: Check warehouse status
        $this->line('🏭 Method 2: Checking Warehouse Status...');
        try {
            $response = $this->gineeClient->getWarehouses(['page' => 0, 'size' => 10]);
            
            if (($response['code'] ?? null) === 'SUCCESS') {
                $warehouses = $response['data']['content'] ?? [];
                
                $this->info("📊 Available Warehouses:");
                foreach ($warehouses as $warehouse) {
                    $status = $warehouse['status'] ?? 'Unknown';
                    $name = $warehouse['name'] ?? 'Unknown';
                    $id = $warehouse['id'] ?? 'Unknown';
                    $icon = $status === 'ENABLE' ? '✅' : '❌';
                    
                    $this->line("   {$icon} {$name} ({$id}) - Status: {$status}");
                }
            }
        } catch (\Exception $e) {
            $this->error("💥 Warehouses exception: " . $e->getMessage());
        }
    }
}