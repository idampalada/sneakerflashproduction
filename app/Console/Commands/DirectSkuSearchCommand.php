<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class DirectSkuSearchCommand extends Command
{
    protected $signature = 'ginee:search-sku {sku}';
    protected $description = 'Direct search for specific SKU in Ginee (much faster for large catalogs)';

    public function handle()
    {
        $sku = $this->argument('sku');
        $this->info("🎯 Direct search for SKU: {$sku}");
        $this->line("🚀 Using efficient search (no pagination needed)");
        $this->newLine();

        try {
            $ginee = new GineeClient();
            
            // Method 1: Direct SKU filter search
            $this->line("🔍 Method 1: Direct SKU filter search...");
            $found = $this->directSkuSearch($ginee, $sku);
            
            if (!$found) {
                // Method 2: Search by product name containing SKU
                $this->line("🔍 Method 2: Search by product name...");
                $found = $this->searchByProductName($ginee, $sku);
            }
            
            if (!$found) {
                // Method 3: Try partial SKU search
                $this->line("🔍 Method 3: Partial SKU search...");
                $found = $this->partialSkuSearch($ginee, $sku);
            }
            
            if (!$found) {
                $this->error("❌ {$sku} not found with any search method");
                $this->line("💡 Try checking the exact SKU spelling");
                $this->line("💡 Or use: php artisan ginee:list-skus to see available SKUs");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function directSkuSearch(GineeClient $ginee, string $sku): bool
    {
        $result = $ginee->getMasterProducts([
            'page' => 0,
            'size' => 50,
            'sku' => $sku
        ]);

        if (($result['code'] ?? null) !== 'SUCCESS') {
            $this->warn("⚠️  Direct SKU search failed: " . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        $items = $result['data']['content'] ?? [];
        $this->line("   Found " . count($items) . " products with SKU filter");
        
        return $this->processSearchResults($items, $sku, 'Direct SKU search');
    }

    private function searchByProductName(GineeClient $ginee, string $sku): bool
    {
        $result = $ginee->getMasterProducts([
            'page' => 0,
            'size' => 50,
            'productName' => $sku
        ]);

        if (($result['code'] ?? null) !== 'SUCCESS') {
            $this->warn("⚠️  Product name search failed: " . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        $items = $result['data']['content'] ?? [];
        $this->line("   Found " . count($items) . " products with name filter");
        
        return $this->processSearchResults($items, $sku, 'Product name search');
    }

    private function partialSkuSearch(GineeClient $ginee, string $sku): bool
    {
        // Try searching with partial SKU or related terms
        $searchTerms = [
            strtoupper($sku),
            strtolower($sku),
            "DOUBLE BOX", // Known product name for BOX
            "SNEAKERS FLASH"
        ];

        foreach ($searchTerms as $term) {
            $this->line("   Trying search term: {$term}");
            
            $result = $ginee->getMasterProducts([
                'page' => 0,
                'size' => 50,
                'productName' => $term
            ]);

            if (($result['code'] ?? null) === 'SUCCESS') {
                $items = $result['data']['content'] ?? [];
                
                if ($this->processSearchResults($items, $sku, "Partial search ({$term})")) {
                    return true;
                }
            }
        }

        return false;
    }

    private function processSearchResults(array $items, string $targetSku, string $method): bool
    {
        foreach ($items as $item) {
            $variations = $item['variationBriefs'] ?? [];
            
            foreach ($variations as $variation) {
                $varSku = $variation['sku'] ?? null;
                
                // Exact match or show all if searching broadly
                if ($varSku === $targetSku || $method === 'Partial search') {
                    if ($varSku === $targetSku) {
                        $this->info("🎯 FOUND {$targetSku} via {$method}!");
                    } else {
                        $this->line("📦 Found related SKU: {$varSku}");
                    }
                    
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
                        ['Search Method', $method],
                    ]);
                    
                    if ($varSku === $targetSku) {
                        return true; // Exact match found
                    }
                }
            }
        }
        
        return false;
    }
}

// Also create a helper command to list available SKUs
class ListGineeSkusCommand extends Command
{
    protected $signature = 'ginee:list-skus {--page=0} {--size=20}';
    protected $description = 'List available SKUs in Ginee (helpful for finding correct SKU names)';

    public function handle()
    {
        $page = (int) $this->option('page');
        $size = (int) $this->option('size');
        
        $this->info("📋 Listing available SKUs (page {$page}, size {$size})");
        $this->newLine();

        try {
            $ginee = new GineeClient();
            
            $result = $ginee->getMasterProducts([
                'page' => $page,
                'size' => $size
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                $this->error("❌ Failed to get products: " . ($result['message'] ?? 'Unknown error'));
                return 1;
            }

            $data = $result['data'] ?? [];
            $items = $data['content'] ?? [];
            $total = $data['total'] ?? 0;
            
            $this->line("📊 Total products in Ginee: {$total}");
            $this->line("📦 Showing " . count($items) . " products from page {$page}");
            $this->newLine();

            $skuList = [];
            foreach ($items as $item) {
                $variations = $item['variationBriefs'] ?? [];
                
                foreach ($variations as $variation) {
                    $sku = $variation['sku'] ?? null;
                    $stock = $variation['stock'] ?? [];
                    
                    if ($sku) {
                        $skuList[] = [
                            $sku,
                            substr($item['name'] ?? 'N/A', 0, 40) . '...',
                            $stock['warehouseStock'] ?? 'N/A',
                            $stock['availableStock'] ?? 'N/A'
                        ];
                    }
                }
            }

            if (!empty($skuList)) {
                $this->table(['SKU', 'Product Name', 'Warehouse Stock', 'Available Stock'], $skuList);
                
                $this->newLine();
                $this->line("💡 To see more SKUs: php artisan ginee:list-skus --page=" . ($page + 1));
                $this->line("💡 To search specific SKU: php artisan ginee:search-sku YOUR_SKU");
            } else {
                $this->warn("❌ No products found on page {$page}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}