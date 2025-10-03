<?php

namespace App\Filament\Admin\Resources\GineeOptimization\Pages;

use Filament\Pages\Page;
use Filament\Actions;
use Filament\Forms;
use App\Models\GineeSyncLog;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Filament\Support\Enums\MaxWidth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.admin.resources.ginee-optimization.pages.dashboard';
    protected static ?string $title = 'Ginee Stock Optimization';
    protected static ?string $navigationLabel = 'Ginee Optimization';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Ginee Management';

    /**
     * Mount method to initialize page data
     */
    public function mount(): void
    {
        // Initialize any required data
    }

    /**
     * Get header actions for the page
     */
    protected function getHeaderActions(): array
    {
        return [
            // Sync All Products
            Actions\Action::make('sync_all_products')
                ->label('🚀 Sync All Products')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sync All Active Products')
                ->modalDescription('This will sync stock for ALL active products in your database. This may take some time.')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->form([
                    Forms\Components\Section::make('Sync Configuration')
                        ->schema([
                            Forms\Components\Toggle::make('dry_run')
                                ->label('🧪 Dry Run (Preview Only)')
                                ->default(true)
                                ->helperText('Strongly recommended: Test with dry run first')
                                ->columnSpanFull(),
                                
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Toggle::make('only_active')
                                        ->label('📦 Only Active Products')
                                        ->default(true)
                                        ->helperText('Sync only products with status = active'),
                                        
                                    Forms\Components\Toggle::make('only_mapped')
                                        ->label('🔗 Only Mapped Products')
                                        ->default(false)
                                        ->helperText('Sync only products with Ginee mappings'),
                                ]),
                                
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('batch_size')
                                        ->label('Batch Size')
                                        ->numeric()
                                        ->default(100)
                                        ->minValue(10)
                                        ->maxValue(500)
                                        ->helperText('Products per batch'),
                                        
                                    Forms\Components\TextInput::make('delay_between_batches')
                                        ->label('Delay Between Batches (seconds)')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1)
                                        ->maxValue(10)
                                        ->helperText('Prevent API rate limiting'),
                                        
                                    Forms\Components\TextInput::make('max_products')
                                        ->label('Max Products (0 = All)')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->helperText('Limit for testing'),
                                ]),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->syncAllProducts($data);
                }),
                
            // Test Single SKU 
            Actions\Action::make('test_single_sku')
                ->label('🎯 Test Single SKU')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('sku')
                        ->label('SKU to Test')
                        ->required()
                        ->placeholder('Enter SKU (e.g., SHOE-001)')
                        ->helperText('Test sync for a specific SKU'),
                        
                    Forms\Components\Toggle::make('dry_run')
                        ->label('🧪 Dry Run')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $this->testSingleSku($data['sku'], $data['dry_run'] ?? true);
                }),
                
            // Clear Old Logs
            Actions\Action::make('clear_old_logs')
                ->label('🧹 Clear Old Logs')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Clear Old Sync Logs')
                ->modalDescription('This will delete sync logs older than the specified days.')
                ->form([
                    Forms\Components\TextInput::make('days_to_keep')
                        ->label('Keep logs from last X days')
                        ->numeric()
                        ->default(7)
                        ->minValue(1)
                        ->maxValue(90)
                        ->helperText('Logs older than this will be deleted'),
                        
                    Forms\Components\Checkbox::make('confirm_delete')
                        ->label('I understand this will permanently delete old log records')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->clearOldLogs($data['days_to_keep'] ?? 7);
                }),
                
            // View Statistics
            Actions\Action::make('view_statistics')
                ->label('📊 View Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalContent(function () {
                    return view('filament.admin.resources.ginee-optimization.modals.statistics');
                })
                ->modalHeading('Sync Statistics')
                ->modalWidth(MaxWidth::FourExtraLarge),
        ];
    }

    // ==========================================
    // CUSTOM METHODS
    // ==========================================

    /**
     * Perform dry run sync for multiple SKUs
     */
    protected function performDryRunSync(array $skus): array
    {
        $sessionId = GineeSyncLog::generateSessionId();
        $results = [];
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        
        Log::info("🧪 [Ginee Optimization] Starting DRY RUN sync", [
            'total_skus' => count($skus),
            'session_id' => $sessionId
        ]);

        foreach ($skus as $sku) {
            try {
                // 1️⃣ Get current stock from LOCAL database
                $localProduct = Product::where('sku', $sku)->first();
                
                if (!$localProduct) {
                    $failed++;
                    $results[] = "❌ {$sku}: Product not found in local database";
                    
                    GineeSyncLog::create([
                        'session_id' => $sessionId,
                        'type' => 'individual_sync',
                        'status' => 'failed',
                        'operation_type' => 'sync',
                        'sku' => $sku,
                        'message' => 'Product not found in local database',
                        'old_stock' => null,
                        'new_stock' => null,
                        'change' => null,
                        'dry_run' => true,
                        'created_at' => now()
                    ]);
                    continue;
                }

                $oldStockFromLocal = $localProduct->stock_quantity ?? 0;

                // 2️⃣ Get current stock from GINEE
                $syncService = new \App\Services\GineeStockSyncService();
                $gineeStockData = $syncService->getStockFromGinee($sku);
                
                if (!$gineeStockData) {
                    $failed++;
                    $results[] = "❌ {$sku}: Not found in Ginee";
                    
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
                        'dry_run' => true,
                        'created_at' => now()
                    ]);
                    continue;
                }

                // 3️⃣ Extract new stock from Ginee data
                $newStockFromGinee = $gineeStockData['total_available_for_sale'] ?? 
                                     $gineeStockData['available_stock'] ?? 
                                     $gineeStockData['total_stock'] ?? 0;

                // 4️⃣ Check if sync is needed
                $stockChange = $newStockFromGinee - $oldStockFromLocal;
                
                if ($stockChange == 0) {
                    $skipped++;
                    $results[] = "⏩ {$sku}: Already in sync (Stock: {$oldStockFromLocal})";
                    
                    GineeSyncLog::create([
                        'session_id' => $sessionId,
                        'type' => 'individual_sync',
                        'status' => 'success',
                        'operation_type' => 'sync',
                        'sku' => $sku,
                        'product_name' => $localProduct->name ?? $gineeStockData['product_name'] ?? 'Unknown',
                        'message' => "Dry run - Already in sync",
                        'old_stock' => $oldStockFromLocal,
                        'new_stock' => $newStockFromGinee,
                        'change' => 0,
                        'dry_run' => true,
                        'created_at' => now()
                    ]);
                } else {
                    $successful++;
                    $results[] = "✅ {$sku}: Would update ({$oldStockFromLocal} → {$newStockFromGinee})";
                    
                    GineeSyncLog::create([
                        'session_id' => $sessionId,
                        'type' => 'individual_sync',
                        'status' => 'success',
                        'operation_type' => 'sync',
                        'sku' => $sku,
                        'product_name' => $localProduct->name ?? $gineeStockData['product_name'] ?? 'Unknown',
                        'message' => "Dry run - Would update from {$oldStockFromLocal} to {$newStockFromGinee}",
                        'old_stock' => $oldStockFromLocal,
                        'new_stock' => $newStockFromGinee,
                        'change' => $stockChange,
                        'dry_run' => true,
                        'created_at' => now()
                    ]);
                }
                
            } catch (\Exception $e) {
                $failed++;
                $results[] = "❌ {$sku}: Exception - " . $e->getMessage();
                
                GineeSyncLog::create([
                    'session_id' => $sessionId,
                    'type' => 'individual_sync',
                    'status' => 'failed',
                    'operation_type' => 'sync',
                    'sku' => $sku,
                    'message' => 'Exception during dry run: ' . $e->getMessage(),
                    'error_message' => $e->getMessage(),
                    'old_stock' => null,
                    'new_stock' => null,
                    'change' => null,
                    'dry_run' => true,
                    'created_at' => now()
                ]);
            }
        }

        // Summary log
        GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'completed',
            'operation_type' => 'sync',
            'message' => "DRY RUN completed: {$successful} would be updated, {$skipped} already in sync, {$failed} failed",
            'dry_run' => true,
            'created_at' => now()
        ]);

        return [
            'success' => true,
            'results' => $results,
            'stats' => [
                'total' => count($skus),
                'successful' => $successful,
                'skipped' => $skipped,
                'failed' => $failed,
                'session_id' => $sessionId
            ]
        ];
    }

    /**
     * Sync all products with advanced options
     */
