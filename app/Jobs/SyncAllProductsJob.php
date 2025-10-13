<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\GineeSyncLog;
use App\Services\OptimizedGineeStockSyncService;

class SyncAllProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected bool $dryRun;
    public $timeout = 10800; // 3 jam

    public function __construct(bool $dryRun = true)
    {
        $this->dryRun = $dryRun;
    }

    public function handle(): void
    {
        $sessionId = GineeSyncLog::generateSessionId();
        $redisKey = "ginee:all_skus:{$sessionId}";
        $service  = new OptimizedGineeStockSyncService();

        Log::info("🚀 [SyncAllProductsJob] Started", [
            'session_id' => $sessionId,
            'dry_run'    => $this->dryRun
        ]);

        // ============================================================
        // 1️⃣ FETCH ALL PRODUCTS DARI GINEE (tanpa fallback)
        // ============================================================
        try {
            $gineeData = $service->fetchAllSkusFromGinee($redisKey);
            $totalFetched = count($gineeData);

            Log::info("📦 [SyncAllProductsJob] Semua data Ginee berhasil di-fetch", [
                'total' => $totalFetched
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ [SyncAllProductsJob] Gagal fetch data Ginee", [
                'error' => $e->getMessage()
            ]);
            GineeSyncLog::create([
                'session_id' => $sessionId,
                'type' => 'bulk_sync_summary',
                'status' => 'failed',
                'operation_type' => 'fetch',
                'message' => 'Gagal fetch data dari Ginee: '.$e->getMessage(),
                'dry_run' => $this->dryRun,
                'created_at' => now(),
            ]);
            return;
        }

        // ============================================================
        // 2️⃣ COMPARE DENGAN DATABASE LOCAL
        // ============================================================
        $products = Product::select('id', 'sku', 'name', 'stock_quantity')
            ->whereNotNull('sku')
            ->get()
            ->keyBy('sku');

        $logs = [];
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $batchCount = 0;
        foreach ($products as $sku => $product) {
            $batchCount++;

            $gineeStock = $gineeData[$sku]['available_stock'] ?? $gineeData[$sku]['total_stock'] ?? null;

            if (is_null($gineeStock)) {
                $failed++;
                $logs[] = [
                    'session_id' => $sessionId,
                    'type' => 'batch_item',
                    'status' => 'failed',
                    'operation_type' => 'sync_all',
                    'sku' => $sku,
                    'product_name' => $product->name,
                    'old_stock' => $product->stock_quantity,
                    'new_stock' => null,
                    'change' => null,
                    'message' => 'SKU tidak ditemukan di hasil Ginee',
                    'dry_run' => $this->dryRun,
                    'created_at' => now(),
                ];
                continue;
            }

            $oldStock = $product->stock_quantity;
            $newStock = (int) $gineeStock;
            $change   = $newStock - $oldStock;

            if ($change === 0) {
                $skipped++;
                $logs[] = [
                    'session_id' => $sessionId,
                    'type' => 'batch_item',
                    'status' => 'skipped',
                    'operation_type' => 'sync_all',
                    'sku' => $sku,
                    'product_name' => $product->name,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'change' => 0,
                    'message' => 'Sudah sinkron',
                    'dry_run' => $this->dryRun,
                    'created_at' => now(),
                ];
                continue;
            }

            if (!$this->dryRun) {
                try {
                    $product->update([
                        'stock_quantity' => $newStock,
                        'ginee_last_sync' => now(),
                        'ginee_sync_status' => 'synced',
                    ]);
                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $logs[] = [
                        'session_id' => $sessionId,
                        'type' => 'batch_item',
                        'status' => 'failed',
                        'operation_type' => 'sync_all',
                        'sku' => $sku,
                        'product_name' => $product->name,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'change' => $change,
                        'message' => 'Update gagal: '.$e->getMessage(),
                        'dry_run' => $this->dryRun,
                        'created_at' => now(),
                    ];
                    continue;
                }
            }

            $logs[] = [
                'session_id' => $sessionId,
                'type' => 'batch_item',
                'status' => $this->dryRun ? 'skipped' : 'success',
                'operation_type' => 'sync_all',
                'sku' => $sku,
                'product_name' => $product->name,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $change,
                'message' => $this->dryRun
                    ? "DRY RUN - {$oldStock} → {$newStock}"
                    : "Updated {$oldStock} → {$newStock}",
                'dry_run' => $this->dryRun,
                'created_at' => now(),
            ];

            // Simpan log per 500 untuk efisiensi
            if ($batchCount % 500 === 0) {
                DB::table('ginee_sync_logs')->insert($logs);
                $logs = [];
            }
        }

        // Insert sisa log
        if (!empty($logs)) {
            DB::table('ginee_sync_logs')->insert($logs);
        }

        // ============================================================
        // 3️⃣ HAPUS DATA REDIS
        // ============================================================
        Redis::del($redisKey);

        // ============================================================
        // 4️⃣ SIMPAN SUMMARY
        // ============================================================
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'sync_all',
            'method_used' => 'direct_fetch',
            'message' => "Sync All selesai. Updated {$updated}, skipped {$skipped}, failed {$failed}.",
            'dry_run' => $this->dryRun,
            'created_at' => now(),
        ]);

        Log::info("✅ [SyncAllProductsJob] Completed", [
            'session_id' => $sessionId,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed
        ]);
    }
}
