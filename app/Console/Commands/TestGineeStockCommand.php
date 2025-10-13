<?php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class TestGineeStockCommand extends Command
{
    protected $signature = 'ginee:test-stock 
                            {--endpoint=all : Test specific endpoint (products, inventory, update, all)}
                            {--detailed : Show detailed response data}';

    protected $description = 'Test Ginee stock synchronization endpoints specifically';

    public function handle()
    {
        $this->info('📊 Testing Ginee Stock Synchronization Endpoints');
        $this->newLine();

        $accessKey = config('services.ginee.access_key');
        $secretKey = config('services.ginee.secret_key');

        if (!$accessKey || !$secretKey) {
            $this->error('❌ Ginee credentials not configured!');
            return 1;
        }

        $this->table(['Setting', 'Value'], [
            ['Access Key', substr($accessKey, 0, 8) . '...'],
            ['Secret Key', substr($secretKey, 0, 8) . '...'],
            ['Country', config('services.ginee.country', 'ID')],
            ['Endpoints', 'Stock Sync Specific'],
        ]);

        $this->newLine();

        try {
            $ginee = new GineeClient();
            $endpoint = $this->option('endpoint');

            switch ($endpoint) {
                case 'products':
                    $this->testMasterProducts($ginee);
                    break;
                    
                case 'inventory':
                    $this->testWarehouseInventory($ginee);
                    break;
                    
                case 'update':
                    $this->testStockUpdate($ginee);
                    break;
                    
                case 'all':
                default:
                    $this->testAllStockEndpoints($ginee);
                    break;
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testAllStockEndpoints(GineeClient $ginee)
    {
        $this->info('🔍 Testing all stock synchronization endpoints...');
        $this->newLine();

        // Test comprehensive stock sync workflow
        $result = $ginee->testStockSyncEndpoints();
        
        if (($result['code'] ?? null) === 'SUCCESS') {
            $summary = $result['data']['summary'];
            
            $this->line('📋 Stock Sync Endpoint Test Results:');
            $this->newLine();
            
            $tableData = [];
            foreach ($summary as $endpoint => $status) {
                $statusIcon = $status['success'] ? '✅' : '❌';
                $tableData[] = [
                    ucfirst(str_replace('_', ' ', $endpoint)),
                    $statusIcon . ' ' . ($status['success'] ? 'Success' : 'Failed'),
                    $status['message'],
                    $status['transaction_id'] ?? 'N/A'
                ];
            }
            
            $this->table(['Endpoint', 'Status', 'Message', 'Transaction ID'], $tableData);
            
            // Show detailed results if requested
            if ($this->option('detailed')) {
                $this->newLine();
                $this->line('📝 Detailed Results:');
                
                $detailed = $result['data']['detailed_results'];
                foreach ($detailed as $endpoint => $result) {
                    $this->line("--- {$endpoint} ---");
                    $this->line(json_encode($result, JSON_PRETTY_PRINT));
                    $this->newLine();
                }
            }
            
            // Summary
            $successCount = count(array_filter($summary, fn($s) => $s['success']));
            $totalCount = count($summary);
            
            $this->newLine();
            if ($successCount === $totalCount) {
                $this->info("🎉 ALL STOCK ENDPOINTS WORKING! ({$successCount}/{$totalCount})");
                $this->line('✅ Ready for stock synchronization!');
            } elseif ($successCount > 0) {
                $this->line("⚠️  PARTIAL: {$successCount}/{$totalCount} endpoints working");
                $this->line('💡 You can proceed with working endpoints');
            } else {
                $this->error("❌ NO ENDPOINTS WORKING: 0/{$totalCount}");
                $this->line('🔧 Check your Ginee account and API permissions');
            }
        }
    }

    private function testMasterProducts(GineeClient $ginee)
    {
        $this->info('📦 Testing Master Products Endpoint...');
        
        $result = $ginee->getMasterProducts(['page' => 0, 'size' => 5]);
        $this->displayResult('Master Products', $result);
    }

    private function testWarehouseInventory(GineeClient $ginee)
    {
        $this->info('📊 Testing Warehouse Inventory Endpoint...');
        
        $result = $ginee->getWarehouseInventory(['page' => 0, 'size' => 5]);
        $this->displayResult('Warehouse Inventory', $result);
    }

    private function testStockUpdate(GineeClient $ginee)
    {
        $this->info('📤 Testing Stock Update Endpoint...');
        $this->line('⚠️  This will attempt a test stock update (may fail with test data)');
        
        $testUpdate = [
            'warehouseId' => 'test-warehouse-id',
            'products' => [
                [
                    'masterSku' => 'TEST-SKU-001',
                    'quantity' => 10,
                    'remark' => 'Test update from Laravel command'
                ]
            ]
        ];
        
        $result = $ginee->updateWarehouseStock($testUpdate);
        $this->displayResult('Stock Update', $result);
        
        if (($result['code'] ?? null) !== 'SUCCESS') {
            $this->line('💡 This is expected to fail with test data - endpoint is working if you get a proper error response');
        }
    }

    private function displayResult(string $type, array $result)
    {
        if (($result['code'] ?? null) === 'SUCCESS') {
            $this->line("✅ {$type}: SUCCESS");
            
            $data = $result['data'] ?? [];
            if (isset($data['list'])) {
                $this->line("📊 Total: " . ($data['total'] ?? 'N/A'));
                $this->line("📦 Items: " . count($data['list']));
                
                if ($this->option('detailed') && !empty($data['list'])) {
                    $this->line('📝 Sample items:');
                    foreach (array_slice($data['list'], 0, 3) as $i => $item) {
                        $name = $item['productName'] ?? $item['masterSku'] ?? $item['name'] ?? 'N/A';
                        $this->line("   " . ($i+1) . ". {$name}");
                    }
                }
            } elseif (is_array($data)) {
                $this->line("📦 Data items: " . count($data));
            }
            
            if (isset($result['transactionId'])) {
                $this->line('🆔 Transaction ID: ' . $result['transactionId']);
            }
            
        } else {
            $this->error("❌ {$type}: FAILED");
            $this->line('💬 Code: ' . ($result['code'] ?? 'unknown'));
            $this->line('💬 Message: ' . ($result['message'] ?? 'no message'));
            
            if ($this->option('detailed')) {
                $this->line('📝 Full response:');
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            }
        }
        
        $this->newLine();
    }
}

// =============================================================================
// USAGE EXAMPLES & COMMANDS
// =============================================================================

/*

# Create the files
php artisan make:controller Frontend/GineeStockSyncController
php artisan make:command GineeStockSyncCommand --command=ginee:stock-sync
php artisan make:command TestGineeStockCommand --command=ginee:test-stock

# Test stock endpoints
php artisan ginee:test-stock
php artisan ginee:test-stock --endpoint=products --detailed
php artisan ginee:test-stock --endpoint=inventory
php artisan ginee:test-stock --endpoint=update

# Stock synchronization commands
php artisan ginee:stock-sync test
php artisan ginee:stock-sync pull --batch-size=20
php artisan ginee:stock-sync push --batch-size=10
php artisan ginee:stock-sync status

# API endpoints (authenticated)
POST /integrations/ginee/pull-products
POST /integrations/ginee/push-stock
GET /integrations/ginee/ginee-stock
GET /integrations/ginee/test-endpoints

*/