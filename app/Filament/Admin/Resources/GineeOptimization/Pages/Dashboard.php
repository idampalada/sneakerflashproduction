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
            $batchSize = $data['batch_size'] ?? 100;
            $delay = $data['delay_between_batches'] ?? 3;
            $maxProducts = $data['max_products'] ?? 0;

            // Build query for products to sync
            $query = Product::query();
            
            if ($onlyActive) {
                $query->where('status', 'active');
            }
            
            if ($onlyMapped) {
                $query->whereHas('gineeMappings', function ($q) {
                    $q->where('sync_enabled', true);
                });
            }
            
            if ($maxProducts > 0) {
                $query->limit($maxProducts);
            }
            
            $skus = $query->pluck('sku')->toArray();
            
            if (empty($skus)) {
                Notification::make()
                    ->title('⚠️ No Products Found')
                    ->body('No products match the selected criteria')
                    ->warning()
                    ->duration(5000)
                    ->send();
                return;
            }

            Log::info("🚀 [Ginee Optimization] Starting bulk sync via QUEUE", [
                'total_skus' => count($skus),
                'dry_run' => $dryRun,
                'only_active' => $onlyActive,
                'only_mapped' => $onlyMapped,
                'batch_size' => $batchSize
            ]);

            // ✅ ALWAYS USE BACKGROUND JOB - NO MORE SYNCHRONOUS PROCESSING
if (class_exists('\App\Jobs\GineeStockSyncJob')) {
    \App\Jobs\GineeStockSyncJob::dispatch($skus, $dryRun, $batchSize, $delay);

                
                Log::info("✅ [Ginee Optimization] Job dispatched to queue", [
                    'job_class' => 'OptimizedBulkGineeSyncJob',
                    'total_skus' => count($skus),
                    'dry_run' => $dryRun
                ]);
                
                Notification::make()
                    ->title('🚀 Sync Job Started')
                    ->body("Processing " . count($skus) . " products in background. Check logs or sync history for progress.")
                    ->info()
                    ->duration(10000)
                    ->send();
                    
            } else {
                // Fallback jika job class tidak ada
                Log::warning("⚠️ OptimizedBulkGineeSyncJob class not found, falling back to synchronous");
                
                // Process immediately for smaller datasets only
                if (count($skus) <= 50) {
                    if ($dryRun) {
                        $result = $this->performDryRunSync($skus);
                    } else {
                        $result = ['success' => false, 'message' => 'Live sync not available without background job'];
                    }
                    
                    if ($result['success']) {
                        $stats = $result['stats'];
                        $skippedCount = $stats['skipped'] ?? 0;
                        
                        Notification::make()
                            ->title($dryRun ? '🧪 Dry Run Completed' : '✅ Sync Completed')
                            ->body("Processed: {$stats['total']}, " . 
                                   ($dryRun ? "Would Update: {$stats['successful']}" : "Updated: {$stats['successful']}") . 
                                   ", Skipped: {$skippedCount}, Failed: {$stats['failed']}")
                            ->success()
                            ->duration(10000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Sync Failed')
                            ->body($result['message'] ?? 'Unknown error occurred')
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }
                } else {
                    Notification::make()
                        ->title('❌ Too Many Products')
                        ->body("Cannot process " . count($skus) . " products synchronously. Background job class not available.")
                        ->danger()
                        ->duration(8000)
                        ->send();
                }
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Exception during bulk sync dispatch", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('❌ Sync Failed')
                ->body('Exception: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }


    /**
     * Test single SKU sync
     */
    protected function testSingleSku(string $sku, bool $dryRun = true): void
    {
        try {
            Log::info("🎯 [Ginee Optimization] Testing single SKU", [
                'sku' => $sku,
                'dry_run' => $dryRun
            ]);

            $syncService = new \App\Services\GineeStockSyncService();
            $result = $syncService->syncSingleSku($sku, $dryRun);
            
            if ($result['success']) {
                Notification::make()
                    ->title($dryRun ? '🧪 Test Dry Run Completed' : '✅ Test Sync Completed')
                    ->body("SKU {$sku}: " . $result['message'])
                    ->success()
                    ->duration(5000)
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Test Failed')
                    ->body("SKU {$sku}: " . $result['message'])
                    ->danger()
                    ->duration(5000)
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error("❌ Exception during test sync for SKU: {$sku}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('❌ Test Exception')
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
}