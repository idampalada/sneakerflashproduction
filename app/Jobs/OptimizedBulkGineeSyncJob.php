<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\GineeSyncLog;
use App\Services\GineeClient;
use Illuminate\Support\Facades\Log;

class OptimizedBulkGineeSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $skus;
    protected $dryRun;
    protected $batchSize;
    protected $delay;
    
    // Job dapat dijalankan ulang maksimal 2 kali jika gagal
    public $tries = 3;
    
    // Batas waktu eksekusi job (5 menit)
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param array $skus
     * @param bool $dryRun
     * @param int $batchSize
     * @param int $delay
     * @return void
     */
    public function __construct(array $skus, bool $dryRun = true, int $batchSize = 50, int $delay = 2)
    {
        $this->skus = $skus;
        $this->dryRun = $dryRun;
        $this->batchSize = $batchSize;
        $this->delay = $delay;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        // Buat session ID unik untuk sinkronisasi ini
        $sessionId = GineeSyncLog::generateSessionId();
        
        // Log permulaan job
        Log::info('🚀 [OptimizedBulkGineeSyncJob] Starting bulk sync job', [
            'total_skus' => count($this->skus),
            'dry_run' => $this->dryRun,
            'batch_size' => $this->batchSize,
            'session_id' => $sessionId
        ]);

        // Buat log entri untuk sinkronisasi ini
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync',
            'status' => 'started',
            'operation_type' => 'sync',
            'message' => 'Starting bulk sync: ' . count($this->skus) . ' SKUs',
            'dry_run' => $this->dryRun,
            'batch_size' => $this->batchSize,
            'triggered_by' => 'dashboard',
            'started_at' => now()
        ]);

        // Ambil semua data inventaris dari Ginee sekaligus
        $gineeClient = new GineeClient();
        $startTime = now();
        
        Log::info('📊 [OptimizedBulkGineeSyncJob] Fetching all inventory data from Ginee');
        
        $allInventoryResult = $gineeClient->pullAllInventory(200); // Gunakan batch size besar
        
        // Siapkan mapping SKU => stock data
        $gineeStockMap = [];
        
        if (($allInventoryResult['code'] ?? null) === 'SUCCESS') {
            $allInventory = $allInventoryResult['data']['inventory'] ?? [];
            
            // Buat mapping SKU ke data stok
            foreach ($allInventory as $item) {
                $sku = $item['masterVariation']['masterSku'] ?? null;
                if ($sku) {
                    // Simpan semua data inventaris untuk SKU ini
                    $gineeStockMap[strtoupper($sku)] = [
                        'available_stock' => $item['warehouseInventory']['availableStock'] ?? 0,
                        'warehouse_stock' => $item['warehouseInventory']['warehouseStock'] ?? 0,
                        'product_name' => $item['masterVariation']['name'] ?? 'Unknown',
                        'total_stock' => ($item['warehouseInventory']['availableStock'] ?? 0) + 
                                         ($item['warehouseInventory']['warehouseStock'] ?? 0)
                    ];
                }
            }
            
            Log::info("✅ [OptimizedBulkGineeSyncJob] Fetched all inventory data from Ginee", [
                'total_items' => count($allInventory),
                'mapped_skus' => count($gineeStockMap),
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);
        } else {
            Log::error("❌ [OptimizedBulkGineeSyncJob] Failed to fetch inventory from Ginee", [
                'error' => $allInventoryResult['message'] ?? 'Unknown error'
            ]);
            
            // Gagal mengambil semua data - gunakan pendekatan fallback
            Log::warning("⚠️ [OptimizedBulkGineeSyncJob] Falling back to individual requests method");
            return $this->processBySingleRequests($sessionId);
        }
        
        // Variabel untuk statistik
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];
        $errors = [];
        
        // Proses dalam batch untuk menghindari timeout
        $totalSkus = count($this->skus);
        $totalBatches = ceil($totalSkus / $this->batchSize);
        
        Log::info("📋 [OptimizedBulkGineeSyncJob] Processing {$totalSkus} SKUs in {$totalBatches} batches");
        
        // Proses semua SKU dalam batch
        for ($batchIndex = 0; $batchIndex < $totalBatches; $batchIndex++) {
            $batchStart = $batchIndex * $this->batchSize;
            $batchSkus = array_slice($this->skus, $batchStart, $this->batchSize);
            
            Log::info("🔄 [OptimizedBulkGineeSyncJob] Processing batch {$batchIndex}/{$totalBatches} ({$this->batchSize} SKUs)");
            
            foreach ($batchSkus as $sku) {
                try {
                    // Dapatkan stok dari database lokal
                    $localProduct = Product::where('sku', $sku)->first();
                    
                    if (!$localProduct) {
                        $failed++;
                        $results[] = "❌ {$sku}: Product not found in local database";
                        
                        // Log kegagalan
                        GineeSyncLog::create([
                            'session_id' => $sessionId,
                            'type' => 'individual_sync',
                            'status' => 'failed',
                            'operation_type' => 'sync',
                            'sku' => $sku,
                            'message' => 'Product not found in local database',
                            'dry_run' => $this->dryRun,
                            'created_at' => now()
                        ]);
                        continue;
                    }

                    // Ambil stok lokal saat ini
                    $oldStockFromLocal = $localProduct->stock_quantity ?? 0;
                    $skuUpper = strtoupper($sku);
                    
                    // Cek apakah SKU ini ada di data Ginee yang sudah diambil
                    if (!isset($gineeStockMap[$skuUpper])) {
                        $failed++;
                        $results[] = "❌ {$sku}: Not found in Ginee";
                        
                        // Log kegagalan
                        GineeSyncLog::create([
                            'session_id' => $sessionId,
                            'type' => 'individual_sync',
                            'status' => 'failed',
                            'operation_type' => 'sync',
                            'sku' => $sku,
                            'product_name' => $localProduct->name ?? 'Unknown',
                            'message' => 'SKU not found in Ginee inventory',
                            'old_stock' => $oldStockFromLocal,
                            'new_stock' => null,
                            'change' => null,
                            'dry_run' => $this->dryRun,
                            'created_at' => now()
                        ]);
                        continue;
                    }
                    
                    // Ambil data stok dari mapping Ginee
                    $gineeStockData = $gineeStockMap[$skuUpper];
                    $newStockFromGinee = $gineeStockData['available_stock'] ?? 0;
                    
                    // Periksa apakah perlu sinkronisasi
                    $stockChange = $newStockFromGinee - $oldStockFromLocal;
                    
                    if ($stockChange == 0) {
                        $skipped++;
                        $results[] = "⏩ {$sku}: Already in sync (Stock: {$oldStockFromLocal})";
                        
                        // Log skip
                        GineeSyncLog::create([
                            'session_id' => $sessionId,
                            'type' => 'individual_sync',
                            'status' => 'success',
                            'operation_type' => 'sync',
                            'sku' => $sku,
                            'product_name' => $localProduct->name ?? $gineeStockData['product_name'] ?? 'Unknown',
                            'message' => $this->dryRun ? "Dry run - Already in sync" : "Already in sync",
                            'old_stock' => $oldStockFromLocal,
                            'new_stock' => $newStockFromGinee,
                            'change' => 0,
                            'dry_run' => $this->dryRun,
                            'created_at' => now()
                        ]);
                    } else {
                        // Update stok jika bukan dry run
                        if (!$this->dryRun) {
                            $localProduct->stock_quantity = $newStockFromGinee;
                            $localProduct->ginee_last_sync = now();
                            $localProduct->ginee_sync_status = 'synced';
                            $localProduct->save();
                        }
                        
                        $successful++;
                        $action = $this->dryRun ? "Would update" : "Updated";
                        $results[] = "✅ {$sku}: {$action} ({$oldStockFromLocal} → {$newStockFromGinee})";
                        
                        // Log sukses
                        GineeSyncLog::create([
                            'session_id' => $sessionId,
                            'type' => 'individual_sync',
                            'status' => 'success',
                            'operation_type' => 'sync',
                            'sku' => $sku,
                            'product_name' => $localProduct->name ?? $gineeStockData['product_name'] ?? 'Unknown',
                            'message' => $this->dryRun ? 
                                "Dry run - Would update from {$oldStockFromLocal} to {$newStockFromGinee}" :
                                "Updated stock from {$oldStockFromLocal} to {$newStockFromGinee}",
                            'old_stock' => $oldStockFromLocal,
                            'new_stock' => $newStockFromGinee,
                            'change' => $stockChange,
                            'dry_run' => $this->dryRun,
                            'created_at' => now()
                        ]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errorMsg = $e->getMessage();
                    $results[] = "❌ {$sku}: Exception - {$errorMsg}";
                    $errors[] = "{$sku}: {$errorMsg}";
                    
                    Log::error("💥 [OptimizedBulkGineeSyncJob] Exception during sync", [
                        'sku' => $sku,
                        'error' => $errorMsg,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Log exception
                    GineeSyncLog::create([
                        'session_id' => $sessionId,
                        'type' => 'individual_sync',
                        'status' => 'failed',
                        'operation_type' => 'sync',
                        'sku' => $sku,
                        'message' => $this->dryRun ? 
                            "Exception during dry run: {$errorMsg}" :
                            "Exception during sync: {$errorMsg}",
                        'error_message' => $errorMsg,
                        'dry_run' => $this->dryRun,
                        'created_at' => now()
                    ]);
                }
            }
            
            // Tambahkan delay antara batch jika bukan batch terakhir
            if ($batchIndex < $totalBatches - 1 && $this->delay > 0) {
                Log::info("⏱️ [OptimizedBulkGineeSyncJob] Waiting for {$this->delay} seconds before next batch");
                sleep($this->delay);
            }
        }
        
        // Log ringkasan sinkronisasi
        $endTime = now();
        $duration = $startTime->diffInSeconds($endTime);
        
        $successRate = $totalSkus > 0 ? round(($successful / $totalSkus) * 100, 1) : 0;
        $summary = "Sync completed in {$duration} seconds: {$successful} updated, {$skipped} already in sync, {$failed} failed. Success rate: {$successRate}%";
        
        Log::info("✅ [OptimizedBulkGineeSyncJob] {$summary}");
        
        // Buat log ringkasan
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'sync',
            'message' => ($this->dryRun ? "DRY RUN - " : "") . $summary,
            'summary' => [
                'total_skus' => $totalSkus,
                'successful' => $successful,
                'skipped' => $skipped,
                'failed' => $failed,
                'success_rate' => $successRate,
                'duration_seconds' => $duration,
                'errors' => $errors
            ],
            'dry_run' => $this->dryRun,
            'completed_at' => $endTime,
            'duration_seconds' => $duration,
            'created_at' => now()
        ]);
        
        return [
            'success' => true,
            'message' => $this->dryRun ? 'Dry run completed' : 'Sync completed',
            'data' => [
                'total_requested' => $totalSkus,
                'successful' => $successful,
                'failed' => $failed,
                'skipped' => $skipped,
                'not_found' => 0, // Tidak relevan dalam implementasi ini
                'no_mapping' => 0, // Tidak relevan dalam implementasi ini
                'errors' => array_slice($errors, 0, 10), // Ambil 10 error pertama saja
                'session_id' => $sessionId,
                'duration_seconds' => $duration
            ]
        ];
    }
    
    /**
     * Metode fallback menggunakan permintaan individual jika bulk fetch gagal
     *
     * @param string $sessionId
     * @return array
     */
    protected function processBySingleRequests($sessionId = null)
    {
        $sessionId = $sessionId ?: GineeSyncLog::generateSessionId();
        $syncService = new \App\Services\GineeStockSyncService();
        
        Log::info('🔄 [OptimizedBulkGineeSyncJob] Using individual requests fallback method', [
            'session_id' => $sessionId
        ]);
        
        // Variabel untuk statistik
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];
        $errors = [];
        
        // Proses dalam batch untuk menghindari timeout
        $totalSkus = count($this->skus);
        $totalBatches = ceil($totalSkus / $this->batchSize);
        
        Log::info("📋 [OptimizedBulkGineeSyncJob] Processing {$totalSkus} SKUs in {$totalBatches} batches (individual mode)");
        
        $startTime = now();
        
        // Proses semua SKU dalam batch
        for ($batchIndex = 0; $batchIndex < $totalBatches; $batchIndex++) {
            $batchStart = $batchIndex * $this->batchSize;
            $batchSkus = array_slice($this->skus, $batchStart, $this->batchSize);
            
            Log::info("🔄 [OptimizedBulkGineeSyncJob] Processing batch {$batchIndex}/{$totalBatches} ({$this->batchSize} SKUs)");
            
            foreach ($batchSkus as $sku) {
                try {
                    // Dapatkan stok dari database lokal
                    $localProduct = Product::where('sku', $sku)->first();
                    
                    if (!$localProduct) {
                        $failed++;
                        $results[] = "❌ {$sku}: Product not found in local database";
                        // Log kegagalan
                        // ...
                        continue;
                    }

                    // Ambil stok lokal saat ini
                    $oldStockFromLocal = $localProduct->stock_quantity ?? 0;
                    
                    // Ambil data stok dari Ginee melalui API individual
                    $gineeResult = $syncService->getStockFromGinee($sku);
                    
                    if (!$gineeResult || !isset($gineeResult['available_stock'])) {
                        $failed++;
                        $results[] = "❌ {$sku}: Not found in Ginee";
                        // Log kegagalan
                        // ...
                        continue;
                    }
                    
                    // Ambil stok Ginee
                    $newStockFromGinee = $gineeResult['available_stock'] ?? 0;
                    
                    // Periksa apakah perlu sinkronisasi
                    $stockChange = $newStockFromGinee - $oldStockFromLocal;
                    
                    if ($stockChange == 0) {
                        $skipped++;
                        $results[] = "⏩ {$sku}: Already in sync (Stock: {$oldStockFromLocal})";
                        // Log skip
                        // ...
                    } else {
                        // Update stok jika bukan dry run
                        if (!$this->dryRun) {
                            $localProduct->stock_quantity = $newStockFromGinee;
                            $localProduct->ginee_last_sync = now();
                            $localProduct->ginee_sync_status = 'synced';
                            $localProduct->save();
                        }
                        
                        $successful++;
                        $action = $this->dryRun ? "Would update" : "Updated";
                        $results[] = "✅ {$sku}: {$action} ({$oldStockFromLocal} → {$newStockFromGinee})";
                        // Log sukses
                        // ...
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errorMsg = $e->getMessage();
                    $results[] = "❌ {$sku}: Exception - {$errorMsg}";
                    $errors[] = "{$sku}: {$errorMsg}";
                    // Log exception
                    // ...
                }
            }
            
            // Tambahkan delay antara batch jika bukan batch terakhir
            if ($batchIndex < $totalBatches - 1 && $this->delay > 0) {
                Log::info("⏱️ [OptimizedBulkGineeSyncJob] Waiting for {$this->delay} seconds before next batch");
                sleep($this->delay);
            }
        }
        
        // Log ringkasan sinkronisasi
        $endTime = now();
        $duration = $startTime->diffInSeconds($endTime);
        
        $successRate = $totalSkus > 0 ? round(($successful / $totalSkus) * 100, 1) : 0;
        $summary = "Individual sync completed in {$duration} seconds: {$successful} updated, {$skipped} already in sync, {$failed} failed. Success rate: {$successRate}%";
        
        Log::info("✅ [OptimizedBulkGineeSyncJob] {$summary}");
        
        // Buat log ringkasan
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'sync',
            'message' => ($this->dryRun ? "DRY RUN (INDIVIDUAL) - " : "") . $summary,
            'summary' => [
                'total_skus' => $totalSkus,
                'successful' => $successful,
                'skipped' => $skipped,
                'failed' => $failed,
                'success_rate' => $successRate,
                'duration_seconds' => $duration,
                'method' => 'individual',
                'errors' => $errors
            ],
            'dry_run' => $this->dryRun,
            'completed_at' => $endTime,
            'duration_seconds' => $duration,
            'created_at' => now()
        ]);
        
        return [
            'success' => true,
            'message' => $this->dryRun ? 'Dry run completed (individual mode)' : 'Sync completed (individual mode)',
            'data' => [
                'total_requested' => $totalSkus,
                'successful' => $successful,
                'failed' => $failed,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 10), // Ambil 10 error pertama saja
                'session_id' => $sessionId,
                'duration_seconds' => $duration,
                'method' => 'individual'
            ]
        ];
    }
    
    /**
     * Handle job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('💥 [OptimizedBulkGineeSyncJob] Job failed with exception', [
            'total_skus' => count($this->skus),
            'dry_run' => $this->dryRun,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Buat log kegagalan job
        GineeSyncLog::create([
            'type' => 'job_failure',
            'status' => 'failed',
            'operation_type' => 'sync',
            'message' => "Bulk sync job failed: {$exception->getMessage()}",
            'error_message' => $exception->getMessage(),
            'dry_run' => $this->dryRun,
            'created_at' => now()
        ]);
    }
}