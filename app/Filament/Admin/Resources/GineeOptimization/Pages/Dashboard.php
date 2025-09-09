<?php

namespace App\Filament\Admin\Resources\GineeOptimization\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use App\Services\GineeStockSyncService;
use App\Models\GineeSyncLog; // ADD: Import model
use Illuminate\Support\Facades\Log;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    
    protected static string $view = 'filament.admin.resources.ginee-optimization.pages.dashboard';
    
    protected static ?string $navigationLabel = 'Ginee Optimization';
    
    protected static ?string $title = 'Ginee Sync Optimization';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 15;
    
    protected static ?string $slug = 'ginee-optimization';

    public static function canAccess(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickSync')
                ->label('Quick Sync Test')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->form([
                    Section::make('Quick Sync Test')
                        ->description('Test sync functionality with sample SKUs')
                        ->schema([
                            Textarea::make('skus')
                                ->label('SKU List')
                                ->placeholder("BOX\n197375689975\nSKU001")
                                ->default("BOX\n197375689975")
                                ->required()
                                ->rows(5)
                                ->helperText('Enter SKUs one per line or comma-separated'),
                            Toggle::make('dry_run')
                                ->label('Dry Run (Safe Mode)')
                                ->default(true)
                                ->helperText('Preview only - no data changes'),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->processQuickSyncWithLogging($data);
                }),
        ];
    }

    protected function processQuickSyncWithLogging(array $data): void
    {
        try {
            $skusInput = $data['skus'];
            $skus = array_filter(array_map('trim', preg_split('/[,\n\r\t\s]+/', $skusInput)));
            $dryRun = $data['dry_run'] ?? true;
            
            if (empty($skus)) {
                Notification::make()
                    ->title('No Valid SKUs')
                    ->body('Please enter at least one valid SKU')
                    ->warning()
                    ->send();
                return;
            }

            $skus = array_slice($skus, 0, 10); // Max 10 for safety
            $sessionId = \Illuminate\Support\Str::uuid();

            Log::info('[Filament] Starting quick sync test', [
                'skus' => $skus,
                'dry_run' => $dryRun,
                'user' => auth()->id(),
                'session_id' => $sessionId
            ]);

            // CREATE SESSION LOG ENTRY
            $sessionLog = GineeSyncLog::create([
                'session_id' => $sessionId,
                'type' => 'filament_quick_sync',
                'status' => 'started',
                'operation_type' => 'sync',
                'started_at' => now(),
                'initiated_by_user' => auth()->id(),
                'dry_run' => $dryRun,
                'message' => $dryRun ? 'Dry run quick sync started' : 'Live quick sync started'
            ]);

            $syncService = new GineeStockSyncService();
            $successful = 0;
            $failed = 0;
            $results = [];
            
            $startTime = microtime(true);
            
            foreach ($skus as $sku) {
                try {
                    if ($dryRun) {
                        $result = $syncService->getStockFromGinee($sku);
                        if ($result) {
                            $successful++;
                            $stockInfo = $result['total_stock'] ?? 'N/A';
                            $results[] = "✅ {$sku}: Found in Ginee (stock: {$stockInfo})";
                            
                            // LOG INDIVIDUAL SUCCESS (DRY RUN)
                            GineeSyncLog::create([
                                'session_id' => $sessionId,
                                'type' => 'individual_sync',
                                'status' => 'success',
                                'operation_type' => 'sync',
                                'sku' => $sku,
                                'product_name' => $result['product_name'] ?? 'Unknown',
                                'old_stock' => null,
                                'new_stock' => $result['total_stock'] ?? null,
                                'message' => "Dry run - Found in Ginee (stock: {$stockInfo})",
                                'dry_run' => true,
                                'created_at' => now()
                            ]);
                        } else {
                            $failed++;
                            $results[] = "❌ {$sku}: Not found in Ginee";
                            
                            // LOG INDIVIDUAL FAILURE (DRY RUN)
                            GineeSyncLog::create([
                                'session_id' => $sessionId,
                                'type' => 'individual_sync',
                                'status' => 'failed',
                                'operation_type' => 'sync',
                                'sku' => $sku,
                                'message' => 'Dry run - SKU not found in Ginee',
                                'error_message' => 'Product not found in Ginee inventory',
                                'dry_run' => true,
                                'created_at' => now()
                            ]);
                        }
                    } else {
                        // LIVE SYNC
                        $result = $syncService->syncSingleSku($sku, false);
                        if ($result['success']) {
                            $successful++;
                            $results[] = "✅ {$sku}: " . $result['message'];
                            
                            // LOG INDIVIDUAL SUCCESS (LIVE)
                            GineeSyncLog::create([
                                'session_id' => $sessionId,
                                'type' => 'individual_sync',
                                'status' => 'success',
                                'operation_type' => 'sync',
                                'sku' => $sku,
                                'message' => $result['message'],
                                'dry_run' => false,
                                'created_at' => now()
                            ]);
                        } else {
                            $failed++;
                            $results[] = "❌ {$sku}: " . $result['message'];
                            
                            // LOG INDIVIDUAL FAILURE (LIVE)
                            GineeSyncLog::create([
                                'session_id' => $sessionId,
                                'type' => 'individual_sync',
                                'status' => 'failed',
                                'operation_type' => 'sync',
                                'sku' => $sku,
                                'message' => $result['message'],
                                'error_message' => $result['message'],
                                'dry_run' => false,
                                'created_at' => now()
                            ]);
                        }
                    }
                    
                    usleep(200000); // 0.2 seconds delay
                    
                } catch (\Exception $e) {
                    $failed++;
                    $results[] = "❌ {$sku}: Exception - " . $e->getMessage();
                    
                    // LOG EXCEPTION
                    GineeSyncLog::create([
                        'session_id' => $sessionId,
                        'type' => 'individual_sync',
                        'status' => 'failed',
                        'operation_type' => 'sync',
                        'sku' => $sku,
                        'message' => 'Exception occurred during sync',
                        'error_message' => $e->getMessage(),
                        'dry_run' => $dryRun,
                        'created_at' => now()
                    ]);
                    
                    Log::error("Quick sync error for SKU {$sku}: " . $e->getMessage());
                }
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            $speed = count($skus) > 0 ? round(count($skus) / $duration, 2) : 0;

            // UPDATE SESSION LOG
            $sessionLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'items_processed' => count($skus),
                'items_successful' => $successful,
                'items_failed' => $failed,
                'summary' => json_encode([
                    'successful' => $successful,
                    'failed' => $failed,
                    'duration' => $duration,
                    'speed' => $speed,
                    'results' => array_slice($results, 0, 5) // First 5 results
                ]),
                'message' => $dryRun 
                    ? "Dry run completed: {$successful} found, {$failed} not found" 
                    : "Live sync completed: {$successful} successful, {$failed} failed"
            ]);

            $mode = $dryRun ? 'DRY RUN' : 'LIVE SYNC';
            $title = "Quick Sync Test Completed";
            
            $message = "{$mode} Results:\n\n";
            $message .= "Summary:\n";
            $message .= "✅ Successful: {$successful}\n";
            $message .= "❌ Failed: {$failed}\n";
            $message .= "📊 Total: " . count($skus) . "\n";
            $message .= "⏱️ Duration: {$duration}s\n";
            $message .= "🚀 Speed: {$speed} SKUs/sec\n";
            $message .= "📋 Session ID: " . substr($sessionId, 0, 8) . "...\n\n";
            
            if (!empty($results)) {
                $message .= "Details:\n";
                foreach (array_slice($results, 0, 5) as $result) {
                    $message .= $result . "\n";
                }
                if (count($results) > 5) {
                    $remaining = count($results) - 5;
                    $message .= "... and {$remaining} more results\n";
                }
            }

            Notification::make()
                ->title($title)
                ->body($message)
                ->success()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('viewLogs')
                        ->label('View Detailed Logs')
                        ->url(route('filament.admin.resources.ginee-sync-logs.index'))
                        ->openUrlInNewTab(),
                ])
                ->send();

            Log::info('[Filament] Quick sync test completed', [
                'session_id' => $sessionId,
                'successful' => $successful,
                'failed' => $failed,
                'duration' => $duration,
                'speed' => $speed
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Sync Test Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();

            Log::error('[Filament] Quick sync test failed: ' . $e->getMessage());
        }
    }
}