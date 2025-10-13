<?php

/**
 * FIXED TEST SCRIPT FOR ALL 4 GINEE STOCK ENDPOINTS
 * 
 * Save as: ginee_four_endpoints_test_fixed.php
 * Run with: php artisan tinker >> include 'ginee_four_endpoints_test_fixed.php';
 */

echo "🚀 COMPLETE Ginee Stock Management API Test\n";
echo "==========================================\n\n";

try {
    // Initialize client
    $ginee = new App\Services\GineeClient();
    echo "✅ Ginee Client initialized\n\n";

    // Test all four endpoints
    echo "🧪 Testing ALL FOUR Stock Management Endpoints...\n";
    echo "================================================\n\n";

    $allTests = $ginee->testAllStockEndpoints();
    
    $testCode = isset($allTests['code']) ? $allTests['code'] : null;
    if ($testCode === 'SUCCESS') {
        $results = isset($allTests['data']['results']) ? $allTests['data']['results'] : array();
        $summary = isset($allTests['data']['endpoint_summary']) ? $allTests['data']['endpoint_summary'] : array();
        $warehouseId = isset($allTests['data']['warehouse_id_used']) ? $allTests['data']['warehouse_id_used'] : 'Unknown';
        
        echo "🏗️  Using Warehouse ID: $warehouseId\n\n";
        
        // Test Results Summary
        echo "📊 ENDPOINT TEST RESULTS:\n";
        echo "========================\n";
        
        $endpoints = array(
            'adjust_inventory' => array('name' => 'AdjustInventory (Warehouse Stock)', 'endpoint' => '/openapi/warehouse-inventory/v1/product/stock/update'),
            'update_available_stock' => array('name' => 'UpdateAvailableStock', 'endpoint' => '/openapi/v1/oms/stock/available-stock/update'),
            'update_spare_stock' => array('name' => 'UpdateSpareStock', 'endpoint' => '/openapi/v1/oms/stock/spare-stock/update'),
            'warehouse_inventory' => array('name' => 'Warehouse Inventory (Read)', 'endpoint' => '/openapi/warehouse-inventory/v1/sku/list')
        );
        
        $totalSuccess = 0;
        foreach ($endpoints as $key => $info) {
            $status = isset($summary[$key]) ? $summary[$key] : false;
            $icon = $status ? "✅" : "❌";
            $statusText = $status ? "WORKING" : "FAILED";
            
            if ($status) $totalSuccess++;
            
            echo "$icon {$info['name']}: $statusText\n";
            echo "   Endpoint: {$info['endpoint']}\n";
            
            // Show detailed result if available
            if (isset($results[$key])) {
                $result = $results[$key];
                $code = isset($result['code']) ? $result['code'] : 'Unknown';
                $message = isset($result['message']) ? $result['message'] : 'No message';
                echo "   Response: $code - $message\n";
                
                // Show specific data for each endpoint
                if ($status && isset($result['data'])) {
                    switch ($key) {
                        case 'adjust_inventory':
                            $stockList = isset($result['data']['stockList']) ? $result['data']['stockList'] : array();
                            if (!empty($stockList)) {
                                $stock = $stockList[0];
                                $masterSku = isset($stock['masterSku']) ? $stock['masterSku'] : 'N/A';
                                $warehouseStock = isset($stock['warehouseStock']) ? $stock['warehouseStock'] : 'N/A';
                                $availableStock = isset($stock['availableStock']) ? $stock['availableStock'] : 'N/A';
                                echo "   Stock Data: SKU $masterSku | Warehouse: $warehouseStock | Available: $availableStock\n";
                            }
                            break;
                            
                        case 'update_available_stock':
                            $successList = isset($result['data']['successList']) ? $result['data']['successList'] : array();
                            $failedList = isset($result['data']['failedList']) ? $result['data']['failedList'] : array();
                            echo "   Results: " . count($successList) . " successful, " . count($failedList) . " failed\n";
                            break;
                            
                        case 'update_spare_stock':
                            $successList = isset($result['data']['successList']) ? $result['data']['successList'] : array();
                            $failedList = isset($result['data']['failedList']) ? $result['data']['failedList'] : array();
                            echo "   Results: " . count($successList) . " successful, " . count($failedList) . " failed\n";
                            if (!empty($successList)) {
                                $spare = $successList[0];
                                $spareStock = isset($spare['spareStock']) ? $spare['spareStock'] : 'N/A';
                                $availableStock = isset($spare['availableStock']) ? $spare['availableStock'] : 'N/A';
                                echo "   Spare Stock: $spareStock | Available: $availableStock\n";
                            }
                            break;
                            
                        case 'warehouse_inventory':
                            $content = isset($result['data']['content']) ? $result['data']['content'] : array();
                            echo "   Retrieved: " . count($content) . " inventory items\n";
                            if (!empty($content)) {
                                $sample = $content[0];
                                $sku = isset($sample['masterVariation']['masterSku']) ? $sample['masterVariation']['masterSku'] : 'N/A';
                                $available = isset($sample['availableStock']) ? $sample['availableStock'] : 'N/A';
                                echo "   Sample: SKU $sku | Available: $available\n";
                            }
                            break;
                    }
                }
            }
            echo "\n";
        }
        
        // Overall Summary
        echo "🎯 OVERALL SUMMARY:\n";
        echo "==================\n";
        echo "Working Endpoints: $totalSuccess/4\n";
        echo "Success Rate: " . round(($totalSuccess/4) * 100) . "%\n\n";
        
        if ($totalSuccess === 4) {
            echo "🚀 PERFECT! All 4 stock management endpoints are working!\n";
            echo "   Your Ginee integration is 100% complete and production-ready.\n\n";
            
            // Demo usage examples
            echo "💡 USAGE EXAMPLES:\n";
            echo "=================\n";
            echo "// 1. Set absolute warehouse stock\n";
            echo "\$stockUpdate = \$ginee->createStockUpdate('SKU001', 100);\n";
            echo "\$result = \$ginee->updateStock([\$stockUpdate]);\n\n";
            
            echo "// 2. Increase available stock\n";
            echo "\$availableUpdate = \$ginee->createAvailableStockUpdate('SKU001', 'INCREASE', 10);\n";
            echo "\$result = \$ginee->updateAvailableStock([\$availableUpdate]);\n\n";
            
            echo "// 3. Set spare stock\n";
            echo "\$spareUpdate = \$ginee->createSpareStockUpdate('SKU001', 'OVER_WRITE', 20);\n";
            echo "\$result = \$ginee->updateSpareStock([\$spareUpdate]);\n\n";
            
            echo "// 4. Get current inventory\n";
            echo "\$inventory = \$ginee->getWarehouseInventory(['page' => 0, 'size' => 50]);\n\n";
            
            echo "🔄 BUSINESS SCENARIOS:\n";
            echo "=====================\n";
            echo "// Setup new product\n";
            echo "\$ginee->setupNewProductStock('NEW_SKU', 100, 20); // total: 100, spare: 20\n\n";
            
            echo "// Process sale\n";
            echo "\$ginee->processSale('SKU001', 5); // sold 5 items\n\n";
            
            echo "// Restock from spare to available\n";
            echo "\$ginee->restockFromSpare('SKU001', 10); // move 10 from spare to available\n\n";
            
        } else {
            echo "⚠️  Some endpoints need attention:\n";
            foreach ($endpoints as $key => $info) {
                $status = isset($summary[$key]) ? $summary[$key] : false;
                if (!$status) {
                    echo "   - Fix: {$info['name']}\n";
                }
            }
        }
        
        // Stock Type Explanations
        echo "📚 STOCK TYPES EXPLANATION:\n";
        echo "==========================\n";
        echo "🏭 Warehouse Stock: Total physical inventory in warehouse\n";
        echo "🛒 Available Stock: Stock available for sale to customers\n";
        echo "🎯 Spare Stock: Reserved stock for emergencies/backup\n";
        echo "🔒 Locked Stock: Stock reserved for pending orders\n";
        echo "🚛 Transport Stock: Stock in transit/shipping\n";
        echo "🎁 Promotion Stock: Stock allocated for promotions\n\n";
        
    } else {
        $errorMessage = isset($allTests['message']) ? $allTests['message'] : 'Unknown error';
        echo "❌ Failed to test endpoints: $errorMessage\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "🔚 Complete test finished.\n";