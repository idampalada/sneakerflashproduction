<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class ReadGineeStockCommand extends Command
{
    protected $signature = 'ginee:read-stock {sku? : SKU to check (default: BOX)}';
    protected $description = 'READ ONLY - Check specific SKU stock in Ginee (NO UPDATES)';

    public function handle()
    {
        $sku = $this->argument('sku') ?? 'BOX';
        $this->info("👀 READ ONLY - Checking stock for SKU: {$sku}");
        $this->warn("🔒 This command will NOT update or change any stock!");
        $this->newLine();

        try {
            $ginee = new GineeClient();
            
            // HANYA cek di master products - TIDAK ADA UPDATE APAPUN
            $this->line("📋 Reading from master products (read-only)...");
            $found = $this->readFromMasterProducts($ginee, $sku);
            
            if (!$found) {
                $this->error("❌ {$sku} not found in master products");
                $this->line("💡 Try checking if the SKU exists or use different search criteria");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function readFromMasterProducts(GineeClient $ginee, string $sku): bool
    {
        $found = false;
        $page = 0;
        $maxPages = 120; // Cek sampai 120 halaman (6000 produk)

        while (!$found && $page < $maxPages) {
            $this->line("   📖 Reading page {$page}...");
            
            $result = $ginee->getMasterProducts([
                'page' => $page,
                'size' => 50
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                $this->error("❌ Failed to read master products: " . ($result['message'] ?? 'Unknown error'));
                break;
            }

            $items = $result['data']['content'] ?? [];
            $this->line("     Found " . count($items) . " products on this page");
            
            foreach ($items as $item) {
                $variations = $item['variationBriefs'] ?? [];
                
                foreach ($variations as $variation) {
                    $varSku = $variation['sku'] ?? null;
                    
                    if ($varSku === $sku) {
                        $this->info("🎯 FOUND {$sku} in master products (READ ONLY)!");
                        
                        $stock = $variation['stock'] ?? [];
                        $this->table(['Field', 'Value'], [
                            ['SKU', $varSku],
                            ['Product Name', $item['name'] ?? 'N/A'],
                            ['Product ID', $item['productId'] ?? 'N/A'],
                            ['Variation ID', $variation['id'] ?? 'N/A'],
                            ['Warehouse Stock', $stock['warehouseStock'] ?? 'N/A'],
                            ['Available Stock', $stock['availableStock'] ?? 'N/A'],
                            ['Spare Stock', $stock['spareStock'] ?? 'N/A'],
                            ['Safety Stock', $stock['safetyStock'] ?? 'N/A'],
                            ['Safety Alert', isset($stock['safetyAlert']) ? ($stock['safetyAlert'] ? 'Yes' : 'No') : 'N/A'],
                            ['Bound Shops', $variation['boundShopCount'] ?? 'N/A'],
                            ['Bound Channels', $variation['boundChannelVariationCount'] ?? 'N/A'],
                            ['Product Status', $item['masterProductStatus'] ?? 'N/A'],
                            ['Warehouse Status', isset($variation['stockTagStatus']['warehouseStatus']) ? 
                                ($variation['stockTagStatus']['warehouseStatus'] ? 'Active' : 'Inactive') : 'N/A'],
                            ['Stock Sync Status', isset($variation['stockTagStatus']['stockSyncStatus']) ? 
                                ($variation['stockTagStatus']['stockSyncStatus'] ? 'Enabled' : 'Disabled') : 'N/A'],
                        ]);
                        
                        // Show option values if available
                        if (!empty($variation['optionValues'])) {
                            $this->newLine();
                            $this->line("🏷️  Option Values: " . implode(', ', $variation['optionValues']));
                        }
                        
                        // Show auto binding rules if available
                        if (!empty($variation['autoBindingRules'])) {
                            $this->line("🔗 Auto Binding Rules: " . implode(', ', $variation['autoBindingRules']));
                        }
                        
                        $found = true;
                        break 2;
                    }
                }
            }
            
            $page++;
            
            // Show progress every 10 pages untuk 120 halaman
            if ($page % 10 === 0 && $page > 0) {
                $this->line("   🔍 Searched {$page} pages so far... (" . ($page * 50) . " products checked)");
            }
        }

        if (!$found) {
            $this->error("❌ {$sku} not found in master products (searched {$page} pages)");
            $this->line("💡 Total products checked: " . ($page * 50) . " out of 2939+ total products");
            $this->line("💡 You may need to check the exact SKU spelling");
            
            // Suggest using search API with SKU filter
            $this->newLine();
            $this->line("🔍 Trying direct SKU search...");
            return $this->searchBySku($ginee, $sku);
        }

        return $found;
    }

    /**
     * Try searching by SKU filter directly
     */
    private function searchBySku(GineeClient $ginee, string $sku): bool
    {
        $this->line("   🎯 Searching directly by SKU filter...");
        
        $result = $ginee->getMasterProducts([
            'page' => 0,
            'size' => 50,
            'sku' => $sku  // Direct SKU search
        ]);

        if (($result['code'] ?? null) !== 'SUCCESS') {
            $this->warn("⚠️  Direct SKU search failed: " . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        $items = $result['data']['content'] ?? [];
        $this->line("     Direct search found " . count($items) . " products");
        
        if (empty($items)) {
            $this->warn("❌ No products found with direct SKU search for: {$sku}");
            return false;
        }

        foreach ($items as $item) {
            $variations = $item['variationBriefs'] ?? [];
            
            foreach ($variations as $variation) {
                $varSku = $variation['sku'] ?? null;
                
                if ($varSku === $sku) {
                    $this->info("🎯 FOUND {$sku} via direct SKU search!");
                    
                    $stock = $variation['stock'] ?? [];
                    $this->table(['Field', 'Value'], [
                        ['SKU', $varSku],
                        ['Product Name', $item['name'] ?? 'N/A'],
                        ['Product ID', $item['productId'] ?? 'N/A'],
                        ['Variation ID', $variation['id'] ?? 'N/A'],
                        ['Warehouse Stock', $stock['warehouseStock'] ?? 'N/A'],
                        ['Available Stock', $stock['availableStock'] ?? 'N/A'],
                        ['Spare Stock', $stock['spareStock'] ?? 'N/A'],
                        ['Safety Stock', $stock['safetyStock'] ?? 'N/A'],
                        ['Safety Alert', isset($stock['safetyAlert']) ? ($stock['safetyAlert'] ? 'Yes' : 'No') : 'N/A'],
                        ['Bound Shops', $variation['boundShopCount'] ?? 'N/A'],
                        ['Bound Channels', $variation['boundChannelVariationCount'] ?? 'N/A'],
                        ['Product Status', $item['masterProductStatus'] ?? 'N/A'],
                    ]);
                    
                    return true;
                }
            }
        }
        
        $this->warn("❌ SKU {$sku} not found even in direct search results");
        return false;
    }
}