<?php

namespace App\Jobs;

use App\Services\GineeStockSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GineeStockSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    protected $skus;
    protected $sessionId;
    protected $dryRun;

    public function __construct(array $skus, string $sessionId, bool $dryRun = false)
    {
        $this->skus = $skus;
        $this->sessionId = $sessionId;
        $this->dryRun = $dryRun;
        $this->onQueue('ginee-sync'); // Dedicated queue
    }

    public function handle(GineeStockSyncService $syncService)
    {
        Log::info('🚀 [Queue] Starting Ginee stock sync job', [
            'session_id' => $this->sessionId,
            'sku_count' => count($this->skus),
            'dry_run' => $this->dryRun
        ]);

        try {
            $result = $syncService->syncMultipleSkusIndividually($this->skus, [
                'dry_run' => $this->dryRun,
                'batch_size' => 20,
                'session_id' => $this->sessionId
            ]);

            Log::info('✅ [Queue] Ginee stock sync job completed', [
                'session_id' => $this->sessionId,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [Queue] Ginee stock sync job failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw for retry mechanism
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('💥 [Queue] Ginee stock sync job permanently failed', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage()
        ]);
    }
}