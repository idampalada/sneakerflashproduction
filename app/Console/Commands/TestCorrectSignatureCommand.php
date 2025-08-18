<?php
// File: app/Console/Commands/TestCorrectSignatureCommand.php

namespace App\Console\Commands;

use App\Services\GineeClient;
use Illuminate\Console\Command;

class TestCorrectSignatureCommand extends Command
{
    protected $signature = 'ginee:test-correct 
                            {--endpoint=all : Test specific endpoint or all}
                            {--detailed : Show detailed response data}';

    protected $description = 'Test Ginee API with CORRECT signature format from official testing tool';

    public function handle()
    {
        $this->info('🎯 Testing Ginee API with CORRECT Signature Format');
        $this->line('Using format: METHOD$PATH$ (without timestamp or content hash)');
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
            ['Base URL', config('services.ginee.base', 'https://api.ginee.com')],
            ['Signature Format', 'METHOD$PATH$ (Ginee official format)'],
        ]);

        $this->newLine();

        try {
            $ginee = new GineeClient();
            $endpoint = $this->option('endpoint');

            if ($endpoint === 'all') {
                $this->testAllEndpoints($ginee);
            } else {
                $this->testSpecificEndpoint($ginee, $endpoint);
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testAllEndpoints(GineeClient $ginee)
    {
        $this->info('🔍 Testing all endpoints with CORRECT signature format...');
        $this->newLine();

        // Test 1: Connection test (shops)
        $this->info('1️⃣ Testing Connection (Shops)...');
        $result = $ginee->testConnection();
        $this->displayConnectionResult($result);
        $this->newLine();

        if (!$result['success']) {
            $this->error('❌ Connection failed, skipping other tests');
            return;
        }

        // Test 2: Get all data overview
        $this->info('2️⃣ Testing All Endpoints Overview...');
        $overview = $ginee->getAllData();
        $this->displayOverview($overview);
        $this->newLine();

        // Test 3: Detailed individual tests
        $this->info('3️⃣ Detailed Individual Tests...');
        
        $endpoints = [
            'warehouses' => fn() => $ginee->getWarehouses(['page' => 0, 'size' => 3]),
            'categories' => fn() => $ginee->getCategories(['page' => 0, 'size' => 3]),
            'products' => fn() => $ginee->listMasterProduct(['page' => 0, 'size' => 3]),
            'orders' => fn() => $ginee->getOrders(['page' => 0, 'size' => 3]),
        ];

        foreach ($endpoints as $name => $endpoint) {
            $this->line("Testing {$name}...");
            $result = $endpoint();
            $this->displayQuickResult($name, $result);
        }
    }

    private function testSpecificEndpoint(GineeClient $ginee, string $endpoint)
    {
        $this->info("🎯 Testing specific endpoint: {$endpoint}");

        switch ($endpoint) {
            case 'connection':
                $result = $ginee->testConnection();
                $this->displayConnectionResult($result);
                break;

            case 'shops':
                $result = $ginee->getShops(['page' => 0, 'size' => 5]);
                $this->displayDetailedResult('Shops', $result);
                break;
                
            case 'warehouses':
                $result = $ginee->getWarehouses(['page' => 0, 'size' => 5]);
                $this->displayDetailedResult('Warehouses', $result);
                break;
                
            case 'categories':
                $result = $ginee->getCategories(['page' => 0, 'size' => 5]);
                $this->displayDetailedResult('Categories', $result);
                break;
                
            case 'products':
                $result = $ginee->listMasterProduct(['page' => 0, 'size' => 5]);
                $this->displayDetailedResult('Products', $result);
                break;
                
            case 'orders':
                $result = $ginee->getOrders(['page' => 0, 'size' => 5]);
                $this->displayDetailedResult('Orders', $result);
                break;

            case 'sync-test':
                $this->testSyncFunctions($ginee);
                break;

            default:
                $this->error("Unknown endpoint: {$endpoint}");
                $this->line('Available: connection, shops, warehouses, categories, products, orders, sync-test');
                return;
        }
    }

    private function testSyncFunctions(GineeClient $ginee)
    {
        $this->info('🔄 Testing Sync Functions...');
        
        // Test product sync
        $this->line('📥 Testing product sync from Ginee...');
        $result = $ginee->syncProductsFromGinee(['page' => 0, 'size' => 3]);
        $this->displayQuickResult('Product Sync', $result);
        
        $this->newLine();
        $this->line('💡 Stock push test would need actual stock data');
        $this->line('Use: $ginee->pushStockToGinee($stockUpdates) when ready');
    }

    private function displayConnectionResult(array $result)
    {
        if ($result['success']) {
            $this->line('✅ Connection test SUCCESSFUL!');
            $this->line('✅ GINEE SIGNATURE FORMAT IS NOW CORRECT!');
            $this->line('Message: ' . $result['message']);
            
            if (isset($result['transaction_id'])) {
                $this->line('Transaction ID: ' . $result['transaction_id']);
            }
            
            if ($this->option('detailed') && isset($result['data'])) {
                $this->line('Sample data: ' . json_encode(array_slice($result['data'], 0, 2), JSON_PRETTY_PRINT));
            }
        } else {
            $this->error('❌ Connection test FAILED!');
            $this->line('Message: ' . $result['message']);
            $this->line('Error code: ' . ($result['error_code'] ?? 'unknown'));
        }
    }

    private function displayDetailedResult(string $type, array $result)
    {
        if (($result['code'] ?? null) === 'SUCCESS') {
            $this->line("✅ {$type} request SUCCESSFUL!");
            
            $data = $result['data'] ?? [];
            
            if (isset($data['list'])) {
                // Paginated data
                $this->line('📊 Total: ' . ($data['total'] ?? 'N/A'));
                $this->line('📦 Items: ' . count($data['list']));
                
                if ($this->option('detailed') && !empty($data['list'])) {
                    $this->line('📝 Sample items:');
                    foreach (array_slice($data['list'], 0, 3) as $i => $item) {
                        $name = $item['productName'] ?? $item['name'] ?? $item['id'] ?? 'N/A';
                        $this->line("   " . ($i+1) . ". {$name}");
                    }
                }
            } elseif (is_array($data)) {
                // Direct array
                $this->line('📦 Items: ' . count($data));
                
                if ($this->option('detailed') && !empty($data)) {
                    $this->line('📝 Sample items:');
                    foreach (array_slice($data, 0, 3) as $i => $item) {
                        $name = $item['name'] ?? $item['id'] ?? 'N/A';
                        $this->line("   " . ($i+1) . ". {$name}");
                    }
                }
            }
            
            if (isset($result['transactionId'])) {
                $this->line('Transaction ID: ' . $result['transactionId']);
            }
            
        } else {
            $this->error("❌ {$type} request FAILED!");
            $this->line('Code: ' . ($result['code'] ?? 'unknown'));
            $this->line('Message: ' . ($result['message'] ?? 'no message'));
        }
    }

    private function displayQuickResult(string $name, array $result)
    {
        $status = ($result['code'] ?? null) === 'SUCCESS' ? '✅' : '❌';
        $message = ($result['code'] ?? null) === 'SUCCESS' ? 'Success' : ($result['message'] ?? 'Failed');
        $this->line("  {$status} {$name}: {$message}");
    }

    private function displayOverview(array $overview)
    {
        $tableData = [];
        foreach ($overview as $endpoint => $result) {
            $status = $result['success'] ? '✅ Success' : '❌ Failed';
            $count = $result['count'] ?? 0;
            $message = $result['success'] ? "{$count} items" : ($result['error'] ?? $result['message'] ?? 'Unknown error');
            
            $tableData[] = [
                ucfirst($endpoint),
                $status,
                $message,
                $result['transaction_id'] ?? 'N/A'
            ];
        }

        $this->table(['Endpoint', 'Status', 'Details', 'Transaction ID'], $tableData);

        // Summary
        $successCount = count(array_filter($overview, fn($r) => $r['success']));
        $totalCount = count($overview);

        $this->newLine();
        if ($successCount === $totalCount) {
            $this->info("🎉 ALL ENDPOINTS WORKING! ({$successCount}/{$totalCount})");
            $this->line('✅ Ginee API integration is fully functional!');
        } elseif ($successCount > 0) {
            $this->line("⚠️  PARTIAL ACCESS: {$successCount}/{$totalCount} endpoints working");
        } else {
            $this->error("❌ NO ACCESS: 0/{$totalCount} endpoints working");
        }
    }
}

// Usage examples:
/*
# Test all with correct signature
php artisan ginee:test-correct

# Test connection only
php artisan ginee:test-correct --endpoint=connection

# Test specific endpoint with details
php artisan ginee:test-correct --endpoint=products --detailed

# Test sync functions
php artisan ginee:test-correct --endpoint=sync-test
*/