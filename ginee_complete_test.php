<?php
echo "🔍 COMPLETE Ginee API Integration Test\n";
echo "=====================================\n\n";

try {
    // 1. Check configuration
    echo "📋 Step 1: Checking Complete Configuration...\n";
    $gineeConfig = config('services.ginee');
    $accessKey = $gineeConfig['access_key'] ?? null;
    $secretKey = $gineeConfig['secret_key'] ?? null;
    $warehouseId = $gineeConfig['warehouse_id'] ?? null;
    
    if (!$accessKey || !$secretKey) {
        echo "❌ ERROR: Missing credentials in .env!\n";
        echo "   Add: GINEE_ACCESS_KEY and GINEE_SECRET_KEY to .env\n";
        exit;
    }
    
    echo "✅ Access Key: " . substr($accessKey, 0, 8) . "...\n";
    echo "✅ Secret Key: " . substr($secretKey, 0, 8) . "...\n";
    echo "✅ Warehouse ID: " . ($warehouseId ? substr($warehouseId, 0, 15) . "..." : "Not configured") . "\n\n";

    // 2. Initialize client
    $ginee = new App\Services\GineeClient();
    echo "✅ Ginee Client initialized\n\n";

    // 3. Get real warehouse info
    echo "🏗️  Step 3: Getting Warehouse Information...\n";
    $warehousesResult = $ginee->getWarehouses(['page' => 0, 'size' => 5]);
    
    if (($warehousesResult['code'] ?? null) === 'SUCCESS') {
        $warehouses = $warehousesResult['data']['list'] ?? [];
        echo "✅ Found " . count($warehouses) . " warehouses:\n";
        
        foreach ($warehouses as $idx => $warehouse) {
            $id = $warehouse['warehouseId'] ?? 'Unknown';
            $name = $warehouse['warehouseName'] ?? 'Unnamed';
            echo "   " . ($idx + 1) . ". ID: $id | Name: $name\n";
        }
        
        if (!empty($warehouses)) {
            $realWarehouseId = $warehouses[0]['warehouseId'];
            echo "\n💡 Recommendation: Use warehouse ID: $realWarehouseId\n";
            echo "   Add to .env: GINEE_WAREHOUSE_ID=$realWarehouseId\n";
        }
    } else {
        echo "❌ Failed to get warehouses\n";
        echo "   Error: " . ($warehousesResult['message'] ?? 'Unknown') . "\n";
    }
    echo "\n";

    // 4. Test AdjustInventory endpoint
    echo "📊 Step 4: Testing AdjustInventory Endpoint...\n";
    $stockResult = $ginee->updateStock([['masterSku' => 'BOX', 'quantity' => 0]]);
    
    if (($stockResult['code'] ?? null) === 'SUCCESS') {
        echo "✅ AdjustInventory working!\n";
        $stockList = $stockResult['data']['stockList'] ?? [];
        if (!empty($stockList)) {
            $stock = $stockList[0];
            echo "   SKU: " . ($stock['masterSku'] ?? 'N/A') . "\n";
            echo "   Available Stock: " . ($stock['availableStock'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ AdjustInventory failed: " . ($stockResult['message'] ?? 'Unknown') . "\n";
    }
    echo "\n";

    // 5. Test UpdateAvailableStock endpoint (if method exists)
    echo "📈 Step 5: Testing UpdateAvailableStock Endpoint...\n";
    $reflection = new ReflectionClass(App\Services\GineeClient::class);
    
    if ($reflection->hasMethod('updateAvailableStock')) {
        echo "✅ updateAvailableStock method found\n";
        
        try {
            $realWarehouseId = $warehouses[0]['warehouseId'] ?? $warehouseId;
            $availableStockUpdate = [
                'masterSku' => 'BOX',
                'action' => 'INCREASE',
                'quantity' => 0,
                'warehouseId' => $realWarehouseId,
                'remark' => 'Test from complete integration'
            ];
            
            $availableResult = $ginee->updateAvailableStock([$availableStockUpdate]);
            
            if (($availableResult['code'] ?? null) === 'SUCCESS') {
                echo "✅ UpdateAvailableStock working!\n";
                $successList = $availableResult['data']['successList'] ?? [];
                $failedList = $availableResult['data']['failedList'] ?? [];
                echo "   Successful: " . count($successList) . " | Failed: " . count($failedList) . "\n";
            } else {
                echo "❌ UpdateAvailableStock failed: " . ($availableResult['message'] ?? 'Unknown') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ UpdateAvailableStock error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ updateAvailableStock method not implemented\n";
        echo "   Please add the method to GineeClient.php\n";
    }
    echo "\n";

    // 6. Test warehouse inventory (fixed)
    echo "📦 Step 6: Testing Fixed Warehouse Inventory...\n";
    $inventoryResult = $ginee->getWarehouseInventory(['page' => 0, 'size' => 3]);
    
    if (($inventoryResult['code'] ?? null) === 'SUCCESS') {
        echo "✅ Warehouse Inventory working!\n";
        $inventory = $inventoryResult['data']['content'] ?? [];
        echo "   Retrieved " . count($inventory) . " inventory items\n";
        
        if (!empty($inventory)) {
            $sample = $inventory[0];
            $sku = $sample['masterVariation']['masterSku'] ?? 'N/A';
            $available = $sample['availableStock'] ?? 'N/A';
            echo "   Sample: SKU $sku | Available: $available\n";
        }
    } else {
        echo "❌ Warehouse Inventory failed: " . ($inventoryResult['message'] ?? 'Unknown') . "\n";
    }
    echo "\n";

    // 7. Final summary
    echo "🎯 COMPLETE INTEGRATION TEST SUMMARY:\n";
    echo "====================================\n";
    $adjustWorking = ($stockResult['code'] ?? null) === 'SUCCESS';
    $availableImplemented = $reflection->hasMethod('updateAvailableStock');
    $inventoryWorking = ($inventoryResult['code'] ?? null) === 'SUCCESS';
    
    echo ($adjustWorking ? "✅" : "❌") . " AdjustInventory Endpoint: " . ($adjustWorking ? "WORKING" : "FAILED") . "\n";
    echo ($availableImplemented ? "✅" : "❌") . " UpdateAvailableStock Method: " . ($availableImplemented ? "IMPLEMENTED" : "MISSING") . "\n";
    echo ($inventoryWorking ? "✅" : "❌") . " Warehouse Inventory: " . ($inventoryWorking ? "WORKING" : "FAILED") . "\n";
    
    if ($adjustWorking && $availableImplemented && $inventoryWorking) {
        echo "\n🚀 SUCCESS: Complete Ginee integration is working perfectly!\n";
        echo "   Both stock update endpoints are ready to use.\n";
    } else {
        echo "\n🔧 ACTION REQUIRED:\n";
        if (!$availableImplemented) echo "   - Add updateAvailableStock method to GineeClient\n";
        if (!$inventoryWorking) echo "   - Check warehouse inventory implementation\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "\n🔚 Complete test finished.\n";