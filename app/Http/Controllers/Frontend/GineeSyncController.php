<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GineeClient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GineeSyncController extends Controller
{
    private GineeClient $ginee;

    public function __construct(GineeClient $gineeClient)
    {
        $this->ginee = $gineeClient;
    }

    /**
     * Pull products from Ginee and sync to local database
     */
    public function pullProducts(Request $request)
    {
        try {
            Log::info('🔄 Starting Ginee product sync...');

            $page = (int)($request->get('page', 0));
            $size = min((int)($request->get('size', 50)), 100); // Max 100 per request
            $totalSynced = 0;
            $errors = [];

            // Get products from Ginee
            $result = $this->ginee->listMasterProduct([
                'page' => $page,
                'size' => $size,
            ]);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                Log::error('❌ Failed to fetch products from Ginee', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'response' => $result
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch products from Ginee: ' . ($result['message'] ?? 'Unknown error'),
                    'details' => $result
                ], 400);
            }

            $data = $result['data'] ?? [];
            $products = $data['list'] ?? [];
            $total = $data['total'] ?? 0;

            Log::info("📦 Fetched {count} products from Ginee (page {page}, total: {total})", [
                'count' => count($products),
                'page' => $page,
                'total' => $total
            ]);

            // Sync each product
            DB::beginTransaction();
            
            foreach ($products as $gineeProduct) {
                try {
                    $synced = $this->syncSingleProduct($gineeProduct);
                    if ($synced) {
                        $totalSynced++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'sku' => $gineeProduct['masterSku'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::warning('⚠️ Failed to sync product', [
                        'sku' => $gineeProduct['masterSku'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info("✅ Ginee product sync completed", [
                'total_processed' => count($products),
                'total_synced' => $totalSynced,
                'errors' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$totalSynced} products",
                'data' => [
                    'page' => $page,
                    'total_from_ginee' => $total,
                    'processed' => count($products),
                    'synced' => $totalSynced,
                    'errors' => $errors,
                    'has_more' => ($page + 1) * $size < $total
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Ginee product sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Product sync failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push stock updates to Ginee
     */
    public function pushStock(Request $request)
    {
        try {
            $warehouseId = $request->get('warehouse_id') ?: config('services.ginee.warehouse_id');
            
            if (!$warehouseId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse ID is required. Set GINEE_WAREHOUSE_ID in .env or pass warehouse_id parameter.'
                ], 400);
            }

            Log::info('📤 Starting stock push to Ginee', ['warehouse_id' => $warehouseId]);

            // Get products that need stock update
            $productsToUpdate = $this->getProductsNeedingStockUpdate($request);

            if ($productsToUpdate->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products need stock updates',
                    'data' => ['updated_count' => 0]
                ]);
            }

            // Prepare stock data for Ginee
            $stockItems = $productsToUpdate->map(function ($product) {
                return [
                    'masterSku' => $product->sku,
                    'quantity' => (int)$product->stock_quantity,
                    'remark' => 'Stock sync from Laravel at ' . now()->format('Y-m-d H:i:s')
                ];
            })->toArray();

            // Send to Ginee in batches (max 20 items per request)
            $batches = array_chunk($stockItems, 20);
            $totalUpdated = 0;
            $errors = [];

            foreach ($batches as $batchIndex => $batch) {
                try {
                    Log::info("📦 Sending stock batch {batch}/{total}", [
                        'batch' => $batchIndex + 1,
                        'total' => count($batches),
                        'items' => count($batch)
                    ]);

                    $result = $this->ginee->adjustInventory($warehouseId, $batch);

                    if (($result['code'] ?? null) === 'SUCCESS') {
                        $totalUpdated += count($batch);
                        
                        // Mark products as synced
                        $skus = collect($batch)->pluck('masterSku')->toArray();
                        Product::whereIn('sku', $skus)->update([
                            'ginee_last_sync' => now(),
                            'ginee_sync_status' => 'synced'
                        ]);
                        
                        Log::info("✅ Stock batch sent successfully", [
                            'batch' => $batchIndex + 1,
                            'items' => count($batch)
                        ]);
                    } else {
                        $errors[] = [
                            'batch' => $batchIndex + 1,
                            'error' => $result['message'] ?? 'Unknown error',
                            'items' => count($batch)
                        ];
                        
                        Log::error("❌ Stock batch failed", [
                            'batch' => $batchIndex + 1,
                            'error' => $result['message'] ?? 'Unknown error',
                            'response' => $result
                        ]);
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'batch' => $batchIndex + 1,
                        'error' => $e->getMessage(),
                        'items' => count($batch)
                    ];
                    
                    Log::error("❌ Stock batch exception", [
                        'batch' => $batchIndex + 1,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info("🎯 Stock push completed", [
                'total_items' => count($stockItems),
                'total_updated' => $totalUpdated,
                'errors' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$totalUpdated} products in Ginee",
                'data' => [
                    'total_items' => count($stockItems),
                    'updated_count' => $totalUpdated,
                    'batch_count' => count($batches),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Stock push failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Stock push failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Ginee warehouses
     */
    public function getWarehouses(Request $request)
    {
        try {
            // Cache warehouses for 1 hour
            $warehouses = Cache::remember('ginee_warehouses', 3600, function () {
                $result = $this->ginee->getWarehouses();
                return ($result['code'] ?? null) === 'SUCCESS' ? ($result['data'] ?? []) : [];
            });

            return response()->json([
                'success' => true,
                'data' => $warehouses
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to get warehouses', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get warehouses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync status and statistics
     */
    public function getSyncStatus(Request $request)
    {
        try {
            $stats = [
                'local_products' => Product::count(),
                'synced_products' => Product::whereNotNull('ginee_last_sync')->count(),
                'pending_sync' => Product::where(function ($q) {
                    $q->whereNull('ginee_last_sync')
                      ->orWhere('ginee_sync_status', '!=', 'synced')
                      ->orWhere('updated_at', '>', DB::raw('ginee_last_sync'));
                })->count(),
                'last_sync' => Product::max('ginee_last_sync'),
                'sync_errors' => Product::where('ginee_sync_status', 'error')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync status: ' . $e->getMessage()
            ], 500);
        }
    }

    /* ===================== PRIVATE HELPER METHODS ===================== */

    /**
     * Sync a single product from Ginee to local database
     */
    private function syncSingleProduct(array $gineeProduct): bool
    {
        $sku = $gineeProduct['masterSku'] ?? null;
        
        if (!$sku) {
            Log::warning('⚠️ Product missing SKU', ['product' => $gineeProduct]);
            return false;
        }

        // Find or create product
        $product = Product::firstOrNew(['sku' => $sku]);

        // Map Ginee data to local product
        $product->fill([
            'name' => $gineeProduct['productName'] ?? $product->name,
            'description' => $gineeProduct['description'] ?? $product->description,
            'price' => isset($gineeProduct['price']) ? (float)$gineeProduct['price'] : $product->price,
            'stock_quantity' => isset($gineeProduct['stock']) ? (int)$gineeProduct['stock'] : $product->stock_quantity,
            'weight' => isset($gineeProduct['weight']) ? (float)$gineeProduct['weight'] : $product->weight,
            'brand' => $gineeProduct['brand'] ?? $product->brand,
            'category' => $gineeProduct['categoryName'] ?? $product->category,
            'is_active' => isset($gineeProduct['status']) ? ($gineeProduct['status'] === 'ACTIVE') : $product->is_active,
            'ginee_last_sync' => now(),
            'ginee_sync_status' => 'synced',
            'ginee_data' => json_encode($gineeProduct) // Store original data for reference
        ]);

        // Set slug if new product
        if (!$product->exists && !$product->slug) {
            $product->slug = \Str::slug($product->name . '-' . $sku);
        }

        $saved = $product->save();

        if ($saved) {
            Log::debug('✅ Product synced', [
                'sku' => $sku,
                'name' => $product->name,
                'was_new' => $product->wasRecentlyCreated
            ]);
        }

        return $saved;
    }

    /**
     * Get products that need stock update
     */
    private function getProductsNeedingStockUpdate(Request $request)
    {
        $query = Product::whereNotNull('sku');

        // Filter options
        if ($request->has('force_all')) {
            // Update all products
            Log::info('📤 Force updating all products');
        } elseif ($request->has('sku')) {
            // Update specific SKUs
            $skus = is_array($request->sku) ? $request->sku : [$request->sku];
            $query->whereIn('sku', $skus);
            Log::info('📤 Updating specific SKUs', ['skus' => $skus]);
        } else {
            // Update only products that changed since last sync
            $query->where(function ($q) {
                $q->whereNull('ginee_last_sync')
                  ->orWhere('ginee_sync_status', '!=', 'synced')
                  ->orWhere('updated_at', '>', DB::raw('ginee_last_sync'));
            });
            Log::info('📤 Updating changed products only');
        }

        // Limit the number of products to update
        $limit = min((int)($request->get('limit', 100)), 200);
        
        return $query->limit($limit)->get(['id', 'sku', 'stock_quantity', 'name']);
    }
}