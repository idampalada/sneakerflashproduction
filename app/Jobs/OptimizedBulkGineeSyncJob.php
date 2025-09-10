<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\OptimizedGineeStockSyncService;
use App\Models\GineeSyncLog;
use Illuminate\Support\Facades\Log;

class OptimizedBulkGineeSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $skus;
    protected $dryRun;
    protected $batchSize;
    protected $delayBetweenBatches;
    protected $sessionId;

    /**
     * Job timeout in seconds (1 hour)
     */
    public $timeout = 3600;

    /**
     * Number of times the job may be attempted
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(array $skus, bool $dryRun = true, int $batchSize = 50, int $delayBetweenBatches = 3)
    {
        $this->skus = $skus;
        $this->dryRun = $dryRun;
        $this->batchSize = $batchSize;
        $this->delayBetweenBatches = $delayBetweenBatches;  // ✅ CHANGED
        $this->sessionId = GineeSyncLog::generateSessionId();
        
        Log::info("🚀 [OptimizedBulkGineeSyncJob] Job created", [
            'session_id' => $this->sessionId,
            'total_skus' => count($skus),
            'dry_run' => $dryRun,
            'batch_size' => $batchSize,
            'delay_between_batches' => $delayBetweenBatches  // ✅ CHANGED
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("🔄 [OptimizedBulkGineeSyncJob] Starting background sync", [
                'session_id' => $this->sessionId,
                'total_skus' => count($this->skus),
                'dry_run' => $this->dryRun,
                'batch_size' => $this->batchSize
            ]);

            // Create initial log entry
            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_start',
                'status' => 'pending',
                'operation_type' => 'sync',
                'message' => "Background sync started for " . count($this->skus) . " SKUs",
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            if (class_exists('\App\Services\OptimizedGineeStockSyncService')) {
                // Use optimized service
                $syncService = new OptimizedGineeStockSyncService();
                $result = $syncService->syncBulkStock(
                    $this->skus, 
                    $this->dryRun, 
                    $this->batchSize, 
                    $this->delayBetweenBatches,
                    $this->sessionId
                );
            } else {
                // Fallback to basic service
                Log::warning("OptimizedGineeStockSyncService not found, using basic service");
                $result = $this->basicBulkSync();
            }

            // Create completion log
            if ($result['success']) {
                $stats = $result['stats'] ?? [];
                
                GineeSyncLog::create([
                    'session_id' => $this->sessionId,
                    'type' => 'bulk_sync_completed',
                    'status' => 'success',
                    'operation_type' => 'sync',
                    'message' => sprintf(
                        "Background sync completed: %d total, %d successful, %d failed, %d skipped",
                        $stats['total'] ?? 0,
                        $stats['successful'] ?? 0,
                        $stats['failed'] ?? 0,
                        $stats['skipped'] ?? 0
                    ),
                    'dry_run' => $this->dryRun,
                    'created_at' => now()
                ]);

                Log::info("✅ [OptimizedBulkGineeSyncJob] Job completed successfully", [
                    'session_id' => $this->sessionId,
                    'stats' => $stats
                ]);
            } else {
                GineeSyncLog::create([
                    'session_id' => $this->sessionId,
                    'type' => 'bulk_sync_failed',
                    'status' => 'failed',
                    'operation_type' => 'sync',
                    'message' => "Background sync failed: " . ($result['message'] ?? 'Unknown error'),
                    'error_message' => $result['message'] ?? 'Unknown error',
                    'dry_run' => $this->dryRun,
                    'created_at' => now()
                ]);

                Log::error("❌ [OptimizedBulkGineeSyncJob] Job failed", [
                    'session_id' => $this->sessionId,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("💥 [OptimizedBulkGineeSyncJob] Job exception", [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Create error log
            GineeSyncLog::create([
                'session_id' => $this->sessionId,
                'type' => 'bulk_sync_exception',
                'status' => 'failed',
                'operation_type' => 'sync',
                'message' => "Background sync exception: " . $e->getMessage(),
                'error_message' => $e->getMessage(),
                'dry_run' => $this->dryRun,
                'created_at' => now()
            ]);

            // Re-throw exception to mark job as failed
            throw $e;
        }
    }

    /**
     * Basic bulk sync fallback
     */
    protected function basicBulkSync(): array
    {
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        
        try {
            $syncService = new \App\Services\OptimizedGineeStockSyncService();
            
            foreach ($this->skus as $sku) {
                try {
                    $result = $syncService->syncSingleSku($sku, $this->dryRun);
                    
                    if ($result['success']) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                    
                    // Add delay between individual syncs
                if ($this->delayBetweenBatches > 0) {  // ✅ CHANGED
                    sleep($this->delayBetweenBatches);  // ✅ CHANGED
                    }
                    
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Error syncing SKU {$sku}: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'stats' => [
                    'total' => count($this->skus),
                    'successful' => $successful,
                    'failed' => $failed,
                    'skipped' => $skipped,
                    'session_id' => $this->sessionId
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("💥 [OptimizedBulkGineeSyncJob] Job permanently failed", [
            'session_id' => $this->sessionId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Create final failure log
        GineeSyncLog::create([
            'session_id' => $this->sessionId,
            'type' => 'bulk_sync_permanent_failure',
            'status' => 'failed',
            'operation_type' => 'sync',
            'message' => "Background sync permanently failed after all retries",
            'error_message' => $exception->getMessage(),
            'dry_run' => $this->dryRun,
            'created_at' => now()
        ]);
    }
}