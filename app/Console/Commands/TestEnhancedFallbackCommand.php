<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EnhancedGineeStockSyncService;

class TestEnhancedFallbackCommand extends Command
{
    protected $signature = 'ginee:test-enhanced-fallback {sku}';
    protected $description = 'Test enhanced fallback methods for specific SKU';

    public function handle()
    {
        $sku = $this->argument('sku');
        
        $this->info('🧪 TESTING ENHANCED FALLBACK METHODS');
        $this->info('🎯 Target SKU: ' . $sku);
        $this->info('📊 Expected from Dashboard: Available Stock = 1');
        $this->info('='.str_repeat('=', 60));
        $this->newLine();

        $enhancedService = new EnhancedGineeStockSyncService();
        
        // Test all fallback methods
        $results = $enhancedService->testSingleSkuEnhanced($sku, true);
        
        $this->displayResults($results);
        
        // If successful, test actual sync
        if ($results['success']) {
            $this->newLine();
            $this->warn('✅ FALLBACK METHOD FOUND DATA! Testing actual sync...');
            $this->testActualSync($enhancedService, $sku);
        }

        return Command::SUCCESS;
    }

    private function displayResults($results)
    {
        $sku = $results['sku'];
        $success = $results['success'];
        
        $this->info('📋 FALLBACK METHODS TEST RESULTS');
        $this->line(str_repeat('-', 60));
        
        // Display each method's results
        foreach ($results['methods_tested'] as $methodKey => $method) {
            $icon = $method['success'] ? '✅' : '❌';
            $status = $method['success'] ? 'SUCCESS' : 'FAILED';
            $duration = $method['duration_seconds'];
            
            $this->line("{$icon} {$method['name']}: {$status} ({$duration}s)");
            
            if ($method['success'] && isset($method['data'])) {
                $data = $method['data'];
                $this->line("   📊 Stock Data Found:");
                $this->line("      - Available Stock: " . ($data['available_stock'] ?? 'N/A'));
                $this->line("      - Warehouse Stock: " . ($data['warehouse_stock'] ?? 'N/A'));
                $this->line("      - Total Stock: " . ($data['total_stock'] ?? 'N/A'));
                $this->line("      - API Source: " . ($data['api_source'] ?? 'N/A'));
                $this->line("      - Product Name: " . ($data['product_name'] ?? 'N/A'));
            }
            $this->newLine();
        }
        
        // Summary
        if ($success) {
            $methodUsed = $results['method_used'];
            $methodName = $results['methods_tested'][$methodUsed]['name'];
            $stockData = $results['stock_data'];
            
            $this->info("🎯 SUCCESS! SKU found using: {$methodName}");
            $this->newLine();
            
            // Compare with dashboard expectations
            $expectedStock = 1;
            $actualStock = $stockData['available_stock'] ?? 0;
            
            if ($actualStock == $expectedStock) {
                $this->info("✅ PERFECT MATCH! API Stock ({$actualStock}) = Dashboard Stock ({$expectedStock})");
            } else {
                $this->warn("⚠️ STOCK MISMATCH: API ({$actualStock}) vs Dashboard ({$expectedStock})");
            }
            
            // Display complete stock breakdown
            $this->newLine();
            $this->warn('📊 COMPLETE STOCK BREAKDOWN:');
            $stockFields = [
                ['Field', 'Value', 'Match Dashboard?'],
                ['Available Stock', $stockData['available_stock'] ?? 'N/A', ($stockData['available_stock'] ?? 0) == 1 ? '✅ YES' : '❌ NO'],
                ['Warehouse Stock', $stockData['warehouse_stock'] ?? 'N/A', ($stockData['warehouse_stock'] ?? 0) == 1 ? '✅ YES' : '❌ NO'],
                ['Total Stock', $stockData['total_stock'] ?? 'N/A', ($stockData['total_stock'] ?? 0) == 1 ? '✅ YES' : '❌ NO'],
                ['Locked Stock', $stockData['locked_stock'] ?? 'N/A', 'N/A'],
                ['API Source', $stockData['api_source'] ?? 'N/A', 'N/A']
            ];
            
            $this->table(['Field', 'Value', 'Match Dashboard?'], array_slice($stockFields, 1));
            
        } else {
            $this->error("❌ ALL FALLBACK METHODS FAILED for SKU: {$sku}");
            $this->warn('Possible reasons:');
            $this->line('1. SKU truly does not exist in any Ginee endpoint');
            $this->line('2. SKU exists in different warehouse not accessible via API');
            $this->line('3. Dashboard shows cached/different data than API');
            $this->line('4. API credentials have limited access scope');
        }
    }

    private function testActualSync($enhancedService, $sku)
    {
        $this->info('🔄 TESTING ACTUAL SYNC (DRY RUN)');
        $this->line(str_repeat('-', 60));
        
        $syncResult = $enhancedService->syncSingleSkuEnhanced($sku, true); // DRY RUN
        
        if ($syncResult['success']) {
            $this->info("✅ SYNC TEST SUCCESS!");
            $this->line("Message: " . $syncResult['message']);
            
            if (isset($syncResult['data'])) {
                $data = $syncResult['data'];
                $this->line("Method used: " . ($data['api_source'] ?? 'Unknown'));
                $this->line("Stock to sync: " . ($data['available_stock'] ?? 'N/A'));
            }
            
            $this->newLine();
            $this->warn('🚀 READY FOR LIVE SYNC!');
            $this->line('To perform actual sync (not dry run), use:');
            $this->line("php artisan ginee:enhanced-sync {$sku} --live");
            
        } else {
            $this->error("❌ SYNC TEST FAILED!");
            $this->line("Error: " . $syncResult['message']);
        }
    }
}