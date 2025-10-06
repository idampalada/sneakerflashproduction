<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\{Product, GineeSyncLog};
use Illuminate\Support\Facades\Log;

class GineeBatchSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $skus;
    protected bool $dryRun;
    protected string $sessionId;
    protected int $batchNumber;
    protected int $totalBatches;

    public $timeout = 3600; // 1 jam per batch

    public function __construct(array $skus, bool $dryRun, string $sessionId, int $batchNumber, int $totalBatches)
    {
        $this->skus = $skus;
        $this->dryRun = $dryRun;
        $this->sessionId = $sessionId;
        $this->batchNumber = $batchNumber;
        $this->totalBatches = $totalBatches;
    }

    public function handle(): void
    {
        $syncService = new \App\Services\OptimizedGineeStockSyncService();

        Log::info("🔄 [Batch {$this->batchNumber}] Starting", [
            'session_id' => $this->sessionId,
            'skus' => count($this->skus),
            'dry_run' => $this->dryRun
        ]);

        // Preload produk
        $products = Product::whereIn('sku', $this->skus)
            ->get(['sku', 'stock_quantity', 'warehouse_stock', 'name'])
            ->keyBy('sku');

        // Bulk request ke Ginee
        $bulkResult = $syncService->getBulkStockFromGinee($this->skus);
        $foundStocks = $bulkResult['found_stock'] ?? [];

        $logs = [];
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($this->skus as $sku) {
            try {
                $product = $products[$sku] ?? null;
                $oldStock = $product?->stock_quantity ?? 0;
                $gineeData = $foundStocks[$sku] ?? null;

                if (!$gineeData) {
                    $failedCount++;
                    $logs[] = [
                        'session_id' => $this->sessionId,
                        'type' => 'batch_item',
                        'status' => 'failed',
                        'sku' => $sku,
                        'product_name' => $product?->name ?? 'Unknown',
                        'message' => '❌ SKU not found in Ginee',
                        'dry_run' => $this->dryRun,
                        'created_at' => now()
                    ];
                    continue;
                }

                $newStock = $gineeData['total_stock'] ?? $gineeData['available_stock'] ?? 0;
                $change = $newStock - $oldStock;

                // Dry run enforcement
                if (!$this->dryRun) {
                    $product->update([
                        'stock_quantity' => $newStock,
                        'ginee_last_sync' => now(),
                        'ginee_sync_status' => 'synced'
                    ]);
                    $updatedCount++;
                } else {
                    if ($change != 0) {
                        $skippedCount++;
                    }
                }

                $logs[] = [
                    'session_id' => $this->sessionId,
                    'type' => 'batch_item',
                    'status' => $this->dryRun ? 'skipped' : 'success',
                    'sku' => $sku,
                    'product_name' => $product?->name ?? 'Unknown',
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $change,
                    'message' => $this->dryRun
                        ? "🧪 DRY RUN: would update {$oldStock} → {$newStock}"
                        : "✅ Updated {$oldStock} → {$newStock}",
                    'dry_run' => $this->dryRun,
                    'created_at' => now()
                ];

            } catch (\Throwable $e) {
                $failedCount++;
                $logs[] = [
                    'session_id' => $this->sessionId,
                    'type' => 'batch_item',
                    'status' => 'failed',
                    'sku' => $sku,
                    'message' => "Exception: " . $e->getMessage(),
                    'dry_run' => $this->dryRun,
                    'created_at' => now()
                ];
            }
        }

        // Insert log sekaligus (hemat I/O)
        GineeSyncLog::insert($logs);

        Log::info("✅ [Batch {$this->batchNumber}/{$this->totalBatches}] Completed", [
            'session_id' => $this->sessionId,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount
        ]);

        // Summary per batch
        GineeSyncLog::create([
            'session_id' => $this->sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'stock_push',
            'method_used' => 'batch_sync',
            'message' => "Batch {$this->batchNumber}/{$this->totalBatches} completed. Updated: {$updatedCount}, Failed: {$failedCount}",
            'dry_run' => $this->dryRun,
            'created_at' => now()
        ]);
    }
}