protected function syncAllProducts(array $data): void
{
    try {
        $dryRun = $data['dry_run'] ?? true;
        $onlyActive = $data['only_active'] ?? true; 
        $onlyMapped = $data['only_mapped'] ?? false;
        
        // ✅ FIX 1: INCREASE DEFAULT BATCH SIZE
        $batchSize = $data['batch_size'] ?? 100; // Changed from 50 to 100
        
        // ✅ FIX 2: REDUCE DELAY
        $delay = $data['delay_between_batches'] ?? 2; // Changed from 5 to 2 seconds
        
        // ✅ FIX 3: FIX MAX PRODUCTS LOGIC - 0 should mean ALL products
        $maxProducts = $data['max_products'] ?? 0;
        
        // Get base query
        $query = Product::query();
        
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        
        if ($onlyMapped) {
            $query->whereHas('gineeMappings');
        }
        
        // ✅ CRITICAL FIX: Only apply limit if maxProducts > 0
        // If maxProducts = 0, sync ALL products
        if ($maxProducts > 0) {
            $skus = $query->limit($maxProducts)->pluck('sku')->toArray();
        } else {
            $skus = $query->pluck('sku')->toArray();
        }
        
        if (empty($skus)) {
            Notification::make()
                ->title('⚠️ No Products to Sync')
                ->body('No products match the selected criteria')
                ->warning()
                ->send();
            return;
        }

        $sessionId = \App\Models\GineeSyncLog::generateSessionId();
        
        // Log start
        \App\Models\GineeSyncLog::create([
            'session_id' => $sessionId,
            'type' => 'bulk_sync_summary',
            'status' => 'pending',
            'operation_type' => 'stock_push',
            'method_used' => 'dashboard_sync_all',
            'message' => ($dryRun ? "🧪 DRY RUN - " : "🚀 SYNC - ") . 
                        "Starting sync for " . count($skus) . " products " .
                        "(Batch: {$batchSize}, Delay: {$delay}s, Max: " . 
                        ($maxProducts > 0 ? $maxProducts : 'ALL') . ")",
            'dry_run' => $dryRun,
            'created_at' => now()
        ]);

        // Show initial notification
        Notification::make()
            ->title($dryRun ? '🧪 Starting Dry Run...' : '🚀 Starting Sync...')
            ->body("Processing " . count($skus) . " products\nBatch: {$batchSize} | Delay: {$delay}s | Max: " . 
                   ($maxProducts > 0 ? $maxProducts : 'ALL'))
            ->info()
            ->duration(5000)
            ->send();

        // ✅ DISPATCH BACKGROUND JOB untuk proses yang panjang
        \App\Jobs\OptimizedBulkGineeSyncJob::dispatch(
            $skus, 
            $dryRun, 
            $batchSize, 
            $delay
        );

        // Success notification
        Notification::make()
            ->title('✅ Background Sync Started')
            ->body("Processing " . count($skus) . " products in background\n" .
                   "Session ID: {$sessionId}\n" .
                   "Check 'View Statistics' to monitor progress")
            ->success()
            ->duration(10000)
            ->send();

    } catch (\Exception $e) {
        Log::error("❌ Sync all products failed: " . $e->getMessage());
        
        Notification::make()
            ->title('❌ Sync Failed')
            ->body('Error: ' . $e->getMessage())
            ->danger()
            ->duration(10000)
            ->send();
    }
}


    /**
     * Test single SKU sync
     */
