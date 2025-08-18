<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GineeClient
{
    private string $base;
    private string $accessKey;
    private string $secretKey;
    private string $country;
    private string $defaultWarehouseId;

    public function __construct(?array $cfg = null)
    {
        $cfg = $cfg ?: config('services.ginee', []);
        $this->base      = rtrim($cfg['base'] ?? 'https://api.ginee.com', '/');
        $this->accessKey = (string)($cfg['access_key'] ?? '');
        $this->secretKey = (string)($cfg['secret_key'] ?? '');
        $this->country   = (string)($cfg['country'] ?? 'ID');
        $this->defaultWarehouseId = (string)($cfg['warehouse_id'] ?? 'WW614C57B6E21B840001B4A467');

        if (!$this->accessKey || !$this->secretKey) {
            throw new \RuntimeException('Ginee access_key/secret_key belum terisi.');
        }
    }

    /* ===================== WORKING STOCK SYNCHRONIZATION METHODS ===================== */

    /**
     * Get Master Products from Ginee - WORKING ✅
     * Endpoint: /openapi/product/master/v1/list
     */
    public function getMasterProducts(array $params = []): array
    {
        $body = [
            'page' => $params['page'] ?? 0,
            'size' => $params['size'] ?? 50,
            'productName' => $params['productName'] ?? null,
            'sku' => $params['sku'] ?? null,
            'brand' => $params['brand'] ?? null,
            'barCode' => $params['barCode'] ?? null,
            'categoryId' => $params['categoryId'] ?? null,
            'createDateFrom' => $params['createDateFrom'] ?? null,
            'createDateTo' => $params['createDateTo'] ?? null,
        ];
        
        // Remove null values
        $body = array_filter($body, fn ($v) => !is_null($v) && $v !== '');
        
        Log::info('📦 [Ginee] Getting master products', [
            'page' => $body['page'],
            'size' => $body['size']
        ]);
        
        return $this->request('POST', '/openapi/product/master/v1/list', $body);
    }

    /**
     * Get Warehouse Inventory from Ginee - WORKING ✅  
     * Endpoint: /openapi/warehouse-inventory/v1/sku/list
     * NOTE: Tidak perlu warehouseId, akan return semua inventory
     */
    public function getWarehouseInventory(array $params = []): array
{
    // PENTING: Jangan tambahkan warehouseId ke request body
    // Berdasarkan test, endpoint ini hanya menerima page dan size
    $body = [
        'page' => $params['page'] ?? 0,
        'size' => $params['size'] ?? 50,
        // JANGAN tambahkan warehouseId - menyebabkan error!
    ];
    
    Log::info('📊 [Ginee] Getting warehouse inventory (WITHOUT warehouseId)', [
        'page' => $body['page'],
        'size' => $body['size'],
        'note' => 'warehouseId tidak boleh ada di request body'
    ]);
    
    return $this->request('POST', '/openapi/warehouse-inventory/v1/sku/list', $body);
}

    /**
     * Update Stock in Ginee - WORKING ✅
     * Endpoint: /openapi/warehouse-inventory/v1/product/stock/update
     * Format: {"warehouseId": "xxx", "stockList": [{"masterSku": "xxx", "quantity": 123}]}
     */
    public function updateStock(array $stockUpdates, string $warehouseId = null): array
    {
        $warehouseId = $warehouseId ?: $this->defaultWarehouseId;
        
        $body = [
            'warehouseId' => $warehouseId,
            'stockList' => $stockUpdates
        ];
        
        Log::info('📤 [Ginee] Updating stock', [
            'warehouse_id' => $warehouseId,
            'updates_count' => count($stockUpdates),
            'sample_sku' => $stockUpdates[0]['masterSku'] ?? 'none'
        ]);
        
        return $this->request('POST', '/openapi/warehouse-inventory/v1/product/stock/update', $body);
    }

    /**
     * Get Shops/Stores - WORKING ✅
     * Endpoint: /openapi/v3/oms/shop/list
     */
    public function getShops(array $params = []): array
    {
        $body = [
            'page' => $params['page'] ?? 0,
            'size' => $params['size'] ?? 20,
        ];
        
        return $this->request('POST', '/openapi/v3/oms/shop/list', $body);
    }

    /**
     * Get Warehouses - WORKING ✅
     * Endpoint: /openapi/warehouse/v1/search
     */
    public function getWarehouses(array $params = []): array
    {
        $body = [
            'page' => $params['page'] ?? 0,
            'size' => $params['size'] ?? 20,
        ];
        
        return $this->request('POST', '/openapi/warehouse/v1/search', $body);
    }

    /* ===================== COMPLETE SYNC WORKFLOWS ===================== */

    /**
     * Pull all products from Ginee to sync to local database
     */
    public function pullAllProducts(int $batchSize = 50): array
    {
        Log::info('🔄 [Ginee] Starting complete product pull');
        
        $allProducts = [];
        $page = 0;
        $totalFetched = 0;
        
        do {
            $result = $this->getMasterProducts([
                'page' => $page,
                'size' => $batchSize
            ]);
            
            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::error('❌ [Ginee] Failed to fetch products', [
                    'page' => $page,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
                break;
            }
            
            $data = $result['data'] ?? [];
            $products = $data['list'] ?? [];
            $total = $data['total'] ?? 0;
            
            $allProducts = array_merge($allProducts, $products);
            $totalFetched += count($products);
            
            Log::info("📦 [Ginee] Fetched page {$page}: " . count($products) . " products");
            
            $page++;
            
            // Stop if we've fetched all products or no more products
        } while (!empty($products) && $totalFetched < ($total ?? 0));
        
        Log::info("✅ [Ginee] Complete product pull finished", [
            'total_products' => count($allProducts),
            'pages_fetched' => $page
        ]);
        
        return [
            'code' => 'SUCCESS',
            'message' => 'All products fetched successfully',
            'data' => [
                'products' => $allProducts,
                'total_count' => count($allProducts),
                'pages_fetched' => $page
            ]
        ];
    }

    /**
     * Pull all inventory/stock data from Ginee
     */
    public function pullAllInventory(int $batchSize = 50): array
    {
        Log::info('📊 [Ginee] Starting complete inventory pull');
        
        $allInventory = [];
        $page = 0;
        $totalFetched = 0;
        
        do {
            $result = $this->getWarehouseInventory([
                'page' => $page,
                'size' => $batchSize
            ]);
            
            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::error('❌ [Ginee] Failed to fetch inventory', [
                    'page' => $page,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
                break;
            }
            
            $data = $result['data'] ?? [];
            $inventory = $data['content'] ?? []; // Note: use 'content' not 'list' for inventory
            $total = $data['total'] ?? 0;
            
            $allInventory = array_merge($allInventory, $inventory);
            $totalFetched += count($inventory);
            
            Log::info("📊 [Ginee] Fetched inventory page {$page}: " . count($inventory) . " items");
            
            $page++;
            
        } while (!empty($inventory) && $totalFetched < ($total ?? 0));
        
        Log::info("✅ [Ginee] Complete inventory pull finished", [
            'total_inventory' => count($allInventory),
            'pages_fetched' => $page
        ]);
        
        return [
            'code' => 'SUCCESS',
            'message' => 'All inventory fetched successfully',
            'data' => [
                'inventory' => $allInventory,
                'total_count' => count($allInventory),
                'pages_fetched' => $page
            ]
        ];
    }

    /**
     * Push stock updates to Ginee in batches
     */
    public function pushStockUpdates(array $stockUpdates, int $batchSize = 20, string $warehouseId = null): array
    {
        Log::info('📤 [Ginee] Starting batch stock updates', [
            'total_updates' => count($stockUpdates),
            'batch_size' => $batchSize
        ]);
        
        $batches = array_chunk($stockUpdates, $batchSize);
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($batches as $batchIndex => $batch) {
            Log::info("📦 [Ginee] Processing batch " . ($batchIndex + 1) . "/" . count($batches));
            
            $result = $this->updateStock($batch, $warehouseId);
            
            if (($result['code'] ?? null) === 'SUCCESS') {
                $successCount += count($batch);
                Log::info("✅ [Ginee] Batch " . ($batchIndex + 1) . " successful");
            } else {
                $failureCount += count($batch);
                Log::error("❌ [Ginee] Batch " . ($batchIndex + 1) . " failed", [
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
            }
            
            $results[] = [
                'batch' => $batchIndex + 1,
                'items' => count($batch),
                'success' => ($result['code'] ?? null) === 'SUCCESS',
                'result' => $result
            ];
            
            // Small delay between batches to avoid rate limiting
            if ($batchIndex < count($batches) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        Log::info("🎯 [Ginee] Batch stock updates completed", [
            'total_batches' => count($batches),
            'successful_items' => $successCount,
            'failed_items' => $failureCount
        ]);
        
        return [
            'code' => 'SUCCESS',
            'message' => 'Stock updates processed',
            'data' => [
                'total_batches' => count($batches),
                'successful_items' => $successCount,
                'failed_items' => $failureCount,
                'batch_results' => $results
            ]
        ];
    }

    /* ===================== SYNC HELPERS ===================== */

    /**
     * Create stock update format for Ginee
     */
    public function createStockUpdate(string $masterSku, int $quantity): array
    {
        return [
            'masterSku' => $masterSku,
            'quantity' => $quantity
        ];
    }

    /**
     * Get current stock for specific SKUs
     */
    public function getCurrentStock(array $skus): array
    {
        Log::info('📊 [Ginee] Getting current stock for specific SKUs', [
            'sku_count' => count($skus)
        ]);
        
        $stockData = [];
        $page = 0;
        $batchSize = 50;
        
        // Get all inventory and filter by SKUs
        do {
            $result = $this->getWarehouseInventory([
                'page' => $page,
                'size' => $batchSize
            ]);
            
            if (($result['code'] ?? null) !== 'SUCCESS') {
                break;
            }
            
            $inventory = $result['data']['content'] ?? [];
            
            foreach ($inventory as $item) {
                $masterSku = $item['masterVariation']['masterSku'] ?? null;
                
                if ($masterSku && in_array($masterSku, $skus)) {
                    $stockData[$masterSku] = [
                        'masterSku' => $masterSku,
                        'warehouseStock' => $item['warehouseStock'] ?? 0,
                        'availableStock' => $item['availableStock'] ?? 0,
                        'lockedStock' => $item['lockedStock'] ?? 0,
                        'productName' => $item['masterVariation']['name'] ?? 'Unknown'
                    ];
                }
            }
            
            $page++;
            
        } while (!empty($inventory) && count($stockData) < count($skus));
        
        return [
            'code' => 'SUCCESS',
            'message' => 'Stock data retrieved',
            'data' => array_values($stockData)
        ];
    }

    /* ===================== CORE REQUEST METHOD ===================== */

    private function request(string $method, string $path, array $json = []): array
    {
        $method = strtoupper($method);
        $path = '/' . ltrim($path, '/');

        // Convert body to JSON string
        $bodyStr = empty($json) ? '{}' : json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // GINEE SIGNATURE FORMAT (verified working)
        $newline = '$';
        $signStr = $method . $newline . $path . $newline;
        
        // Generate signature using HMAC-SHA256
        $signature = base64_encode(hash_hmac('sha256', $signStr, $this->secretKey, true));
        
        // Create authorization header
        $authorization = $this->accessKey . ':' . $signature;

        $headers = [
            'Authorization'   => $authorization,
            'Content-Type'    => 'application/json',
            'X-Advai-Country' => $this->country,
        ];

        Log::debug('🔐 [Ginee] Request details', [
            'method' => $method,
            'path' => $path,
            'body_length' => strlen($bodyStr)
        ]);

        try {
            $response = Http::baseUrl($this->base)
                ->timeout(60)
                ->withHeaders($headers)
                ->acceptJson()
                ->withBody($bodyStr, 'application/json')
                ->send($method, $path);

            $responseData = $response->json() ?? [];
            
            if (($responseData['code'] ?? null) === 'SUCCESS') {
                Log::debug('✅ [Ginee] Request successful', [
                    'path' => $path,
                    'transaction_id' => $responseData['transactionId'] ?? 'N/A'
                ]);
            } else {
                Log::warning('❌ [Ginee] Request failed', [
                    'path' => $path,
                    'code' => $responseData['code'] ?? 'unknown',
                    'message' => $responseData['message'] ?? 'no message'
                ]);
            }

            return $responseData ?: [
                'code' => 'HTTP_ERROR',
                'message' => "HTTP {$response->status()}: {$response->body()}",
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error('❌ [Ginee] Request exception', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return [
                'code' => 'CLIENT_ERROR',
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /* ===================== TESTING & UTILITIES ===================== */

    public function testStockSyncEndpoints(): array
    {
        Log::info('🧪 [Ginee] Testing all stock sync endpoints');
        
        $results = [];
        
        // Test 1: Get shops (basic connectivity)
        $results['shops'] = $this->getShops(['page' => 0, 'size' => 2]);
        
        // Test 2: Get master products
        $results['master_products'] = $this->getMasterProducts(['page' => 0, 'size' => 3]);
        
        // Test 3: Get warehouse inventory
        $results['warehouse_inventory'] = $this->getWarehouseInventory(['page' => 0, 'size' => 3]);
        
        // Test 4: Stock update (with real SKU that exists)
        $testStockUpdate = $this->createStockUpdate('BOX', 1600);
        $results['stock_update'] = $this->updateStock([$testStockUpdate]);
        
        // Analyze results
        $summary = [];
        foreach ($results as $test => $result) {
            $summary[$test] = [
                'success' => ($result['code'] ?? null) === 'SUCCESS',
                'message' => $result['message'] ?? 'Unknown',
                'transaction_id' => $result['transactionId'] ?? null
            ];
        }
        
        return [
            'code' => 'SUCCESS',
            'message' => 'Stock sync endpoints tested',
            'data' => [
                'summary' => $summary,
                'detailed_results' => $results
            ]
        ];
    }

    public function testAllEndpoints(): array
    {
        Log::info('🧪 [Ginee] Testing all working endpoints');
        
        $results = [];
        
        // Test all working endpoints
        $results['shops'] = $this->getShops(['page' => 0, 'size' => 2]);
        $results['warehouses'] = $this->getWarehouses(['page' => 0, 'size' => 2]);
        $results['master_products'] = $this->getMasterProducts(['page' => 0, 'size' => 3]);
        $results['warehouse_inventory'] = $this->getWarehouseInventory(['page' => 0, 'size' => 3]);
        
        // Test stock update with sample data
        $testStockUpdate = $this->createStockUpdate('BOX', 1600);
        $results['stock_update'] = $this->updateStock([$testStockUpdate]);
        
        return [
            'code' => 'SUCCESS',
            'message' => 'All endpoints tested',
            'data' => $results
        ];
    }
}