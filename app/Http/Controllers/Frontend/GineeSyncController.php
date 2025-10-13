<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\StandardGineeService;
use App\Services\OptimizedGineeStockSyncService;
use App\Services\EnhancedGineeStockSyncService;
use Illuminate\Support\Facades\Log;

class GineeSyncController extends Controller
{
    protected $standardService;
    protected $optimizedService;
    protected $enhancedService;

    public function __construct()
    {
        $this->standardService = new StandardGineeService();
        $this->optimizedService = new OptimizedGineeStockSyncService();
        $this->enhancedService = new EnhancedGineeStockSyncService();
    }

    /**
     * 🚀 ENHANCED TEST SINGLE SKU - dengan fallback methods
     */
public function testSingleSkuEnhanced(Request $request)
{
    $sku = $request->input('sku');
    
    if (!$sku) {
        return response()->json(['success' => false, 'message' => 'SKU is required'], 400);
    }

    try {
        $startTime = microtime(true);

        // Method 1: Try bulk optimized (same as artisan tinker)
        $optimizedService = new \App\Services\OptimizedGineeStockSyncService();
        $bulkResult = $optimizedService->getBulkStockFromGinee([$sku]);
        
        if ($bulkResult['success'] && isset($bulkResult['found_stock'][$sku])) {
            $stockData = $bulkResult['found_stock'][$sku];
            
            return response()->json([
                'success' => true,
                'message' => "✅ SKU found using Bulk Optimized method (same as artisan tinker)",
                'method_used' => 'bulk_optimized',
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'data' => [
                    'sku' => $sku,
                    'available_stock' => $stockData['available_stock'] ?? 0,
                    'warehouse_stock' => $stockData['warehouse_stock'] ?? 0,
                    'total_stock' => $stockData['total_stock'] ?? 0,
                    'product_name' => $stockData['product_name'] ?? 'N/A',
                    'api_source' => 'bulk_optimized_inventory',
                    'method_priority' => 1
                ]
            ]);
        }

        // Method 2: Try individual search using existing GineeStockSyncService
        $standardService = new \App\Services\GineeStockSyncService();
        $stockResult = $standardService->getStockFromGinee($sku);
        
        if ($stockResult['success'] && isset($stockResult['data']['ginee_stock'])) {
            $stockData = $stockResult['data']['ginee_stock'];
            
            return response()->json([
                'success' => true,
                'message' => "✅ SKU found using Individual search method",
                'method_used' => 'individual_search',
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'data' => [
                    'sku' => $sku,
                    'available_stock' => $stockData['available_stock'] ?? 0,
                    'warehouse_stock' => $stockData['warehouse_stock'] ?? 0,
                    'total_stock' => ($stockData['warehouse_stock'] ?? 0) + ($stockData['available_stock'] ?? 0),
                    'product_name' => $stockData['product_name'] ?? 'N/A',
                    'api_source' => 'individual_search',
                    'method_priority' => 2
                ]
            ]);
        }

        // Method 3: Try direct Ginee client
        $gineeClient = new \App\Services\GineeClient();
        $directResult = $gineeClient->getProductStock($sku);
        
        if (($directResult['code'] ?? null) === 'SUCCESS' && !empty($directResult['data'])) {
            $stockData = $directResult['data'];
            
            return response()->json([
                'success' => true,
                'message' => "✅ SKU found using Direct Ginee client",
                'method_used' => 'direct_ginee_client',
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'data' => [
                    'sku' => $sku,
                    'available_stock' => $stockData['availableStock'] ?? 0,
                    'warehouse_stock' => $stockData['warehouseStock'] ?? 0,
                    'total_stock' => ($stockData['warehouseStock'] ?? 0) + ($stockData['availableStock'] ?? 0),
                    'product_name' => $stockData['masterProductName'] ?? 'N/A',
                    'api_source' => 'direct_ginee_client',
                    'method_priority' => 3
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "❌ SKU {$sku} not found using any available methods",
            'methods_tried' => ['bulk_optimized', 'individual_search', 'direct_ginee_client'],
            'duration_seconds' => round(microtime(true) - $startTime, 2),
            'recommendation' => 'Check if SKU exists in Ginee dashboard manually'
        ]);

    } catch (\Exception $e) {
        \Log::error("Enhanced test SKU error: " . $e->getMessage(), [
            'sku' => $sku,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Test failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * 🎯 METHOD 2: Individual Search (original dashboard method)
 */
private function attemptIndividualMethod(string $sku): array
{
    try {
        Log::info("🔍 [Method 2] Trying individual search for SKU: {$sku}");
        
        $stockData = $this->standardService->getStockFromGinee($sku);
        
        if ($stockData) {
            return [
                'success' => true,
                'data' => [
                    'sku' => $sku,
                    'available_stock' => $stockData['available_stock'] ?? 0,
                    'warehouse_stock' => $stockData['warehouse_stock'] ?? 0,
                    'total_stock' => $stockData['total_stock'] ?? 0,
                    'product_name' => $stockData['product_name'] ?? 'N/A',
                    'api_source' => 'individual_search',
                    'method_priority' => 2
                ]
            ];
        }

        return ['success' => false, 'message' => 'Not found in individual search'];

    } catch (\Exception $e) {
        Log::error("❌ [Method 2] Individual search failed: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 🎯 METHOD 3: Enhanced Fallback Methods (if available)
 */
private function attemptEnhancedFallback(string $sku): array
{
    try {
        Log::info("🔍 [Method 3] Trying enhanced fallback for SKU: {$sku}");
        
        if (!isset($this->enhancedService)) {
            return ['success' => false, 'message' => 'Enhanced service not available'];
        }
        
        $result = $this->enhancedService->testSingleSkuEnhanced($sku, true);
        
        if ($result['success']) {
            return [
                'success' => true,
                'method_used' => $result['method_used'],
                'data' => [
                    'sku' => $sku,
                    'available_stock' => $result['stock_data']['available_stock'] ?? 0,
                    'warehouse_stock' => $result['stock_data']['warehouse_stock'] ?? 0,
                    'total_stock' => $result['stock_data']['total_stock'] ?? 0,
                    'product_name' => $result['stock_data']['product_name'] ?? 'N/A',
                    'api_source' => $result['stock_data']['api_source'] ?? 'enhanced_fallback',
                    'method_priority' => 3
                ]
            ];
        }

        return ['success' => false, 'message' => 'Not found in enhanced fallback'];

    } catch (\Exception $e) {
        Log::error("❌ [Method 3] Enhanced fallback failed: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 🚀 SYNC SINGLE SKU - Enhanced method
 */
public function syncSingleSkuEnhanced(Request $request)
{
    $sku = $request->input('sku');
    $dryRun = $request->boolean('dry_run', true);

    if (!$sku) {
        return response()->json(['success' => false, 'message' => 'SKU is required'], 400);
    }

    try {
        // Priority 1: Use bulk optimized method for sync (same as artisan)
        $optimizedService = new \App\Services\OptimizedGineeStockSyncService();
        $syncResult = $optimizedService->syncMultipleSkusOptimized([$sku], [
            'dry_run' => $dryRun,
            'chunk_size' => 1
        ]);

        if ($syncResult['success'] && ($syncResult['data']['successful'] ?? 0) > 0) {
            return response()->json([
                'success' => true,
                'message' => $dryRun ? 
                    "✅ Dry run success - would update SKU {$sku}" : 
                    "✅ Successfully synced SKU {$sku}",
                'method_used' => 'bulk_optimized_sync'
            ]);
        }

        // Fallback: Try individual sync using existing service
        $standardService = new \App\Services\GineeStockSyncService();
        
        if ($dryRun) {
            // For dry run, just check if stock exists
            $stockResult = $standardService->getStockFromGinee($sku);
            if ($stockResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "✅ Dry run success - SKU found and would be synced",
                    'method_used' => 'individual_search_dry_run'
                ]);
            }
        } else {
            // For live sync, use pushSingleSkuToGinee if available
            if (method_exists($standardService, 'pushSingleSkuToGinee')) {
                $pushResult = $standardService->pushSingleSkuToGinee($sku, false);
                if ($pushResult['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => "✅ Successfully synced SKU {$sku} using individual method",
                        'method_used' => 'individual_push'
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => "❌ Failed to sync SKU {$sku} using available methods"
        ]);

    } catch (\Exception $e) {
        \Log::error("Enhanced sync SKU error: " . $e->getMessage(), [
            'sku' => $sku,
            'dry_run' => $dryRun,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Sync failed: ' . $e->getMessage()
        ], 500);
    }
}
/**
 * 🔍 COMPARE ALL METHODS - untuk debugging
 */
public function compareAllMethods(Request $request)
{
    $sku = $request->input('sku');
    
    if (!$sku) {
        return response()->json(['success' => false, 'message' => 'SKU is required'], 400);
    }

    try {
        $methods = [];
        $startTime = microtime(true);
        
        // Test 1: Bulk optimized
        $method1Start = microtime(true);
        try {
            $optimizedService = new \App\Services\OptimizedGineeStockSyncService();
            $bulkResult = $optimizedService->getBulkStockFromGinee([$sku]);
            $method1Duration = round(microtime(true) - $method1Start, 2);
            
            $methods['bulk_optimized'] = [
                'name' => 'Bulk Optimized (Artisan Tinker Method)',
                'success' => $bulkResult['success'] && isset($bulkResult['found_stock'][$sku]),
                'message' => $bulkResult['success'] && isset($bulkResult['found_stock'][$sku]) ? 
                    "✅ Found - Stock: " . ($bulkResult['found_stock'][$sku]['total_stock'] ?? 0) :
                    "❌ Not found in bulk search",
                'priority' => 1,
                'duration' => $method1Duration . 's',
                'api_calls' => 1
            ];
        } catch (\Exception $e) {
            $methods['bulk_optimized'] = [
                'name' => 'Bulk Optimized (Artisan Tinker Method)',
                'success' => false,
                'message' => "💥 Exception: " . $e->getMessage(),
                'priority' => 1,
                'duration' => 'N/A',
                'api_calls' => 0
            ];
        }
        
        // Test 2: Individual search using existing service
        $method2Start = microtime(true);
        try {
            $standardService = new \App\Services\GineeStockSyncService();
            $stockResult = $standardService->getStockFromGinee($sku);
            $method2Duration = round(microtime(true) - $method2Start, 2);
            
            $stockFound = $stockResult['success'] && isset($stockResult['data']['ginee_stock']);
            
            $methods['individual_search'] = [
                'name' => 'Individual Search (Original Dashboard)',
                'success' => $stockFound,
                'message' => $stockFound ? 
                    "✅ Found - Stock: " . (($stockResult['data']['ginee_stock']['warehouse_stock'] ?? 0) + ($stockResult['data']['ginee_stock']['available_stock'] ?? 0)) :
                    "❌ Not found in individual search",
                'priority' => 2,
                'duration' => $method2Duration . 's',
                'api_calls' => 1
            ];
        } catch (\Exception $e) {
            $methods['individual_search'] = [
                'name' => 'Individual Search (Original Dashboard)',
                'success' => false,
                'message' => "💥 Exception: " . $e->getMessage(),
                'priority' => 2,
                'duration' => 'N/A',
                'api_calls' => 0
            ];
        }

        // Test 3: Direct Ginee client
        $method3Start = microtime(true);
        try {
            $gineeClient = new \App\Services\GineeClient();
            $directResult = $gineeClient->getProductStock($sku);
            $method3Duration = round(microtime(true) - $method3Start, 2);
            
            $directFound = ($directResult['code'] ?? null) === 'SUCCESS' && !empty($directResult['data']);
            
            $methods['direct_ginee_client'] = [
                'name' => 'Direct Ginee Client',
                'success' => $directFound,
                'message' => $directFound ? 
                    "✅ Found - Stock: " . (($directResult['data']['warehouseStock'] ?? 0) + ($directResult['data']['availableStock'] ?? 0)) :
                    "❌ Not found via direct client",
                'priority' => 3,
                'duration' => $method3Duration . 's',
                'api_calls' => 1
            ];
        } catch (\Exception $e) {
            $methods['direct_ginee_client'] = [
                'name' => 'Direct Ginee Client',
                'success' => false,
                'message' => "💥 Exception: " . $e->getMessage(),
                'priority' => 3,
                'duration' => 'N/A',
                'api_calls' => 0
            ];
        }

        $totalDuration = round(microtime(true) - $startTime, 2);
        $successfulMethods = array_filter($methods, fn($m) => $m['success']);
        
        $bestMethod = !empty($successfulMethods) ? array_reduce($successfulMethods, function($best, $current) {
            return (!$best || $current['priority'] < $best['priority']) ? $current : $best;
        }) : null;

        $recommendation = $bestMethod ? 
            "✅ Best method: {$bestMethod['name']} (Priority {$bestMethod['priority']})\n" .
            "⚡ Duration: {$bestMethod['duration']}\n" .
            "📡 API Calls: {$bestMethod['api_calls']}\n\n" .
            "🚀 FOR DASHBOARD:\n" .
            "- Update 'Test Single SKU' to use: {$bestMethod['name']}\n" .
            "- Expected response time: {$bestMethod['duration']}\n\n" .
            "💡 IMPLEMENTATION:\n" .
            "Dashboard enhanced method sudah menggunakan priority yang benar:\n" .
            "1. Bulk Optimized (same as artisan tinker) ✅\n" .
            "2. Individual Search (fallback) ✅\n" .
            "3. Direct Ginee Client (last resort) ✅" :
            "❌ SKU {$sku} not found using any method.\n\n" .
            "🔧 TROUBLESHOOTING:\n" .
            "1. Check if SKU exists in Ginee dashboard manually\n" .
            "2. Verify API credentials in .env file\n" .
            "3. Check Ginee API rate limits\n" .
            "4. Run: php artisan ginee:debug-optimization {$sku}";

        return response()->json([
            'success' => true,
            'message' => !empty($successfulMethods) ? 
                "Found using " . count($successfulMethods) . " method(s)" :
                "SKU not found using any method",
            'data' => [
                'sku' => $sku,
                'total_duration' => $totalDuration . 's',
                'methods' => $methods,
                'best_method' => $bestMethod ? $bestMethod['name'] : null,
                'successful_methods' => count($successfulMethods),
                'recommendation' => $recommendation
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error("Compare methods error: " . $e->getMessage(), [
            'sku' => $sku,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Comparison failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * 💡 Generate recommendation based on method comparison
 */
private function generateRecommendation(array $methods, array $bestMethod): string
{
    $recommendation = "🎯 RECOMMENDATION:\n\n";
    
    $recommendation .= "✅ Best method: {$bestMethod['name']}\n";
    $recommendation .= "⚡ Duration: {$bestMethod['duration']}\n";
    $recommendation .= "📡 API calls: {$bestMethod['api_calls']}\n\n";
    
    $recommendation .= "🚀 FOR DASHBOARD:\n";
    $recommendation .= "- Update 'Test Single SKU' to use: {$bestMethod['name']}\n";
    $recommendation .= "- Expected response time: {$bestMethod['duration']}\n\n";
    
    $recommendation .= "⚙️ FOR BULK OPERATIONS:\n";
    if (isset($methods['bulk_optimized']) && $methods['bulk_optimized']['success']) {
        $recommendation .= "- Use Bulk Optimized method (like artisan tinker)\n";
        $recommendation .= "- Process in chunks of 50-100 SKUs\n";
    } else {
        $recommendation .= "- Fallback to Individual Search with rate limiting\n";
        $recommendation .= "- Process in smaller chunks (10-20 SKUs)\n";
    }
    
    $recommendation .= "\n🔧 IMPLEMENTATION:\n";
    $recommendation .= "1. Priority 1: " . ($methods['bulk_optimized']['success'] ? "Bulk Optimized ✅" : "Bulk Optimized ❌") . "\n";
    $recommendation .= "2. Priority 2: " . ($methods['individual_search']['success'] ? "Individual Search ✅" : "Individual Search ❌") . "\n";
    $recommendation .= "3. Priority 3: " . ($methods['enhanced_fallback']['success'] ? "Enhanced Fallback ✅" : "Enhanced Fallback ❌") . "\n";
    
    return $recommendation;
}

/**
 * 📊 Analyze performance metrics
 */
private function analyzePerformance(array $methods): array
{
    $analysis = [
        'fastest_method' => null,
        'most_api_efficient' => null,
        'reliability_score' => [],
        'speed_comparison' => []
    ];
    
    $successfulMethods = array_filter($methods, fn($m) => $m['success']);
    
    if (!empty($successfulMethods)) {
        // Find fastest method
        $fastestMethod = array_reduce($successfulMethods, function($fastest, $current) {
            if (!$fastest) return $current;
            
            $fastestDuration = (float)str_replace('s', '', $fastest['duration']);
            $currentDuration = (float)str_replace('s', '', $current['duration']);
            
            return $currentDuration < $fastestDuration ? $current : $fastest;
        });
        
        $analysis['fastest_method'] = $fastestMethod['name'];
        
        // Find most API efficient
        $mostEfficient = array_reduce($successfulMethods, function($efficient, $current) {
            if (!$efficient) return $current;
            return $current['api_calls'] < $efficient['api_calls'] ? $current : $efficient;
        });
        
        $analysis['most_api_efficient'] = $mostEfficient['name'];
        
        // Speed comparison
        foreach ($methods as $key => $method) {
            $duration = (float)str_replace('s', '', $method['duration']);
            $analysis['speed_comparison'][$key] = [
                'duration_seconds' => $duration,
                'relative_speed' => $method['success'] ? 'Success' : 'Failed',
                'api_efficiency' => $method['api_calls'] . ' calls'
            ];
        }
    }
    
    return $analysis;
}

/**
 * 🚀 SYNC ALL PRODUCTS - Enhanced bulk operations
 */
public function syncAllProductsEnhanced(Request $request)
{
    $dryRun = $request->boolean('dry_run', true);
    $chunkSize = $request->integer('chunk_size', 50);
    
    Log::info("🚀 [Dashboard] Starting enhanced bulk sync", [
        'dry_run' => $dryRun,
        'chunk_size' => $chunkSize
    ]);

    try {
        // Get all SKUs that need syncing
        $skusToSync = \App\Models\Product::where(function($query) {
            $query->whereNull('ginee_last_sync')
                  ->orWhere('ginee_sync_status', '!=', 'synced')
                  ->orWhere('updated_at', '>', DB::raw('ginee_last_sync'));
        })->pluck('sku')->toArray();

        if (empty($skusToSync)) {
            return response()->json([
                'success' => true,
                'message' => '✅ All products are already synced',
                'data' => [
                    'total_skus' => 0,
                    'successful' => 0,
                    'failed' => 0
                ]
            ]);
        }

        // Use optimized bulk sync
        $syncResult = $this->optimizedService->syncMultipleSkusOptimized($skusToSync, [
            'dry_run' => $dryRun,
            'chunk_size' => $chunkSize
        ]);

        return response()->json([
            'success' => $syncResult['success'],
            'message' => $dryRun ? 
                "🧪 Bulk sync dry run completed" : 
                "🚀 Bulk sync completed",
            'data' => $syncResult['data']
        ]);

    } catch (\Exception $e) {
        Log::error("💥 [Dashboard] Enhanced bulk sync failed: " . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Bulk sync failed: ' . $e->getMessage()
        ], 500);
    }
}
}
