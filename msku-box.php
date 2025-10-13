<?php
/*
=============================================================================
CEK STOCK SKU "BOX" - STANDALONE PHP FILE
=============================================================================

Simpan file ini sebagai: msku-box.php
Jalankan dengan: php msku-box.php

File ini HANYA READ/CEK stock SKU BOX - TIDAK mengubah apapun!
=============================================================================
*/

// Bootstrap Laravel Application
require_once __DIR__ . '/vendor/autoload.php';

// Import required classes
use App\Services\GineeClient;
use App\Models\Product;

// Ensure we're in the correct directory
if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
    echo "❌ ERROR: Laravel bootstrap/app.php not found!\n";
    echo "   Pastikan file ini berada di root directory Laravel Anda\n";
    exit(1);
}

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 CEK STOCK SKU BOX - READ ONLY\n";
echo "================================\n";
echo "⚠️  SAFE MODE: Hanya membaca data, tidak mengubah apapun!\n\n";

// Test Laravel bootstrap
try {
    $testConfig = config('app.name');
    echo "✅ Laravel ready: {$testConfig}\n\n";
} catch (Exception $e) {
    echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // ============= INISIALISASI GINEE CLIENT =============
    echo "=== GINEE API CONNECTION ===\n";
    $ginee = new GineeClient();
    echo "✅ Ginee Client initialized\n";
    
    $config = config('services.ginee');
    echo "📡 API Base: " . ($config['base'] ?? 'Not set') . "\n";
    echo "🔑 Access Key: " . substr($config['access_key'] ?? 'Not set', 0, 8) . "...\n\n";

    // ============= SEARCH SKU BOX - METHOD 1 =============
    echo "=== METHOD 1: WAREHOUSE INVENTORY SEARCH ===\n";
    
    $boxFound = false;
    $boxStockData = null;
    $maxPages = 10; // Search up to 10 pages
    
    for ($page = 0; $page < $maxPages; $page++) {
        echo "🔍 Searching page {$page}...";
        
        $inventory = $ginee->getWarehouseInventory(['page' => $page, 'size' => 100]);
        
        if (($inventory['code'] ?? null) === 'SUCCESS') {
            $inventoryList = $inventory['data']['content'] ?? [];
            
            if (empty($inventoryList)) {
                echo " No more items.\n";
                break;
            }
            
            echo " Found " . count($inventoryList) . " items.";
            
            foreach ($inventoryList as $item) {
                $masterVar = $item['masterVariation'] ?? [];
                $masterSku = $masterVar['masterSku'] ?? '';
                
                if (strtoupper($masterSku) === 'BOX' || $masterSku === 'BOX') {
                    $boxFound = true;
                    $boxStockData = $item;
                    echo " ✅ FOUND BOX!\n";
                    break 2; // Break both loops
                }
            }
            
            echo " BOX not found.\n";
        } else {
            echo " ❌ API Error: " . ($inventory['message'] ?? 'Unknown') . "\n";
            break;
        }
    }

    // ============= DISPLAY BOX STOCK RESULTS =============
    if ($boxFound && $boxStockData) {
        $masterVar = $boxStockData['masterVariation'] ?? [];
        
        echo "\n🎉 SUCCESS! SKU BOX DITEMUKAN\n";
        echo "════════════════════════════════════════════\n";
        echo "📦 Product Name: " . ($masterVar['name'] ?? 'N/A') . "\n";
        echo "🏷️  Master SKU: " . ($masterVar['masterSku'] ?? 'N/A') . "\n";
        echo "🏪 Warehouse ID: " . ($boxStockData['warehouseId'] ?? 'N/A') . "\n";
        echo "\n📊 STOCK DETAILS:\n";
        echo "   Warehouse Stock: " . ($boxStockData['warehouseStock'] ?? 0) . " units\n";
        echo "   Available Stock: " . ($boxStockData['availableStock'] ?? 0) . " units\n";
        echo "   Locked Stock: " . ($boxStockData['lockedStock'] ?? 0) . " units\n";
        echo "   Reserved Stock: " . ($boxStockData['reservedStock'] ?? 0) . " units\n";
        
        $totalStock = ($boxStockData['warehouseStock'] ?? 0) + ($boxStockData['availableStock'] ?? 0);
        $lockedStock = ($boxStockData['lockedStock'] ?? 0) + ($boxStockData['reservedStock'] ?? 0);
        
        echo "\n📈 SUMMARY:\n";
        echo "   🟢 TOTAL AVAILABLE: {$totalStock} units\n";
        echo "   🟡 TOTAL LOCKED: {$lockedStock} units\n";
        echo "   📦 GRAND TOTAL: " . ($totalStock + $lockedStock) . " units\n";
        echo "════════════════════════════════════════════\n";
        
        if ($totalStock > 0) {
            echo "✅ STATUS: BOX memiliki stock tersedia untuk dijual!\n";
        } elseif ($lockedStock > 0) {
            echo "⚠️  STATUS: BOX ada stock tapi sedang locked/reserved\n";
        } else {
            echo "❌ STATUS: BOX stock kosong (0 units)\n";
        }
        
    } else {
        echo "\n❌ SKU 'BOX' TIDAK DITEMUKAN\n";
        echo "════════════════════════════════════════════\n";
        echo "Kemungkinan:\n";
        echo "1. SKU tidak ada di Ginee inventory\n";
        echo "2. SKU dengan format berbeda (box, Box, dll)\n";
        echo "3. Produk ada tapi tidak di warehouse yang dicek\n";
        echo "4. Ada di page lebih dari {$maxPages}\n";
    }

    // ============= CHECK MASTER PRODUCTS =============
    echo "\n=== METHOD 2: MASTER PRODUCTS VERIFICATION ===\n";
    
    $products = $ginee->getMasterProducts(['page' => 0, 'size' => 50, 'sku' => 'BOX']);
    
    if (($products['code'] ?? null) === 'SUCCESS') {
        $productList = $products['data']['list'] ?? [];
        echo "✅ Master products search completed\n";
        
        $masterProductFound = false;
        foreach ($productList as $product) {
            $masterSku = $product['masterSku'] ?? '';
            if (strtoupper($masterSku) === 'BOX' || $masterSku === 'BOX') {
                $masterProductFound = true;
                echo "✅ BOX found in master products:\n";
                echo "   Name: " . ($product['productName'] ?? 'N/A') . "\n";
                echo "   SKU: " . $masterSku . "\n";
                echo "   Brand: " . ($product['brand'] ?? 'N/A') . "\n";
                echo "   Status: " . ($product['status'] ?? 'N/A') . "\n";
                echo "   Category: " . ($product['categoryName'] ?? 'N/A') . "\n";
                break;
            }
        }
        
        if (!$masterProductFound) {
            echo "⚠️ BOX not found in master products (sku filter)\n";
        }
    } else {
        echo "❌ Master products check failed: " . ($products['message'] ?? 'Unknown') . "\n";
    }

    // ============= CHECK LOCAL DATABASE =============
    echo "\n=== LOCAL DATABASE CHECK ===\n";
    
    try {
        $localBoxProduct = Product::where('sku', 'BOX')->first();
        
        if ($localBoxProduct) {
            echo "✅ BOX found in local database:\n";
            echo "   ID: " . $localBoxProduct->id . "\n";
            echo "   Name: " . ($localBoxProduct->name ?? 'N/A') . "\n";
            echo "   SKU: " . $localBoxProduct->sku . "\n";
            echo "   Local Stock: " . ($localBoxProduct->stock_quantity ?? 0) . " units\n";
            echo "   Price: Rp " . number_format($localBoxProduct->price ?? 0, 0, ',', '.') . "\n";
            echo "   Status: " . ($localBoxProduct->is_active ? 'Active' : 'Inactive') . "\n";
            echo "   Ginee Last Sync: " . ($localBoxProduct->ginee_last_sync ?? 'Never') . "\n";
            echo "   Ginee Sync Status: " . ($localBoxProduct->ginee_sync_status ?? 'Not synced') . "\n";
        } else {
            echo "❌ SKU 'BOX' not found in local database\n";
        }
    } catch (Exception $e) {
        echo "❌ Database check failed: " . $e->getMessage() . "\n";
    }

    // ============= FINAL RECOMMENDATIONS =============
    echo "\n=== RECOMMENDATIONS ===\n";
    
    if ($boxFound && $boxStockData) {
        $totalStock = ($boxStockData['warehouseStock'] ?? 0) + ($boxStockData['availableStock'] ?? 0);
        
        if ($totalStock > 0) {
            echo "🎯 READY TO SELL: BOX memiliki {$totalStock} units stock\n";
            echo "💡 Actions you can take:\n";
            echo "   - Update local database stock jika perlu\n";
            echo "   - Pastikan harga dan deskripsi sesuai\n";
            echo "   - Monitor stock secara berkala\n";
        } else {
            echo "⚠️ STOCK EMPTY: BOX tidak ada stock tersedia\n";
            echo "💡 Actions you can take:\n";
            echo "   - Cek di warehouse lain jika ada\n";
            echo "   - Tambah stock di Ginee jika perlu\n";
            echo "   - Set status 'out of stock' di toko online\n";
        }
    } else {
        echo "❓ PRODUCT NOT FOUND: BOX tidak ditemukan di Ginee\n";
        echo "💡 Actions you can take:\n";
        echo "   - Pastikan SKU BOX sudah dibuat di Ginee\n";
        echo "   - Cek apakah ada typo di SKU\n";
        echo "   - Hubungi tim Ginee jika diperlukan\n";
    }

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n\n";
    
    echo "🔧 TROUBLESHOOTING:\n";
    echo "1. Pastikan koneksi internet stabil\n";
    echo "2. Pastikan Ginee credentials benar\n";
    echo "3. Pastikan GineeClient class tersedia\n";
} catch (Error $e) {
    echo "❌ PHP ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}

echo "\n=== COMPLETED ===\n";
echo "🕒 Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "🔒 REMINDER: Ini hanya READ operation - tidak ada data yang diubah!\n";

/*
=============================================================================
USAGE INSTRUCTIONS
=============================================================================

1. Simpan file ini sebagai: msku-box.php di root directory Laravel

2. Jalankan dengan:
   php msku-box.php

3. File ini akan:
   ✅ Cari SKU BOX di Ginee inventory (hingga 10 pages)
   ✅ Cross-check dengan master products  
   ✅ Compare dengan database lokal
   ✅ Berikan recommendations

4. 100% READ-ONLY - tidak mengubah data apapun!

=============================================================================
*/