// Update method testSingleSku di Dashboard.php

protected function testSingleSku(string $sku, bool $dryRun = true): void
{
    try {
        Log::info("🎯 [Dashboard] Testing single SKU with fallback logic", [
            'sku' => $sku,
            'dry_run' => $dryRun
        ]);

        // PRIORITY 1: Stock Push (existing method)
        $syncService = new \App\Services\OptimizedGineeStockSyncService();
        $result = $syncService->syncSingleSku($sku, $dryRun);
        
        if ($result['success']) {
            // Stock push berhasil - gunakan method ini
            Notification::make()
                ->title($dryRun ? '🧪 Test Dry Run Completed' : '✅ Test Sync Completed')
                ->body("SKU {$sku}: " . $result['message'] . " (using stock_push)")
                ->success()
                ->duration(5000)
                ->send();
            return; // Stop di sini jika berhasil
        }

        // PRIORITY 2: Enhanced Dashboard Test (fallback only if stock_push fails)
        Log::info("🔄 [Dashboard] Stock push failed, trying enhanced fallback for SKU: {$sku}");
        
        // ✅ GET OLD STOCK FROM DATABASE (like stock_push does)
        $product = \App\Models\Product::where('sku', $sku)->first();
        $oldStock = $product ? ($product->stock_quantity ?? 0) : null;
        $oldWarehouseStock = $product ? ($product->warehouse_stock ?? 0) : null;
        $productName = $product ? $product->name : 'Product Not Found';
        
        // Try bulk optimized method as fallback
        $bulkResult = $syncService->getBulkStockFromGinee([$sku]);
        
        if ($bulkResult['success'] && isset($bulkResult['found_stock'][$sku])) {
            $stockData = $bulkResult['found_stock'][$sku];
            
            // ✅ CALCULATE STOCK CHANGE (like stock_push does)
            $newStock = $stockData['total_stock'] ?? $stockData['available_stock'] ?? 0;
            $newWarehouseStock = $stockData['warehouse_stock'] ?? 0;
            $stockChange = $oldStock !== null ? ($newStock - $oldStock) : null;
            
            // ✅ ENHANCED LOGGING WITH OLD STOCK AND CHANGE
            \App\Models\GineeSyncLog::create([
                'type' => 'enhanced_dashboard_fallback',
                'status' => $dryRun ? 'skipped' : 'success',     // ✅ Use 'skipped' for dry run
                'operation_type' => 'stock_push',               // ✅ Same as stock_push
                'method_used' => 'enhanced_dashboard_fallback', // ✅ Track method
                'sku' => $sku,
                'product_name' => $productName,
                'old_stock' => $oldStock,                       // ✅ NOW READS FROM DATABASE
                'new_stock' => $newStock,                       // ✅ From Ginee API
                'change' => $stockChange,                       // ✅ CALCULATED CHANGE
                'old_warehouse_stock' => $oldWarehouseStock,    // ✅ From database
                'new_warehouse_stock' => $newWarehouseStock,    // ✅ From Ginee API
                'message' => $dryRun ? 
                    "DRY RUN - Enhanced fallback would update from {$oldStock} to {$newStock}" . 
                    ($stockChange !== null ? " (change: {$stockChange})" : "") :
                    "SUCCESS - Enhanced fallback updated from {$oldStock} to {$newStock}" . 
                    ($stockChange !== null ? " (change: {$stockChange})" : ""),
                'ginee_response' => $stockData,                 // ✅ Store full response
                'dry_run' => $dryRun,
                'session_id' => \App\Models\GineeSyncLog::generateSessionId()
            ]);
            
            // ✅ NOTIFICATION WITH STOCK CHANGE INFO
            Notification::make()
                ->title('⚡ Enhanced Fallback Success!')
                ->body(
                    "SKU {$sku} found using enhanced fallback method. " .
                    ($oldStock !== null ? 
                        "Stock: {$oldStock} → {$newStock}" . 
                        ($stockChange !== null ? " ({$stockChange})" : "") :
                        "New stock: {$newStock}"
                    )
                )
                ->warning() // Warning color untuk indicate fallback
                ->duration(7000)
                ->send();
            return;
        }

        // Both methods failed
        Notification::make()
            ->title('❌ Test Failed - All Methods')
            ->body("SKU {$sku}: Not found using stock_push OR enhanced fallback")
            ->danger()
            ->duration(5000)
            ->send();
        
        // ✅ LOG FAILURE WITH PRODUCT INFO
        \App\Models\GineeSyncLog::create([
            'type' => 'enhanced_dashboard_fallback',
            'status' => 'failed',
            'operation_type' => 'stock_push',
            'method_used' => 'enhanced_dashboard_fallback',
            'sku' => $sku,
            'product_name' => $productName,
            'old_stock' => $oldStock,
            'new_stock' => null,
            'change' => null,
            'message' => "Enhanced fallback failed - SKU not found in Ginee",
            'error_message' => 'SKU not found using enhanced fallback methods',
            'dry_run' => $dryRun,
            'session_id' => \App\Models\GineeSyncLog::generateSessionId()
        ]);
        
    } catch (\Exception $e) {
        Log::error("❌ Exception during test sync for SKU: {$sku}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // ✅ LOG EXCEPTION WITH PROPER ERROR HANDLING
        \App\Models\GineeSyncLog::create([
            'type' => 'enhanced_dashboard_fallback',
            'status' => 'failed',
            'operation_type' => 'stock_push',
            'method_used' => 'enhanced_dashboard_fallback',
            'sku' => $sku,
            'product_name' => 'Unknown',
            'message' => "Exception during enhanced fallback: " . $e->getMessage(),
            'error_message' => $e->getMessage(),
            'dry_run' => $dryRun,
            'session_id' => \App\Models\GineeSyncLog::generateSessionId()
        ]);
        
        Notification::make()
            ->title('💥 Test Exception')
            ->body("SKU {$sku}: " . $e->getMessage())
            ->danger()
            ->duration(8000)
            ->send();
    }
}

    /**
     * Clear old sync logs
     */
    protected function clearOldLogs(int $daysToKeep): void
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);
            $deletedCount = GineeSyncLog::where('created_at', '<', $cutoffDate)->delete();
            
            Log::info("🧹 [Ginee Optimization] Cleared old logs", [
                'days_to_keep' => $daysToKeep,
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
            
            Notification::make()
                ->title('🧹 Logs Cleared')
                ->body("Deleted {$deletedCount} old log entries (older than {$daysToKeep} days)")
                ->success()
                ->duration(5000)
                ->send();
                
        } catch (\Exception $e) {
            Log::error("❌ Exception during log cleanup", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('❌ Clear Failed')
                ->body('Exception: ' . $e->getMessage())
                ->danger()
                ->duration(5000)
                ->send();
        }
    }

    /**
     * Get table stats for header display
     */
    public function getTableStats(): array
    {
        try {
            $query = GineeSyncLog::query()
                ->whereIn('type', ['individual_sync', 'bulk_optimized_sync', 'bulk_sync']);
            
            return [
                'total' => $query->count(),
                'success' => $query->where('status', 'success')->count(),
                'failed' => $query->where('status', 'failed')->count(),
                'dry_run' => $query->where('dry_run', true)->count(),
                'today' => $query->whereDate('created_at', today())->count(),
            ];
        } catch (\Exception $e) {
            Log::error("❌ Exception getting table stats", ['error' => $e->getMessage()]);
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'dry_run' => 0,
                'today' => 0,
            ];
        }
    }

    /**
     * Get page heading with stats
     */
    public function getHeading(): string
    {
        $stats = $this->getTableStats();
        return "Ginee Stock Optimization ({$stats['total']} records)";
    }

    /**
     * Get page subheading
     */
    public function getSubheading(): ?string
    {
        $stats = $this->getTableStats();
        return "Success: {$stats['success']} | Failed: {$stats['failed']} | Today: {$stats['today']}";
    }

    /**
     * Custom view data
     */
    public function getViewData(): array
    {
        return [
            'stats' => $this->getTableStats(),
        ];
    }
    protected function processSkusWithSameMethods(array $skus, bool $dryRun, int $batchSize): array
{
    $sessionId = \App\Models\GineeSyncLog::generateSessionId();
    $totalSuccessful = 0;
    $totalFailed = 0;
    $method1Successful = 0; // Stock Push success count
    $method2Successful = 0; // Enhanced Fallback success count
    
    Log::info("🔄 Processing " . count($skus) . " SKUs with same 2-method priority", [
        'session_id' => $sessionId
    ]);

    // Process in small batches to avoid memory issues
    $chunks = array_chunk($skus, $batchSize);
    
    foreach ($chunks as $chunkIndex => $chunk) {
        Log::info("📦 Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks) . " (" . count($chunk) . " SKUs)");
        
        foreach ($chunk as $sku) {
            $skuResult = $this->processSingleSkuSameMethods($sku, $dryRun, $sessionId);
            
            if ($skuResult['success']) {
                $totalSuccessful++;
                if ($skuResult['method_used'] === 'stock_push') {
                    $method1Successful++;
                } else {
                    $method2Successful++;
                }
            } else {
                $totalFailed++;
            }
            
            // Rate limiting between SKUs
            usleep(200000); // 0.2 second delay
        }
        
        // Rate limiting between chunks
        if ($chunkIndex < count($chunks) - 1) {
            sleep(2); // 2 second delay between chunks
        }
    }
    
    // ✅ CREATE BULK SUMMARY LOG
    \App\Models\GineeSyncLog::create([
        'type' => 'bulk_sync_summary',
        'status' => 'completed',
        'operation_type' => 'stock_push', // Same as single method
        'method_used' => 'same_as_single_test', // Track that we used same methods
        'message' => ($dryRun ? 'BULK DRY RUN - ' : 'BULK SYNC - ') . 
                    "Completed using same 2 methods as single test: {$totalSuccessful} successful, {$totalFailed} failed. " .
                    "Method breakdown: Stock Push ({$method1Successful}), Enhanced Fallback ({$method2Successful})",
        'dry_run' => $dryRun,
        'session_id' => $sessionId,
        'created_at' => now()
    ]);

    return [
        'total_processed' => count($skus),
        'total_successful' => $totalSuccessful,
        'total_failed' => $totalFailed,
        'method1_successful' => $method1Successful,
        'method2_successful' => $method2Successful,
        'session_id' => $sessionId
    ];
}
protected function processSingleSkuSameMethods(string $sku, bool $dryRun, string $sessionId): array
{
    try {
        Log::debug("🎯 Processing SKU {$sku} with same 2-method priority (READ ONLY)");

        // ✅ PRIORITY 1: STOCK PUSH (same as testSingleSku Priority 1)
        $syncService = new \App\Services\OptimizedGineeStockSyncService();
        $result = $syncService->syncSingleSku($sku, $dryRun);
        
        if ($result['success']) {
            // Stock push berhasil - STOP here (same as testSingleSku)
            Log::debug("✅ SKU {$sku}: Stock Push method successful (Priority 1)");
            
            return [
                'success' => true,
                'method_used' => 'stock_push',
                'message' => $result['message']
            ];
        }

        // ✅ PRIORITY 2: ENHANCED DASHBOARD FALLBACK (same as testSingleSku Priority 2)
        Log::debug("🔄 SKU {$sku}: Stock push failed, trying enhanced fallback");
        
        // ✅ GET OLD STOCK FROM DATABASE (same as fixed testSingleSku)
        $product = \App\Models\Product::where('sku', $sku)->first();
        $oldStock = $product ? ($product->stock_quantity ?? 0) : null;
        $oldWarehouseStock = $product ? ($product->warehouse_stock ?? 0) : null;
        $productName = $product ? $product->name : 'Product Not Found';
        
        if (!$product) {
            Log::debug("❌ SKU {$sku}: Product not found in database");
            return ['success' => false, 'method_used' => 'none', 'message' => 'Product not found'];
        }
        
        // Try enhanced fallback method (same as testSingleSku)
        $bulkResult = $syncService->getBulkStockFromGinee([$sku]);
        
        if ($bulkResult['success'] && isset($bulkResult['found_stock'][$sku])) {
            $stockData = $bulkResult['found_stock'][$sku];
            
            // ✅ CALCULATE STOCK CHANGE (same as fixed testSingleSku)
            $newStock = $stockData['total_stock'] ?? $stockData['available_stock'] ?? 0;
            $newWarehouseStock = $stockData['warehouse_stock'] ?? 0;
            $stockChange = $oldStock !== null ? ($newStock - $oldStock) : null;
            
            // ✅ UPDATE LOCAL DATABASE ONLY (READ ONLY - no write to external platforms)
            if (!$dryRun) {
                $product->stock_quantity = $newStock;
                $product->warehouse_stock = $newWarehouseStock;
                $product->ginee_last_sync = now();
                $product->ginee_sync_status = 'synced';
                $product->save();
                Log::debug("✅ SKU {$sku}: Updated local database only (READ ONLY)");
            }
            
            // ✅ ENHANCED LOGGING WITH OLD STOCK AND CHANGE (same as fixed testSingleSku)
            \App\Models\GineeSyncLog::create([
                'type' => 'enhanced_dashboard_fallback', // Same type as single test
                'status' => $dryRun ? 'skipped' : 'success',
                'operation_type' => 'stock_push', // Same as single test
                'method_used' => 'enhanced_dashboard_fallback', // Same as single test
                'sku' => $sku,
                'product_name' => $productName,
                'old_stock' => $oldStock,                       // ✅ Read from database
                'new_stock' => $newStock,                       // ✅ From Ginee API (READ ONLY)
                'change' => $stockChange,                       // ✅ Calculated change
                'old_warehouse_stock' => $oldWarehouseStock,    // ✅ From database
                'new_warehouse_stock' => $newWarehouseStock,    // ✅ From Ginee API (READ ONLY)
                'message' => $dryRun ? 
                    "BULK DRY RUN - Enhanced fallback would update from {$oldStock} to {$newStock}" . 
                    ($stockChange !== null ? " (change: {$stockChange})" : "") :
                    "BULK SUCCESS - Enhanced fallback updated local DB from {$oldStock} to {$newStock}" . 
                    ($stockChange !== null ? " (change: {$stockChange})" : ""),
                'ginee_response' => $stockData,
                'dry_run' => $dryRun,
                'session_id' => $sessionId
            ]);
            
            Log::debug("✅ SKU {$sku}: Enhanced fallback successful (Priority 2) - READ ONLY update");
            
            return [
                'success' => true,
                'method_used' => 'enhanced_dashboard_fallback',
                'message' => "Enhanced fallback found and updated (READ ONLY): {$oldStock} → {$newStock}"
            ];
        }

        // ✅ BOTH METHODS FAILED (same as testSingleSku)
        Log::debug("❌ SKU {$sku}: Both stock push and enhanced fallback failed");
        
        \App\Models\GineeSyncLog::create([
            'type' => 'enhanced_dashboard_fallback',
            'status' => 'failed',
            'operation_type' => 'stock_push',
            'method_used' => 'both_methods_failed',
            'sku' => $sku,
            'product_name' => $productName,
            'old_stock' => $oldStock,
            'message' => "Both stock push and enhanced fallback failed for bulk sync",
            'error_message' => 'SKU not found using either method',
            'dry_run' => $dryRun,
            'session_id' => $sessionId
        ]);
        
        return [
            'success' => false,
            'method_used' => 'both_failed',
            'message' => 'Both methods failed'
        ];
        
    } catch (\Exception $e) {
        Log::error("❌ Exception processing SKU {$sku}: " . $e->getMessage());
        
        \App\Models\GineeSyncLog::create([
            'type' => 'enhanced_dashboard_fallback',
            'status' => 'failed',
            'operation_type' => 'stock_push',
            'method_used' => 'exception',
            'sku' => $sku,
            'message' => "Exception during bulk sync: " . $e->getMessage(),
            'error_message' => $e->getMessage(),
            'dry_run' => $dryRun,
            'session_id' => $sessionId
        ]);
        
        return [
            'success' => false,
            'method_used' => 'exception',
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}


/**
 * ✅ SHOW SUMMARY NOTIFICATION
 */
protected function showBulkSyncSummary(array $results, bool $dryRun): void
{
    $message = ($dryRun ? '🧪 BULK DRY RUN' : '✅ BULK SYNC') . " COMPLETED (READ ONLY)\n\n";
    
    $message .= "📊 TOTAL RESULTS:\n";
    $message .= "✅ Successful: {$results['total_successful']}/{$results['total_processed']}\n";
    $message .= "❌ Failed: {$results['total_failed']}\n\n";
    
    $message .= "🔧 METHOD BREAKDOWN (same as single test):\n";
    $message .= "📦 Priority 1 (Stock Push): {$results['method1_successful']} successful\n";
    $message .= "🔄 Priority 2 (Enhanced Fallback): {$results['method2_successful']} successful\n";
    
    if ($results['total_failed'] > 0) {
        $message .= "\n⚠️ {$results['total_failed']} SKUs failed both methods";
    }
    
    $successRate = $results['total_processed'] > 0 ? 
        round(($results['total_successful'] / $results['total_processed']) * 100, 1) : 0;
    $message .= "\n📈 Success Rate: {$successRate}%";
    $message .= "\n🔒 READ only - no external platform updates";

    Notification::make()
        ->title($dryRun ? '🧪 Bulk Sync Preview Completed' : '✅ Bulk Sync Completed (READ ONLY)')
        ->body($message)
        ->success()
        ->duration(15000)
        ->send();
}
protected function estimateCompletionTime(int $totalSkus, int $batchSize, int $delay): string
{
    $totalBatches = ceil($totalSkus / $batchSize);
    $estimatedMinutes = ($totalBatches * $delay) / 60;
    
    if ($estimatedMinutes < 1) {
        return "< 1 minute";
    } elseif ($estimatedMinutes < 60) {
        return round($estimatedMinutes) . " minutes";
    } else {
        $hours = floor($estimatedMinutes / 60);
        $minutes = round($estimatedMinutes % 60);
        return "{$hours}h {$minutes}m";
    }
}

}