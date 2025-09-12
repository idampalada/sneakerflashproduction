<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GineeSyncLog;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OptimizedBulkGineeSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $skus;
    protected $dryRun;
    protected $batchSize;
    protected $delayBetweenBatches;
    protected $sessionId;

    public $timeout = 7200; // 2 hours
    public $tries = 2;

    public function __construct(array $skus, bool $dryRun = true, int $batchSize = 50, int $delay = 2)
    {
        $this->skus = $skus;
        $this->dryRun = $dryRun;
        $this->batchSize = $batchSize;
        $this->delayBetweenBatches = $delay;
        $this->sessionId = GineeSyncLog::generateSessionId();
        
        Log::info("🚀 [Background Job] Created sync job", [
            'session_id' => $this->sessionId,
            'total_skus' => count($skus),
            'dry_run' => $dryRun,
            'batch_size' => $batchSize,
            'delay' => $delay
        ]);
    }

    public function handle(): void
    {
        try {
            Log::info("🔄 [Background Job] Starting background sync", [
                'session_id' => $this->sessionId,
                'total_skus' => count($this->skus)
            ]);

            // ✅ CREATE START LOG FOR REAL-TIME MONITORING
            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_summary',
                'status' => 'pending',
                'operation_type' => 'stock_push',
                'method_used' => 'background_job',
                'message' => "🚀 BACKGROUND SYNC STARTED - Processing " . count($this->skus) . " products",
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            // ✅ PROCESS WITH SAME 2-METHOD SYSTEM AS SINGLE TEST
            $results = $this->processWithSameMethods();

            // ✅ CREATE COMPLETION LOG  
            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_summary',
                'status' => 'success',
                'operation_type' => 'stock_push',
                'method_used' => 'background_job',
                'message' => ($this->dryRun ? "🧪 BACKGROUND DRY RUN COMPLETED" : "✅ BACKGROUND SYNC COMPLETED") . 
                            " - Total: {$results['total']}, Success: {$results['successful']}, Failed: {$results['failed']}" .
                            " | Method 1 (Stock Push): {$results['method1_successful']}, Method 2 (Enhanced Fallback): {$results['method2_successful']}",
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            Log::info("✅ [Background Job] Completed successfully", [
                'session_id' => $this->sessionId,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [Background Job] Failed", [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_summary',
                'status' => 'failed',
                'operation_type' => 'stock_push',
                'method_used' => 'background_job',
                'message' => "❌ BACKGROUND SYNC FAILED: " . $e->getMessage(),
                'error_message' => $e->getMessage(),
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            throw $e;
        }
    }

    /**
     * ✅ PROCESS WITH SAME 2-METHOD SYSTEM AS SINGLE TEST
     * Read-only, same priority logic
     */
    protected function processWithSameMethods(): array
    {
        $results = [
            'total' => count($this->skus),
            'successful' => 0,
            'failed' => 0,
            'method1_successful' => 0, // Stock Push
            'method2_successful' => 0, // Enhanced Fallback
        ];

        // Process in batches to avoid memory issues
        $chunks = array_chunk($this->skus, $this->batchSize);
        $totalChunks = count($chunks);

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkNumber = $chunkIndex + 1;
            
            Log::info("📦 [Background Job] Processing chunk {$chunkNumber}/{$totalChunks}", [
                'session_id' => $this->sessionId,
                'chunk_size' => count($chunk)
            ]);

            // ✅ PROGRESS LOG FOR REAL-TIME MONITORING
            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_summary',
                'status' => 'pending',
                'operation_type' => 'stock_push',
                'method_used' => 'background_job',
                'message' => "📦 Processing chunk {$chunkNumber}/{$totalChunks} ({$results['successful']} completed so far)",
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            foreach ($chunk as $sku) {
                $skuResult = $this->processSingleSkuSameMethods($sku);
                
                if ($skuResult['success']) {
                    $results['successful']++;
                    if ($skuResult['method_used'] === 'stock_push') {
                        $results['method1_successful']++;
                    } else {
                        $results['method2_successful']++;
                    }
                } else {
                    $results['failed']++;
                }

                // Small delay between SKUs
                usleep(200000); // 0.2 second
            }

            // Delay between chunks (user setting)
            if ($chunkIndex < $totalChunks - 1) {
                sleep($this->delay);
            }
        }

        return $results;
    }

    /**
     * ✅ PROCESS SINGLE SKU - EXACT SAME AS DASHBOARD TEST
     * Priority 1: Stock Push, Priority 2: Enhanced Dashboard Fallback
     */
    protected function processSingleSkuSameMethods(string $sku): array
    {
        try {
            // ✅ PRIORITY 1: STOCK PUSH (same as testSingleSku Priority 1)
            $syncService = new \App\Services\OptimizedGineeStockSyncService();
            $result = $syncService->syncSingleSku($sku, $this->dryRun);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'method_used' => 'stock_push',
                    'message' => $result['message']
                ];
            }

            // ✅ PRIORITY 2: ENHANCED DASHBOARD FALLBACK (same as testSingleSku Priority 2)
            $product = Product::where('sku', $sku)->first();
            $oldStock = $product ? ($product->stock_quantity ?? 0) : null;
            $oldWarehouseStock = $product ? ($product->warehouse_stock ?? 0) : null;
            $productName = $product ? $product->name : 'Product Not Found';
            
            if (!$product) {
                return ['success' => false, 'method_used' => 'none', 'message' => 'Product not found'];
            }
            
            // Try enhanced fallback
            $bulkResult = $syncService->getBulkStockFromGinee([$sku]);
            
            if ($bulkResult['success'] && isset($bulkResult['found_stock'][$sku])) {
                $stockData = $bulkResult['found_stock'][$sku];
                
                $newStock = $stockData['total_stock'] ?? $stockData['available_stock'] ?? 0;
                $newWarehouseStock = $stockData['warehouse_stock'] ?? 0;
                $stockChange = $oldStock !== null ? ($newStock - $oldStock) : null;
                
                // ✅ UPDATE LOCAL DATABASE ONLY (READ ONLY)
                if (!$this->dryRun) {
                    $product->stock_quantity = $newStock;
                    $product->warehouse_stock = $newWarehouseStock;
                    $product->ginee_last_sync = now();
                    $product->ginee_sync_status = 'synced';
                    $product->save();
                }
                
                // ✅ LOG WITH COMPLETE INFO (same as dashboard)
                GineeSyncLog::create([
                    'type' => 'enhanced_dashboard_fallback',
                    'status' => $this->dryRun ? 'skipped' : 'success',
                    'operation_type' => 'stock_push',
                    'method_used' => 'enhanced_dashboard_fallback',
                    'sku' => $sku,
                    'product_name' => $productName,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $stockChange,
                    'old_warehouse_stock' => $oldWarehouseStock,
                    'new_warehouse_stock' => $newWarehouseStock,
                    'message' => $this->dryRun ? 
                        "BACKGROUND DRY RUN - Enhanced fallback would update from {$oldStock} to {$newStock}" :
                        "BACKGROUND SUCCESS - Enhanced fallback updated local DB from {$oldStock} to {$newStock}",
                    'ginee_response' => $stockData,
                    'dry_run' => $this->dryRun,
                    'session_id' => $this->sessionId
                ]);
                
                return [
                    'success' => true,
                    'method_used' => 'enhanced_dashboard_fallback',
                    'message' => "Enhanced fallback: {$oldStock} → {$newStock}"
                ];
            }

            // Both methods failed
            GineeSyncLog::create([
                'type' => 'enhanced_dashboard_fallback',
                'status' => 'failed',
                'operation_type' => 'stock_push',
                'method_used' => 'both_methods_failed',
                'sku' => $sku,
                'product_name' => $productName,
                'old_stock' => $oldStock,
                'message' => "Background sync - Both stock push and enhanced fallback failed",
                'error_message' => 'Both methods failed for background sync',
                'dry_run' => $this->dryRun,
                'session_id' => $this->sessionId
            ]);
            
            return [
                'success' => false,
                'method_used' => 'both_failed',
                'message' => 'Both methods failed'
            ];
            
        } catch (\Exception $e) {
            GineeSyncLog::create([
                'type' => 'enhanced_dashboard_fallback',
                'status' => 'failed',
                'operation_type' => 'stock_push',
                'method_used' => 'exception',
                'sku' => $sku,
                'message' => "Background sync exception: " . $e->getMessage(),
                'error_message' => $e->getMessage(),
                'dry_run' => $this->dryRun,
                'session_id' => $this->sessionId
            ]);
            
            return [
                'success' => false,
                'method_used' => 'exception',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("💥 [Background Job] Permanently failed", [
            'session_id' => $this->sessionId,
            'exception' => $exception->getMessage()
        ]);

        GineeSyncLog::create([
            'session_id' => $this->sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'failed',
            'operation_type' => 'stock_push',
            'method_used' => 'background_job',
            'message' => "💥 BACKGROUND SYNC PERMANENTLY FAILED after retries",
            'error_message' => $exception->getMessage(),
            'dry_run' => $this->dryRun,
            'created_at' => now()
        ]);
    }
}
