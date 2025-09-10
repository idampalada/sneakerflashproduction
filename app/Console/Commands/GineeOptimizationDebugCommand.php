<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OptimizedGineeStockSyncService;
use App\Services\GineeStockSyncService;
use App\Services\GineeClient;
use App\Models\Product;
use App\Models\GineeMapping;
use App\Models\GineeSyncLog;
use Exception;

class GineeOptimizationDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ginee:debug-optimization {sku?} {--all-failed : Debug all recently failed SKUs}';

    /**
     * The console command description.
     */
    protected $description = 'Debug Ginee Optimization sync issues for specific SKU or all failed SKUs';

    private $optimizedService;
    private $standardService;
    private $gineeClient;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->optimizedService = new OptimizedGineeStockSyncService();
        $this->standardService = new GineeStockSyncService();
        $this->gineeClient = new GineeClient();

        $this->info('🔍 GINEE OPTIMIZATION DEBUGGING TOOL');
        $this->info('=====================================');

        if ($this->option('all-failed')) {
            $this->debugAllFailedSkus();
        } else {
            $sku = $this->argument('sku') ?: $this->askForSku();
            $this->debugSingleSku($sku);
        }

        return Command::SUCCESS;
    }

    /**
     * Ask user for SKU input
     */
    private function askForSku(): string
    {
        $defaultSku = '1000174051067';
        
        // Show recent failed SKUs for reference
        $this->info('📋 Recent failed SKUs:');
        $recentFailed = GineeSyncLog::where('status', 'failed')
            ->whereNotNull('sku')
            ->latest('created_at')
            ->limit(5)
            ->pluck('sku')
            ->unique()
            ->values()
            ->toArray();

        if (!empty($recentFailed)) {
            $this->table(['Recent Failed SKUs'], array_map(fn($sku) => [$sku], $recentFailed));
        }

        return $this->ask("Enter SKU to debug", $defaultSku);
    }

    /**
     * Debug all recently failed SKUs
     */
    private function debugAllFailedSkus()
    {
        $this->info('🔍 Debugging ALL recently failed SKUs...');
        
        $failedSkus = GineeSyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('sku')
            ->pluck('sku')
            ->unique()
            ->values()
            ->toArray();

        if (empty($failedSkus)) {
            $this->warn('No failed SKUs found in last 7 days');
            return;
        }

        $this->info("Found " . count($failedSkus) . " failed SKUs in last 7 days");
        
        if (!$this->confirm('This may take a while. Continue?')) {
            return;
        }

        $results = [];
        $progressBar = $this->output->createProgressBar(count($failedSkus));
        $progressBar->start();

        foreach ($failedSkus as $sku) {
            $result = $this->quickDebugSku($sku);
            $results[] = array_merge(['sku' => $sku], $result);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->displayBulkResults($results);
    }

    /**
     * Debug single SKU with full analysis
     */
    private function debugSingleSku(string $sku)
    {
        $this->info("🎯 Target SKU: {$sku}");
        $this->newLine();

        // Step 1: Analyze sync logs
        $this->analyzeSyncLogs($sku);

        // Step 2: Compare sync methods
        $this->compareSyncMethods($sku);

        // Step 3: Test bulk vs individual
        $this->testBulkVsIndividual($sku);

        // Step 4: Debug API endpoints
        $this->debugApiEndpoints($sku);

        // Step 5: Test optimized bulk search
        $this->testOptimizedBulkSearch($sku);

        // Step 6: Provide recommendations
        $this->provideOptimizedRecommendations($sku);
    }

    /**
     * Quick debug for bulk analysis
     */
    private function quickDebugSku(string $sku): array
    {
        $result = [
            'found_in_local' => false,
            'has_mapping' => false,
            'found_individual' => false,
            'found_bulk' => false,
            'recent_failures' => 0
        ];

        try {
            // Check local database
            $product = Product::where('sku', $sku)->first();
            $result['found_in_local'] = !is_null($product);

            if ($product) {
                $mapping = $product->gineeMappings()->first();
                $result['has_mapping'] = !is_null($mapping);
            }

            // Quick individual test
            try {
                $stock = $this->standardService->getStockFromGinee($sku);
                $result['found_individual'] = !is_null($stock);
            } catch (Exception $e) {
                $result['found_individual'] = false;
            }

            // Quick bulk test
            try {
                $bulkResult = $this->optimizedService->getBulkStockFromGinee([$sku]);
                $result['found_bulk'] = $bulkResult['success'] && isset($bulkResult['found_stock'][$sku]);
            } catch (Exception $e) {
                $result['found_bulk'] = false;
            }

            // Count recent failures
            $result['recent_failures'] = GineeSyncLog::where('sku', $sku)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

        } catch (Exception $e) {
            // Silent fail for bulk analysis
        }

        return $result;
    }

    /**
     * Analyze sync logs for the SKU
     */
    private function analyzeSyncLogs(string $sku)
    {
        $this->info('📋 STEP 1: Analyzing Recent Sync Logs');
        $this->line(str_repeat('-', 40));

        $recentLogs = GineeSyncLog::where('sku', $sku)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentLogs->count() > 0) {
            $this->info("✅ Found {$recentLogs->count()} recent sync attempts:");
            
            $logData = [];
            foreach ($recentLogs as $log) {
                $status = $log->status == 'success' ? '✅' : '❌';
                $logData[] = [
                    'Time' => $log->created_at->format('Y-m-d H:i:s'),
                    'Type' => $log->type,
                    'Status' => $status . ' ' . $log->status,
                    'Message' => strlen($log->message) > 50 ? substr($log->message, 0, 50) . '...' : $log->message
                ];
            }
            
            $this->table(['Time', 'Type', 'Status', 'Message'], $logData);
        } else {
            $this->warn('❌ No sync logs found for this SKU');
            $this->line('   Kemungkinan SKU belum pernah di-sync atau baru ditambahkan');
        }
        $this->newLine();
    }

    /**
     * Compare different sync methods
     */
    private function compareSyncMethods(string $sku)
    {
        $this->info('⚡ STEP 2: Comparing Sync Methods');
        $this->line(str_repeat('-', 40));

        $results = [];

        // Test 1: Standard individual sync
        $this->line('🔧 Testing standard individual sync...');
        try {
            $start = microtime(true);
            $standardResult = $this->standardService->syncSingleSku($sku, true);
            $duration = round(microtime(true) - $start, 2);

            $status = $standardResult['success'] ? 'SUCCESS' : 'FAILED';
            $icon = $standardResult['success'] ? '✅' : '❌';
            
            $results[] = ['Standard Sync', $icon . ' ' . $status, $duration . 's', substr($standardResult['message'], 0, 40)];
            
        } catch (Exception $e) {
            $results[] = ['Standard Sync', '💥 EXCEPTION', 'N/A', substr($e->getMessage(), 0, 40)];
        }

        // Test 2: Optimized bulk sync (single SKU)
        $this->line('🚀 Testing optimized bulk sync...');
        try {
            $start = microtime(true);
            $optimizedResult = $this->optimizedService->syncMultipleSkusOptimized([$sku], [
                'dry_run' => true,  // 🛡️ FORCE DRY RUN
                'chunk_size' => 1
            ]);
            $duration = round(microtime(true) - $start, 2);

            if ($optimizedResult['success']) {
                $successful = $optimizedResult['data']['successful'] ?? 0;
                $status = $successful > 0 ? 'SUCCESS' : 'NOT FOUND';
                $icon = $successful > 0 ? '✅' : '❌';
                $message = "Found: {$successful}, Failed: " . ($optimizedResult['data']['failed'] ?? 0);
            } else {
                $status = 'FAILED';
                $icon = '❌';
                $message = substr($optimizedResult['message'], 0, 40);
            }
            
            $results[] = ['Optimized Sync', $icon . ' ' . $status, $duration . 's', $message];
            
        } catch (Exception $e) {
            $results[] = ['Optimized Sync', '💥 EXCEPTION', 'N/A', substr($e->getMessage(), 0, 40)];
        }

        $this->table(['Method', 'Result', 'Duration', 'Details'], $results);
        $this->newLine();
    }

    /**
     * Test bulk vs individual search
     */
    private function testBulkVsIndividual(string $sku)
    {
        $this->info('📊 STEP 3: Testing Bulk vs Individual Search');
        $this->line(str_repeat('-', 40));

        $results = [];

        // Test individual search
        $this->line('🔍 Individual search test...');
        try {
            $start = microtime(true);
            $stock = $this->standardService->getStockFromGinee($sku);
            $duration = round(microtime(true) - $start, 2);
            
            if ($stock) {
                $results[] = ['Individual Search', '✅ FOUND', $duration . 's', 'Stock: ' . ($stock['total_stock'] ?? 0)];
            } else {
                $results[] = ['Individual Search', '❌ NOT FOUND', $duration . 's', 'No data returned'];
            }
        } catch (Exception $e) {
            $results[] = ['Individual Search', '💥 ERROR', 'N/A', substr($e->getMessage(), 0, 30)];
        }

        // Test bulk search
        $this->line('📦 Bulk search test...');
        try {
            $start = microtime(true);
            $bulkResult = $this->optimizedService->getBulkStockFromGinee([$sku]);
            $duration = round(microtime(true) - $start, 2);

            if ($bulkResult['success']) {
                if (isset($bulkResult['found_stock'][$sku])) {
                    $stock = $bulkResult['found_stock'][$sku];
                    $results[] = ['Bulk Search', '✅ FOUND', $duration . 's', 'Stock: ' . ($stock['total_stock'] ?? 0)];
                } else {
                    $results[] = ['Bulk Search', '❌ NOT FOUND', $duration . 's', 'In not_found array'];
                }
            } else {
                $results[] = ['Bulk Search', '❌ FAILED', $duration . 's', 'API call failed'];
            }
        } catch (Exception $e) {
            $results[] = ['Bulk Search', '💥 ERROR', 'N/A', substr($e->getMessage(), 0, 30)];
        }

        $this->table(['Method', 'Result', 'Duration', 'Details'], $results);
        $this->newLine();
    }

    /**
     * Debug API endpoints
     */
    private function debugApiEndpoints(string $sku)
    {
        $this->info('🌐 STEP 4: Debug API Endpoints');
        $this->line(str_repeat('-', 40));

        $results = [];

        // Test warehouse inventory endpoint
        $this->line('📋 Testing warehouse inventory endpoint...');
        try {
            $response = $this->gineeClient->getWarehouseInventory([
                'page' => 0,
                'size' => 10
            ]);

            if ($response && ($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $results[] = ['Warehouse Inventory', '✅ WORKING', count($data) . ' items', 'API responding'];
                
                // Search for SKU in response
                $found = false;
                foreach ($data as $item) {
                    $masterVariations = $item['masterVariations'] ?? [];
                    foreach ($masterVariations as $variation) {
                        if (isset($variation['masterSku']) && $variation['masterSku'] == $sku) {
                            $found = true;
                            break 2;
                        }
                    }
                }
                
                if ($found) {
                    $results[] = ['SKU in Inventory', '🎯 FOUND', 'Yes', 'Located in warehouse inventory'];
                } else {
                    $results[] = ['SKU in Inventory', '⚠️ NOT FOUND', 'No', 'Needs deeper search'];
                }
            } else {
                $results[] = ['Warehouse Inventory', '❌ FAILED', 'N/A', $response['message'] ?? 'API call failed'];
            }
        } catch (Exception $e) {
            $results[] = ['Warehouse Inventory', '💥 ERROR', 'N/A', substr($e->getMessage(), 0, 30)];
        }

        // Test master products endpoint  
        $this->line('🏬 Testing master products endpoint...');
        try {
            $response = $this->gineeClient->getMasterProducts([
                'page' => 0,
                'size' => 10
            ]);

            if ($response && ($response['code'] ?? null) === 'SUCCESS') {
                $data = $response['data'] ?? [];
                $stockCount = count($data);
                $results[] = ['Master Products', '✅ WORKING', $stockCount . ' products', 'API responding'];
                
                // Search for SKU in master products
                $found = false;
                foreach ($data as $product) {
                    if (isset($product['masterSku']) && $product['masterSku'] == $sku) {
                        $found = true;
                        $results[] = ['SKU in Master Products', '🎯 FOUND', 'Yes', 'Product exists in catalog'];
                        break;
                    }
                }
                
                if (!$found) {
                    $results[] = ['SKU in Master Products', '⚠️ NOT FOUND', 'No', 'Not in product catalog'];
                }
            } else {
                $results[] = ['Master Products', '❌ NOT FOUND', 'No data', $response['message'] ?? 'API call failed'];
            }
        } catch (Exception $e) {
            $results[] = ['Master Products', '💥 ERROR', 'N/A', substr($e->getMessage(), 0, 30)];
        }

        $this->table(['Endpoint', 'Status', 'Data', 'Notes'], $results);
        $this->newLine();
    }

    /**
     * Test optimized bulk search with multiple SKUs
     */
    private function testOptimizedBulkSearch(string $sku)
    {
        $this->info('🚀 STEP 5: Testing Optimized Bulk Search');
        $this->line(str_repeat('-', 40));

        $testSkus = [$sku, 'TEST-SKU-1', 'TEST-SKU-2'];
        $this->line('Testing with SKUs: ' . implode(', ', $testSkus));

        try {
            $start = microtime(true);
            $result = $this->optimizedService->getBulkStockFromGinee($testSkus);
            $duration = round(microtime(true) - $start, 2);

            $data = [
                ['Duration', $duration . ' seconds'],
                ['Success', $result['success'] ? 'YES' : 'NO'],
                ['Found SKUs', count($result['found_stock'] ?? [])],
                ['Not Found SKUs', count($result['not_found'] ?? [])],
                ['Target SKU Found', isset($result['found_stock'][$sku]) ? '🎯 YES' : '❌ NO']
            ];

            if (!empty($result['not_found'])) {
                $data[] = ['Not Found List', implode(', ', array_slice($result['not_found'], 0, 3))];
            }

            $this->table(['Metric', 'Value'], $data);

        } catch (Exception $e) {
            $this->error('💥 Optimized bulk search ERROR: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Provide optimization recommendations
     */
    private function provideOptimizedRecommendations(string $sku)
    {
        $this->info('💡 STEP 6: Ginee Optimization Recommendations');
        $this->line(str_repeat('-', 40));

        $this->warn('🚀 OPTIMIZATION-SPECIFIC FIXES:');
        $this->line('   a) Increase chunk_size for bulk operations (50-100)');
        $this->line('   b) Use getBulkStockFromGinee() instead of individual calls');
        $this->line('   c) Check pagination settings di warehouse inventory');
        $this->line('   d) Enable fallback to individual search if bulk fails');
        $this->newLine();

        $this->warn('📦 BULK SEARCH IMPROVEMENTS:');
        $this->line('   a) Increase API pagination limit (100-500 items per page)');
        $this->line('   b) Implement multi-warehouse search');
        $this->line('   c) Add retry mechanism for failed bulk operations');
        $this->line('   d) Cache warehouse inventory for faster subsequent searches');
        $this->newLine();

        $this->warn('⚡ PERFORMANCE OPTIMIZATIONS:');
        $this->line('   a) Jalankan sync during off-peak hours');
        $this->line('   b) Use background jobs untuk batch besar (>100 SKUs)');
        $this->line('   c) Implement rate limiting yang lebih aggressive');
        $this->line('   d) Parallel processing untuk multiple chunks');
        $this->newLine();

        $this->warn('🔧 DEBUGGING COMMANDS:');
        $this->line("   a) Test single SKU: php artisan ginee:debug-optimization {$sku}");
        $this->line("   b) Force resync: php artisan ginee:sync --force --sku={$sku}");
        $this->line('   c) Bulk test: php artisan ginee:debug-optimization --all-failed');
        $this->line('   d) View dashboard: /admin/ginee-optimization');
        $this->newLine();

        $this->warn('🆘 EMERGENCY FIXES:');
        $this->line('   a) Switch to individual sync sementara');
        $this->line('   b) Manual mapping via Ginee dashboard');
        $this->line('   c) Increase timeout settings');
        $this->line('   d) Contact Ginee support untuk API rate limits');
        $this->newLine();
    }

    /**
     * Display bulk analysis results
     */
    private function displayBulkResults(array $results)
    {
        $this->info('📊 BULK ANALYSIS SUMMARY');
        $this->line(str_repeat('=', 50));

        // Statistics
        $totalSkus = count($results);
        $foundLocal = count(array_filter($results, fn($r) => $r['found_in_local']));
        $hasMappings = count(array_filter($results, fn($r) => $r['has_mapping']));
        $foundIndividual = count(array_filter($results, fn($r) => $r['found_individual']));
        $foundBulk = count(array_filter($results, fn($r) => $r['found_bulk']));

        $stats = [
            ['Total Failed SKUs', $totalSkus],
            ['Found in Local DB', $foundLocal . ' (' . round($foundLocal/$totalSkus*100, 1) . '%)'],
            ['Have Ginee Mappings', $hasMappings . ' (' . round($hasMappings/$totalSkus*100, 1) . '%)'],
            ['Found Individual Search', $foundIndividual . ' (' . round($foundIndividual/$totalSkus*100, 1) . '%)'],
            ['Found Bulk Search', $foundBulk . ' (' . round($foundBulk/$totalSkus*100, 1) . '%)'],
        ];

        $this->table(['Metric', 'Count'], $stats);

        // Show problematic SKUs
        $problematicSkus = array_filter($results, function($r) {
            return $r['found_in_local'] && !$r['found_individual'] && !$r['found_bulk'];
        });

        if (!empty($problematicSkus)) {
            $this->newLine();
            $this->warn('🚨 MOST PROBLEMATIC SKUs (in local DB but not found in Ginee):');
            
            $problemData = [];
            foreach (array_slice($problematicSkus, 0, 10) as $item) {
                $problemData[] = [
                    $item['sku'],
                    $item['has_mapping'] ? 'Yes' : 'No',
                    $item['recent_failures'],
                    $item['found_individual'] ? 'Yes' : 'No',
                    $item['found_bulk'] ? 'Yes' : 'No'
                ];
            }
            
            $this->table(['SKU', 'Has Mapping', 'Recent Failures', 'Individual', 'Bulk'], $problemData);
        }
    }
}