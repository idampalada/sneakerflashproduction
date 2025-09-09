<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\OptimizedGineeStockSyncService;
use App\Models\GineeSyncLog;

class OptimizedBulkGineeSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour for large batches
    public $tries = 2;
    public $failOnTimeout = true;

    protected $skus;
    protected $options;
    protected $sessionId;

    public function __construct(array $skus, array $options = [])
    {
        $this->skus = $skus;
        $this->options = $options;
        $this->sessionId = $options['session_id'] ?? \Illuminate\Support\Str::uuid();
        
        // Set queue untuk optimized jobs
        $this->onQueue('ginee-optimized');
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        Log::info('🚀 [OPTIMIZED JOB] Starting optimized background sync', [
            'session_id' => $this->sessionId,
            'total_skus' => count($this->skus),
            'job_id' => $this->job->getJobId(),
            'optimization_enabled' => true
        ]);

        // Create initial log entry
        $logEntry = GineeSyncLog::create([
            'session_id' => $this->sessionId,
            'type' => 'optimized_bulk_background',
            'status' => 'started',
            'operation_type' => 'sync',
            'items_processed' => 0,
            'items_successful' => 0,
            'items_failed' => 0,
            'started_at' => now(),
            'initiated_by_user' => $this->options['user_id'] ?? 'system',
            'dry_run' => $this->options['dry_run'] ?? false,
            'batch_size' => $this->options['chunk_size'] ?? 50,
            'message' => 'Optimized background sync job started',
            'summary' => json_encode([
                'optimization_enabled' => true,
                'expected_performance' => '10-20 SKUs per second',
                'bulk_operations' => true
            ])
        ]);

        try {
            // Use optimized sync service
            $optimizedService = new OptimizedGineeStockSyncService();
            
            // Process with optimized bulk operations
            $result = $optimizedService->syncMultipleSkusOptimized($this->skus, [
                'dry_run' => $this->options['dry_run'] ?? false,
                'chunk_size' => $this->options['chunk_size'] ?? 50,
                'session_id' => $this->sessionId
            ]);

            $endTime = microtime(true);
            $totalDuration = round($endTime - $startTime, 2);
            $overallSpeed = round(count($this->skus) / $totalDuration, 2);

            if ($result['success']) {
                $stats = $result['data'];
                
                // Update final status
                $logEntry->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'items_processed' => count($this->skus),
                    'items_successful' => $stats['successful'],
                    'items_failed' => $stats['failed'],
                    'summary' => json_encode(array_merge($stats, [
                        'optimization_enabled' => true,
                        'total_duration_seconds' => $totalDuration,
                        'overall_speed_skus_per_sec' => $overallSpeed,
                        'performance_improvement' => 'Optimized bulk operations used',
                        'api_efficiency' => 'Bulk inventory fetch instead of individual searches'
                    ])),
                    'message' => "Optimized sync completed: {$stats['successful']} successful, {$stats['failed']} failed in {$totalDuration}s ({$overallSpeed} SKUs/sec)"
                ]);

                Log::info('✅ [OPTIMIZED JOB] Background sync completed successfully', [
                    'session_id' => $this->sessionId,
                    'successful' => $stats['successful'],
                    'failed' => $stats['failed'],
                    'total_duration' => $totalDuration . 's',
                    'speed' => $overallSpeed . ' SKUs/sec',
                    'performance_gain' => 'Up to 10x faster than standard sync'
                ]);

            } else {
                throw new \Exception('Optimized sync failed: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            // Update status to failed
            $logEntry->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'summary' => json_encode([
                    'optimization_enabled' => true,
                    'failed_after_seconds' => $duration,
                    'error_type' => 'optimization_job_exception'
                ]),
                'message' => 'Optimized sync job failed: ' . $e->getMessage()
            ]);

            Log::error('❌ [OPTIMIZED JOB] Background sync failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'duration_before_fail' => $duration . 's',
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw untuk queue retry mechanism
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('💥 [OPTIMIZED JOB] Ultimately failed after retries', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'optimization_enabled' => true
        ]);

        // Update log entry if exists
        GineeSyncLog::where('session_id', $this->sessionId)
            ->where('type', 'optimized_bulk_background')
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
                'summary' => json_encode([
                    'optimization_enabled' => true,
                    'ultimate_failure' => true,
                    'attempts' => $this->attempts(),
                    'error_type' => 'optimization_job_ultimate_failure'
                ]),
                'message' => 'Optimized sync job ultimately failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
            ]);
    }

    /**
     * Get estimated completion time for monitoring
     */
    public function getEstimatedCompletion(): array
    {
        $estimatedSpeed = 15; // SKUs per second (conservative estimate)
        $estimatedSeconds = ceil(count($this->skus) / $estimatedSpeed);
        
        return [
            'total_skus' => count($this->skus),
            'estimated_speed' => $estimatedSpeed . ' SKUs/sec',
            'estimated_duration' => $estimatedSeconds . ' seconds',
            'estimated_completion' => now()->addSeconds($estimatedSeconds)->toDateTimeString(),
            'optimization_enabled' => true,
            'expected_improvement' => 'Up to 10x faster than standard sync'
        ];
    }
}