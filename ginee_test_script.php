<?php

/**
 * Ginee API Integration Test Script
 * Jalankan di PHP Artisan Tinker untuk mengecek integrasi API
 * 
 * Usage:
 * php artisan tinker
 * >> include 'ginee_test_script.php';
 */

echo "🔍 Testing Ginee API Integration\n";
echo "================================\n\n";

try {
    // 1. Check configuration
    echo "📋 Step 1: Checking Ginee Configuration...\n";
    $accessKey = config('services.ginee.access_key');
    $secretKey = config('services.ginee.secret_key');
    $baseUrl = config('services.ginee.base') ?? 'https://api.ginee.com';
    $warehouseId = config('services.ginee.warehouse_id');
    
    if (!$accessKey || !$secretKey) {
        echo "❌ ERROR: Ginee credentials not configured!\n";
        echo "   Please set GINEE_ACCESS_KEY and GINEE_SECRET_KEY in .env\n";
        exit;
    }
    
    echo "✅ Access Key: " . substr($accessKey, 0, 8) . "...\n";
    echo "✅ Secret Key: " . substr($secretKey, 0, 8) . "...\n";
    echo "✅ Base URL: $baseUrl\n";
    echo "✅ Warehouse ID: " . ($warehouseId ?? 'Default') . "\n\n";

    // 2. Initialize Ginee Client
    echo "📦 Step 2: Initializing Ginee Client...\n";
    $ginee = new App\Services\GineeClient();
    echo "✅ Ginee Client initialized successfully\n\n";

    // 3. Test basic connectivity
    echo "🔌 Step 3: Testing Basic Connectivity...\n";
    $shopsResult = $ginee->getShops(['page' => 0, 'size' => 2]);
    if (($shopsResult['code'] ?? null) === 'SUCCESS') {
        echo "✅ Basic connectivity test passed\n";
        $shopCount = count($shopsResult['data']['list'] ?? []);
        echo "   Found $shopCount shops\n\n";
    } else {
        echo "❌ Basic connectivity failed\n";
        echo "   Error: " . ($shopsResult['message'] ?? 'Unknown error') . "\n\n";
        return;
    }

    // 4. Test AdjustInventory endpoint (Stock Update)
    echo "📊 Step 4: Testing AdjustInventory Endpoint...\n";
    echo "   Endpoint: POST /openapi/warehouse-inventory/v1/product/stock/update\n";
    
    // Get a real SKU first
    $productResult = $ginee->getMasterProducts(['page' => 0, 'size' => 5]);
    $testSku = null;
    
    if (($productResult['code'] ?? null) === 'SUCCESS') {
        $products = $productResult['data']['list'] ?? [];
        if (!empty($products)) {
            $testSku = $products[0]['masterSku'] ?? null;
        }
    }
    
    if (!$testSku) {
        echo "⚠️  No test SKU found, using default 'BOX'\n";
        $testSku = 'BOX';
    } else {
        echo "   Using test SKU: $testSku\n";
    }
    
    // Test with quantity 0 to check current stock without changing it
    $stockUpdate = [
        'masterSku' => $testSku,
        'quantity' => 0,  // Using 0 to check current stock
        'remark' => 'API integration test'
    ];
    
    $adjustResult = $ginee->updateStock([$stockUpdate]);
    
    if (($adjustResult['code'] ?? null) === 'SUCCESS') {
        echo "✅ AdjustInventory endpoint working!\n";
        $stockList = $adjustResult['data']['stockList'] ?? [];
        if (!empty($stockList)) {
            $stockInfo = $stockList[0];
            echo "   SKU: " . ($stockInfo['masterSku'] ?? 'N/A') . "\n";
            echo "   Product Name: " . ($stockInfo['masterProductName'] ?? 'N/A') . "\n";
            echo "   Warehouse Stock: " . ($stockInfo['warehouseStock'] ?? 'N/A') . "\n";
            echo "   Available Stock: " . ($stockInfo['availableStock'] ?? 'N/A') . "\n";
            echo "   Last Updated: " . ($stockInfo['updateDatetime'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ AdjustInventory endpoint failed\n";
        echo "   Error: " . ($adjustResult['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";

    // 5. Check if UpdateAvailableStock endpoint is implemented
    echo "📈 Step 5: Checking UpdateAvailableStock Implementation...\n";
    echo "   Endpoint: POST /openapi/v1/oms/stock/available-stock/update\n";
    
    // Check if method exists in GineeClient
    $gineeReflection = new ReflectionClass(App\Services\GineeClient::class);
    $hasUpdateAvailableStock = $gineeReflection->hasMethod('updateAvailableStock');
    
    if ($hasUpdateAvailableStock) {
        echo "✅ updateAvailableStock method found in GineeClient\n";
        
        // Test the method if it exists
        try {
            $availableStockUpdate = [
                'masterSku' => $testSku,
                'action' => 'INCREASE',
                'quantity' => 0,  // Test with 0 increase
                'warehouseId' => $warehouseId,
                'remark' => 'API integration test'
            ];
            
            $availableStockResult = $ginee->updateAvailableStock([$availableStockUpdate]);
            
            if (($availableStockResult['code'] ?? null) === 'SUCCESS') {
                echo "✅ UpdateAvailableStock endpoint working!\n";
                $successList = $availableStockResult['data']['successList'] ?? [];
                $failedList = $availableStockResult['data']['failedList'] ?? [];
                echo "   Successful updates: " . count($successList) . "\n";
                echo "   Failed updates: " . count($failedList) . "\n";
            } else {
                echo "❌ UpdateAvailableStock endpoint failed\n";
                echo "   Error: " . ($availableStockResult['message'] ?? 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Error testing UpdateAvailableStock: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ updateAvailableStock method NOT found in GineeClient\n";
        echo "   This endpoint needs to be implemented\n";
    }
    echo "\n";

    // 6. Test comprehensive stock sync
    echo "🔄 Step 6: Testing Comprehensive Stock Sync...\n";
    
    // Test getting warehouse inventory
    $inventoryResult = $ginee->getWarehouseInventory(['page' => 0, 'size' => 3]);
    if (($inventoryResult['code'] ?? null) === 'SUCCESS') {
        echo "✅ Warehouse inventory retrieval working\n";
        $inventory = $inventoryResult['data']['content'] ?? [];
        echo "   Retrieved " . count($inventory) . " inventory items\n";
        
        if (!empty($inventory)) {
            $sample = $inventory[0];
            echo "   Sample SKU: " . ($sample['masterVariation']['masterSku'] ?? 'N/A') . "\n";
            echo "   Available Stock: " . ($sample['availableStock'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Warehouse inventory retrieval failed\n";
        echo "   Error: " . ($inventoryResult['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";

    // 7. Final summary
    echo "📋 Integration Test Summary:\n";
    echo "===========================\n";
    echo "✅ Configuration: OK\n";
    echo "✅ Basic Connectivity: " . (($shopsResult['code'] ?? null) === 'SUCCESS' ? 'OK' : 'FAILED') . "\n";
    echo "✅ AdjustInventory Endpoint: " . (($adjustResult['code'] ?? null) === 'SUCCESS' ? 'OK' : 'FAILED') . "\n";
    echo ($hasUpdateAvailableStock ? "✅" : "❌") . " UpdateAvailableStock Method: " . ($hasUpdateAvailableStock ? 'IMPLEMENTED' : 'NOT IMPLEMENTED') . "\n";
    echo "✅ Warehouse Inventory: " . (($inventoryResult['code'] ?? null) === 'SUCCESS' ? 'OK' : 'FAILED') . "\n";
    
    echo "\n🎯 Conclusion: ";
    if (($shopsResult['code'] ?? null) === 'SUCCESS' && ($adjustResult['code'] ?? null) === 'SUCCESS') {
        echo "Ginee API integration is working! ✅\n";
        
        if (!$hasUpdateAvailableStock) {
            echo "\n💡 Recommendation: Implement UpdateAvailableStock method for complete functionality\n";
        }
    } else {
        echo "Ginee API integration has issues ❌\n";
        echo "   Please check the error messages above\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🔚 Test completed.\n";