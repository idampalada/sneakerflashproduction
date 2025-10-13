<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GineeSyncLog;
use Illuminate\Support\Facades\Log;

class OptimizedBulkGineeSyncDispatcherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $skus;
    protected bool $dryRun;
    protected int $batchSize;

    public $timeout = 7200; // 2 jam maksimum

    public function __construct(array $skus, bool $dryRun = true, int $batchSize = 100)
    {
        $this->skus = $skus;
        $this->dryRun = $dryRun;
        $this->batchSize = $batchSize;
    }

    public function handle(): void
    {
        $sessionId = \App\Models\GineeSyncLog::generateSessionId();

        Log::info("🚀 [Dispatcher] Starting bulk sync", [
            'session_id' => $sessionId,
            'total_skus' => count($this->skus),
            'dry_run' => $this->dryRun
        ]);

        // Simpan log session baru
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'pending',
            'operation_type' => 'stock_push',
            'method_used' => 'dispatcher',
            'message' => "Dispatched bulk sync batches. Dry Run: " . ($this->dryRun ? 'true' : 'false'),
            'dry_run' => $this->dryRun,
            'created_at' => now()
        ]);

        $chunks = array_chunk($this->skus, $this->batchSize);
        $totalBatches = count($chunks);

        foreach ($chunks as $i => $chunk) {
            $batchNumber = $i + 1;
            Log::info("📦 Dispatching Batch {$batchNumber}/{$totalBatches} ({$this->batchSize} SKUs)");
            GineeBatchSyncJob::dispatch($chunk, $this->dryRun, $sessionId, $batchNumber, $totalBatches)
                ->onQueue('ginee-sync');
        }

        Log::info("✅ [Dispatcher] All batches dispatched", [
            'session_id' => $sessionId,
            'batches' => $totalBatches
        ]);
    }
}
