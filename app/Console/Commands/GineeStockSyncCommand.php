<?php

namespace App\Console\Commands;

use App\Services\GineeStockSyncService;
use Illuminate\Console\Command;

class GineeStockSyncCommand extends Command
{
    protected $signature = 'ginee:sync-stock 
                            {action=sync : Action to perform (sync, push, both)}
                            {--sku= : Sync/push specific SKU only}
                            {--batch-size=50 : Number of SKUs to process per batch}
                            {--dry-run : Preview changes without updating database/Ginee}
                            {--only-active : Only sync/push active products (default: true)}
                            {--force : Force update all products (ignore last sync timestamp)}';

    protected $description = 'Sync stock between local database and Ginee (sync=Ginee→Local, push=Local→Ginee, both=bidirectional)';

    public function handle()
    {
        $this->info('🔄 Ginee Stock Synchronization');
        $this->newLine();

        $action = $this->argument('action');
        $sku = $this->option('sku');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');
        $onlyActive = $this->option('only-active') !== false;
        $force = $this->option('force');

        if (!in_array($action, ['sync', 'push', 'both'])) {
            $this->error('❌ Invalid action. Use: sync, push, or both');
            return 1;
        }

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No actual updates will be made');
            $this->newLine();
        }

        $this->table(['Setting', 'Value'], [
            ['Action', $action],
            ['Mode', $dryRun ? 'DRY RUN' : 'LIVE UPDATE'],
            ['Batch Size', $batchSize],
            ['Only Active Products', $onlyActive ? 'Yes' : 'No'],
            ['Force Update', $force ? 'Yes' : 'No'],
            ['Specific SKU', $sku ?: 'All SKUs'],
        ]);

        $this->newLine();

        try {
            $syncService = new GineeStockSyncService();

            if ($sku) {
                // Single SKU operation
                $this->handleSingleSku($syncService, $sku, $action, $dryRun);
            } else {
                // Bulk operation
                $this->handleBulkOperation($syncService, $action, $batchSize, $dryRun, $onlyActive, $force);
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function handleSingleSku(GineeStockSyncService $syncService, string $sku, string $action, bool $dryRun)
    {
        switch ($action) {
            case 'sync':
                $this->info("📥 Syncing single SKU from Ginee: {$sku}");
                $this->syncSingleSku($syncService, $sku, $dryRun);
                break;
                
            case 'push':
                $this->info("📤 Pushing single SKU to Ginee: {$sku}");
                $this->pushSingleSku($syncService, $sku, $dryRun);
                break;
                
            case 'both':
                $this->info("🔄 Syncing and pushing single SKU: {$sku}");
                $this->syncSingleSku($syncService, $sku, $dryRun);
                $this->newLine();
                $this->pushSingleSku($syncService, $sku, $dryRun);
                break;
        }
    }

    private function handleBulkOperation(GineeStockSyncService $syncService, string $action, int $batchSize, bool $dryRun, bool $onlyActive, bool $force)
    {
        switch ($action) {
            case 'sync':
                $this->info('📥 Starting bulk stock sync from Ginee...');
                $this->syncAllSkus($syncService, $batchSize, $dryRun, $onlyActive);
                break;
                
            case 'push':
                $this->info('📤 Starting bulk stock push to Ginee...');
                $this->pushAllSkus($syncService, $batchSize, $dryRun, $onlyActive, $force);
                break;
                
            case 'both':
                $this->info('🔄 Starting bidirectional stock sync...');
                $this->syncAllSkus($syncService, $batchSize, $dryRun, $onlyActive);
                $this->newLine();
                $this->info('📤 Now pushing local changes to Ginee...');
                $this->pushAllSkus($syncService, $batchSize, $dryRun, $onlyActive, $force);
                break;
        }
    }

    private function syncSingleSku(GineeStockSyncService $syncService, string $sku, bool $dryRun)
    {
        $result = $syncService->syncSingleSku($sku, $dryRun);
        
        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            
            if (isset($result['data']['ginee_stock'])) {
                $stock = $result['data']['ginee_stock'];
                $this->table(['Field', 'Value'], [
                    ['SKU', $stock['sku']],
                    ['Product Name', $stock['product_name']],
                    ['Warehouse Stock', $stock['warehouse_stock']],
                    ['Available Stock', $stock['available_stock']],
                    ['Spare Stock', $stock['spare_stock']],
                    ['Locked Stock', $stock['locked_stock']],
                    ['Bound Shops', $stock['bound_shops']],
                    ['Product Status', $stock['product_status']],
                ]);
            }
        } else {
            $this->error('❌ ' . $result['message']);
        }
    }

    private function pushSingleSku(GineeStockSyncService $syncService, string $sku, bool $dryRun)
    {
        $result = $syncService->pushSingleSkuToGinee($sku, $dryRun);
        
        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            
            if (isset($result['data']['ginee_response'])) {
                $gineeData = $result['data']['ginee_response'];
                $this->table(['Field', 'Value'], [
                    ['SKU', $gineeData['masterSku'] ?? 'N/A'],
                    ['Product Name', $gineeData['masterProductName'] ?? 'N/A'],
                    ['New Warehouse Stock', $gineeData['warehouseStock'] ?? 'N/A'],
                    ['New Available Stock', $gineeData['availableStock'] ?? 'N/A'],
                    ['Pushed Stock', $result['data']['local_stock'] ?? 'N/A'],
                    ['Transaction ID', $result['data']['transaction_id'] ?? 'N/A'],
                    ['Updated At', $gineeData['updateDatetime'] ?? 'N/A'],
                ]);
            }
        } else {
            $this->error('❌ ' . $result['message']);
        }
    }

