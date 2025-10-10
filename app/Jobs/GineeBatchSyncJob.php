<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\{Product, GineeSyncLog};

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

        Log::info("🟦 [Batch {$this->batchNumber}] Starting", [
            'session_id' => $this->sessionId,
            'total_skus' => count($this->skus),
            'dry_run' => $this->dryRun
        ]);

        /**
         * 🔍 Filter SKU agar hanya yang benar-benar ada di tabel products
         */
        $validSkus = Product::whereIn('sku', $this->skus)->pluck('sku')->toArray();
        $missingSkus = array_diff($this->skus, $validSkus);

        if (!empty($missingSkus)) {
            Log::warning("⚠️ [Batch {$this->batchNumber}] Skipping " . count($missingSkus) . " SKUs not found in database", [
                'session_id' => $this->sessionId,
                'sample_missing' => array_slice($missingSkus, 0, 10),
            ]);
        }

        // Preload produk hanya untuk SKU valid
        $products = Product::whereIn('sku', $validSkus)
            ->get(['sku', 'stock_quantity', 'warehouse_stock', 'name'])
            ->keyBy('sku');

        // Ambil data stok dari Ginee hanya untuk SKU valid
        $bulkResult = $syncService->getBulkStockFromGinee($validSkus);
        $foundStocks = $bulkResult['found_stock'] ?? [];

        $logs = [];
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($validSkus as $sku) {
            try {
                $product = $products[$sku] ?? null;
                $oldStock = $product->stock_quantity ?? 0;
                $gineeData = $foundStocks[$sku] ?? null;

                if (!$gineeData) {
                    $failedCount++;
                    $logs[] = [
                        'session_id' => $this->sessionId,
                        'type' => 'batch_item',
                        'status' => 'failed',
                        'sku' => $sku,
                        'product_name' => $product->name ?? 'Unknown',
                        'old_stock' => $product->stock_quantity ?? 0,
                        'new_stock' => null,
                        'change' => null,
                        'message' => '❌ SKU not found in Ginee',
                        'dry_run' => $this->dryRun,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    continue;
                }

                // Gunakan rumus dari service: available = warehouse - locked
                $newStock = $gineeData['total_stock'] ?? $gineeData['available_stock'] ?? 0;
                $change = $newStock - $oldStock;

                // Update produk jika bukan dry run
                if (!$this->dryRun && $product) {
                    $product->update([
                        'stock_quantity' => $newStock,
                        'ginee_last_sync' => now(),
                        'ginee_sync_status' => 'synced',
                    ]);
                }

                $status = $change === 0 ? 'skipped' : 'success';
                if ($status === 'success') {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }

                $logs[] = [
                    'session_id' => $this->sessionId,
                    'type' => 'batch_item',
                    'status' => $status,
                    'sku' => $sku,
                    'product_name' => $product->name ?? 'Unknown',
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => $change,
                    'message' => $this->dryRun
                        ? "🧪 DRY RUN: would update {$oldStock} → {$newStock}"
                        : "✅ Updated {$oldStock} → {$newStock}",
                    'dry_run' => $this->dryRun,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

            } catch (\Throwable $e) {
                $failedCount++;
                $logs[] = [
                    'session_id' => $this->sessionId,
                    'type' => 'batch_item',
                    'status' => 'failed',
                    'sku' => $sku,
                    'product_name' => $product->name ?? 'Unknown',
                    'old_stock' => $product->stock_quantity ?? 0,
                    'new_stock' => null,
                    'change' => null,
                    'message' => "Exception: " . $e->getMessage(),
                    'dry_run' => $this->dryRun,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert semua log ke DB (pakai Query Builder agar tidak kena Eloquent overhead)
        if (!empty($logs)) {
            DB::table('ginee_sync_logs')->insert($logs);
        }

        // 🧾 Summary hasil batch
        Log::info("✅ [Batch {$this->batchNumber}/{$this->totalBatches}] Completed", [
            'session_id' => $this->sessionId,
            'requested_skus' => count($this->skus),
            'valid_skus' => count($validSkus),
            'missing_skus' => count($missingSkus),
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
        ]);

        // Simpan summary ke database
        GineeSyncLog::create([
            'session_id' => $this->sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'stock_push',
            'method_used' => 'batch_sync',
            'message' => "Batch {$this->batchNumber}/{$this->totalBatches} completed. Updated {$updatedCount}, skipped {$skippedCount}, failed {$failedCount}. Missing in DB: " . count($missingSkus),
            'dry_run' => $this->dryRun,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
