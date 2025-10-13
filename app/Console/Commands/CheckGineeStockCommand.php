<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class CheckGineeStockCommand extends Command
{
    protected $signature = 'ginee:check-stock {sku? : SKU to check (default: BOX)}';
    protected $description = 'Check specific SKU stock in Ginee';

    public function handle()
    {
        $sku = $this->argument('sku') ?? 'BOX';
        $this->info("🔍 Checking stock for SKU: {$sku}");
        $this->newLine();

        try {
            $ginee = new GineeClient();
            
            // Method 1: Check in warehouse inventory
            $this->line("📦 Checking warehouse inventory...");
            $warehouseResult = $this->checkWarehouseInventory($ginee, $sku);
            
            if (!$warehouseResult) {
                // Method 2: Check in master products
                $this->line("📋 Checking master products...");
                $this->checkMasterProducts($ginee, $sku);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function checkWarehouseInventory(GineeClient $ginee, string $sku): bool
    {
        // Coba method alternatif: test stock update untuk mendapatkan stock info
        $this->line("   Trying stock update test to get current stock...");
        
        $testUpdate = [
            ['masterSku' => $sku, 'quantity' => 0] // Update dengan 0 untuk cek stock tanpa mengubah
        ];
        
        $result = $ginee->updateStock($testUpdate);
        
        if (($result['code'] ?? null) === 'SUCCESS') {
            $stockList = $result['data']['stockList'] ?? [];
            foreach ($stockList as $item) {
                if (($item['masterSku'] ?? null) === $sku) {
                    $this->info("🎯 FOUND {$sku} via stock update method!");
                    $this->table(['Field', 'Value'], [
                        ['SKU', $item['masterSku'] ?? 'N/A'],
                        ['Product Name', $item['masterProductName'] ?? 'N/A'],
                        ['Warehouse Stock', $item['warehouseStock'] ?? 'N/A'],
                        ['Available Stock', $item['availableStock'] ?? 'N/A'],
                        ['Locked Stock', $item['lockedStock'] ?? 'N/A'],
                        ['Spare Stock', $item['spareStock'] ?? 'N/A'],
                        ['Transport Stock', $item['transportStock'] ?? 'N/A'],
                        ['Promotion Stock', $item['promotionStock'] ?? 'N/A'],
                        ['Out Stock', $item['outStock'] ?? 'N/A'],
                        ['Safety Stock', $item['safetyStock'] ?? 'N/A'],
                        ['Last Updated', $item['updateDatetime'] ?? 'N/A'],
                    ]);
                    return true;
                }
            }
        }
        
        // Fallback ke warehouse inventory (akan gagal tapi kita coba)
        $result = $ginee->getWarehouseInventory([
            'page' => 0,
            'size' => 100
        ]);

        if (($result['code'] ?? null) !== 'SUCCESS') {
            $this->warn("⚠️  Warehouse inventory failed: " . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        $items = $result['data']['content'] ?? [];
        $this->line("   Total items in warehouse: " . count($items));

        foreach ($items as $item) {
            $itemSku = $item['masterVariation']['masterSku'] ?? $item['sku'] ?? null;
            
            if ($itemSku === $sku) {
                $this->info("🎯 FOUND {$sku} in warehouse inventory!");
                $this->table(['Field', 'Value'], [
                    ['SKU', $itemSku],
                    ['Product Name', $item['masterVariation']['name'] ?? 'N/A'],
                    ['Warehouse Stock', $item['warehouseStock'] ?? 'N/A'],
                    ['Available Stock', $item['availableStock'] ?? 'N/A'],
                    ['Locked Stock', $item['lockedStock'] ?? 'N/A'],
                    ['Spare Stock', $item['spareStock'] ?? 'N/A'],
                ]);
                return true;
            }
        }

        $this->warn("❌ {$sku} not found in warehouse inventory");
        return false;
    }

    private function checkMasterProducts(GineeClient $ginee, string $sku): bool
    {
        $found = false;
        $page = 0;
        $maxPages = 20; // Cek maksimal 20 halaman (1000 produk)

        while (!$found && $page < $maxPages) {
            $this->line("   Searching page {$page}...");
            
            $result = $ginee->getMasterProducts([
                'page' => $page,
                'size' => 50
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                $this->error("❌ Failed to get master products: " . ($result['message'] ?? 'Unknown error'));
                break;
            }

            $items = $result['data']['content'] ?? [];
            
            foreach ($items as $item) {
                $variations = $item['variationBriefs'] ?? [];
                
                foreach ($variations as $variation) {
                    $varSku = $variation['sku'] ?? null;
                    
                    if ($varSku === $sku) {
                        $this->info("🎯 FOUND {$sku} in master products!");
                        
                        $stock = $variation['stock'] ?? [];
                        $this->table(['Field', 'Value'], [
                            ['SKU', $varSku],
                            ['Product Name', $item['name'] ?? 'N/A'],
                            ['Variation ID', $variation['id'] ?? 'N/A'],
                            ['Warehouse Stock', $stock['warehouseStock'] ?? 'N/A'],
                            ['Available Stock', $stock['availableStock'] ?? 'N/A'],
                            ['Spare Stock', $stock['spareStock'] ?? 'N/A'],
                            ['Safety Stock', $stock['safetyStock'] ?? 'N/A'],
                            ['Safety Alert', $stock['safetyAlert'] ? 'Yes' : 'No'],
                            ['Bound Shops', $variation['boundShopCount'] ?? 'N/A'],
                            ['Product Status', $item['masterProductStatus'] ?? 'N/A'],
                        ]);
                        
                        $found = true;
                        break 2;
                    }
                }
            }
            
            $page++;
        }

        if (!$found) {
            $this->error("❌ {$sku} not found in master products (checked {$page} pages)");
        }

        return $found;
    }
}