    private function syncAllSkus(GineeStockSyncService $syncService, int $batchSize, bool $dryRun, bool $onlyActive)
    {
        $result = $syncService->syncStockFromGinee([
            'batch_size' => $batchSize,
            'dry_run' => $dryRun,
            'only_active' => $onlyActive
        ]);
        
        if ($result['success']) {
            $data = $result['data'];
            
            $this->newLine();
            $this->info('✅ Stock synchronization completed!');
            $this->newLine();
            
            // Summary table
            $this->table(['Metric', 'Count'], [
                ['Total Processed', $data['total_processed']],
                ['Successful Updates', $data['successful_updates']],
                ['Failed Updates', $data['failed_updates']],
                ['Not Found in Ginee', $data['not_found_in_ginee']],
            ]);

            // Show sample of updated products
            if (!empty($data['updated_products']) && count($data['updated_products']) > 0) {
                $this->newLine();
                $this->line('📦 Sample of updated products:');
                
                $sampleUpdates = array_slice($data['updated_products'], 0, 5);
                $updateTable = [];
                
                foreach ($sampleUpdates as $update) {
                    $updateTable[] = [
                        $update['sku'],
                        $update['old_stock'] ?? 'N/A',
                        $update['new_stock'] ?? 'N/A',
                        ($update['new_stock'] ?? 0) - ($update['old_stock'] ?? 0)
                    ];
                }
                
                $this->table(['SKU', 'Old Stock', 'New Stock', 'Change'], $updateTable);
            }

            // Show failed products if any
            if (!empty($data['failed_products'])) {
                $this->newLine();
                $this->warn('⚠️  Failed products:');
                foreach (array_slice($data['failed_products'], 0, 5) as $failed) {
                    $this->line("   - {$failed['sku']}: {$failed['error']}");
                }
            }

            // Show not found SKUs if any
            if (!empty($data['not_found_skus'])) {
                $this->newLine();
                $this->warn('🔍 SKUs not found in Ginee:');
                foreach (array_slice($data['not_found_skus'], 0, 10) as $notFound) {
                    $this->line("   - {$notFound}");
                }
                
                if (count($data['not_found_skus']) > 10) {
                    $remaining = count($data['not_found_skus']) - 10;
                    $this->line("   ... and {$remaining} more");
                }
            }

        } else {
            $this->error('❌ ' . $result['message']);
        }
    }

    private function pushAllSkus(GineeStockSyncService $syncService, int $batchSize, bool $dryRun, bool $onlyActive, bool $force)
    {
        $result = $syncService->pushStockToGinee([
            'batch_size' => $batchSize,
            'dry_run' => $dryRun,
            'only_active' => $onlyActive,
            'force_update' => $force
        ]);
        
        if ($result['success']) {
            $data = $result['data'];
            
            $this->newLine();
            $this->info('✅ Stock push completed!');
            $this->newLine();
            
            // Summary table
            $this->table(['Metric', 'Count'], [
                ['Total Processed', $data['total_processed']],
                ['Successful Updates', $data['successful_updates']],
                ['Failed Updates', $data['failed_updates']],
            ]);

            // Show sample of updated products
            if (!empty($data['updated_products']) && count($data['updated_products']) > 0) {
                $this->newLine();
                $this->line('📤 Sample of pushed products:');
                
                $sampleUpdates = array_slice($data['updated_products'], 0, 5);
                $updateTable = [];
                
                foreach ($sampleUpdates as $update) {
                    $updateTable[] = [
                        $update['sku'],
                        $update['stock'] ?? 'N/A',
                        $update['pushed_at'] ?? 'N/A'
                    ];
                }
                
                $this->table(['SKU', 'Pushed Stock', 'Pushed At'], $updateTable);
            }

            // Show failed products if any
            if (!empty($data['failed_products'])) {
                $this->newLine();
                $this->warn('⚠️  Failed products:');
                foreach (array_slice($data['failed_products'], 0, 5) as $failed) {
                    $this->line("   - {$failed['sku']}: {$failed['error']}");
                }
            }

        } else {
            $this->error('❌ ' . $result['message']);
        }
    }
}