<?php
// =============================================================================
// File: app/Http/Controllers/Frontend/GineeStockSyncController.php
// =============================================================================

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GineeClient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GineeStockSyncController extends Controller
{
    private GineeClient $ginee;

    public function __construct(GineeClient $ginee)
    {
        $this->ginee = $ginee;
    }

    /**
     * Pull products from Ginee and sync to local database
     */
    public function pullProducts(Request $request)
    {
        try {
            Log::info('🔄 Starting Ginee product pull');

            $batchSize = min((int)($request->get('batch_size', 50)), 100);
            $result = $this->ginee->pullAllProducts($batchSize);

            if (($result['code'] ?? null) !== 'SUCCESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to pull products from Ginee',
                    'error' => $result['message'] ?? 'Unknown error'
                ], 400);
            }

            $products = $result['data']['products'] ?? [];
            $syncedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($products as $gineeProduct) {
                try {
                    $synced = $this->syncSingleProduct($gineeProduct);
                    if ($synced) {
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'sku' => $gineeProduct['masterSku'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            Log::info("✅ Product pull completed", [
                'total_from_ginee' => count($products),
                'synced_to_local' => $syncedCount,
                'errors' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$syncedCount} products from Ginee",
                'data' => [
                    'total_from_ginee' => count($products),
                    'synced_to_local' => $syncedCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Product pull failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Product pull failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push stock updates from local database to Ginee
     */
    public function pushStock(Request $request)
    {
        try {
            Log::info('📤 Starting stock push to Ginee');

            // Get products that need stock updates
            $products = $this->getProductsNeedingStockUpdate($request);

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products need stock updates',
                    'data' => ['updated_count' => 0]
                ]);
            }

            // Prepare stock updates for Ginee format
            $stockUpdates = $this->prepareStockUpdates($products);

            // Push to Ginee in batches
            $batchSize = min((int)($request->get('batch_size', 20)), 50);
            $result = $this->ginee->pushStockUpdates($stockUpdates, $batchSize);

            if (($result['code'] ?? null) === 'SUCCESS') {
                // Mark products as synced
                $this->markProductsAsSynced($products);

                Log::info("✅ Stock push completed", $result['data']);

                return response()->json([
                    'success' => true,
                    'message' => 'Stock updates pushed to Ginee successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to push stock updates to Ginee',
                    'error' => $result['message'] ?? 'Unknown error'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ Stock push failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Stock push failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current stock status from Ginee
     */
    public function getGineeStock(Request $request)
    {
        try {
            $warehouseId = $request->get('warehouse_id');
            $batchSize = min((int)($request->get('batch_size', 50)), 100);

            $result = $this->ginee->pullAllWarehouseInventory($warehouseId, $batchSize);

            if (($result['code'] ?? null) === 'SUCCESS') {
                return response()->json([
                    'success' => true,
                    'message' => 'Ginee inventory retrieved successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get inventory from Ginee',
                    'error' => $result['message'] ?? 'Unknown error'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Ginee inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test all stock sync endpoints
     */
    public function testEndpoints()
    {
        try {
            $result = $this->ginee->testStockSyncEndpoints();

            return response()->json([
                'success' => true,
                'message' => 'Stock sync endpoints tested',
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /* ===================== PRIVATE HELPER METHODS ===================== */

    private function syncSingleProduct(array $gineeProduct): bool
    {
        $sku = $gineeProduct['masterSku'] ?? null;
        
        if (!$sku) {
            return false;
        }

        $product = Product::firstOrNew(['sku' => $sku]);

        // Map Ginee data to local product
        $product->fill([
            'name' => $gineeProduct['productName'] ?? $product->name,
            'description' => $gineeProduct['description'] ?? $product->description,
            'price' => isset($gineeProduct['price']) ? (float)$gineeProduct['price'] : $product->price,
            'weight' => isset($gineeProduct['weight']) ? (float)$gineeProduct['weight'] : $product->weight,
            'brand' => $gineeProduct['brand'] ?? $product->brand,
            'is_active' => isset($gineeProduct['status']) ? ($gineeProduct['status'] === 'ACTIVE') : true,
            'ginee_last_sync' => now(),
            'ginee_sync_status' => 'synced',
            'ginee_data' => json_encode($gineeProduct)
        ]);

        // Set slug if new product
        if (!$product->exists && !$product->slug) {
            $product->slug = \Str::slug($product->name . '-' . $sku);
        }

        return $product->save();
    }

    private function getProductsNeedingStockUpdate(Request $request)
    {
        $query = Product::whereNotNull('sku');

        if ($request->has('force_all')) {
            // Update all products
            Log::info('📤 Force updating all products');
        } elseif ($request->has('skus')) {
            // Update specific SKUs
            $skus = is_array($request->skus) ? $request->skus : [$request->skus];
            $query->whereIn('sku', $skus);
        } else {
            // Update only products that changed since last sync
            $query->where(function ($q) {
                $q->whereNull('ginee_last_sync')
                  ->orWhere('ginee_sync_status', '!=', 'synced')
                  ->orWhere('updated_at', '>', DB::raw('ginee_last_sync'));
            });
        }

        $limit = min((int)($request->get('limit', 100)), 200);
        
        return $query->limit($limit)->get(['id', 'sku', 'stock_quantity', 'name']);
    }

    private function prepareStockUpdates($products)
    {
        return $products->map(function ($product) {
            return [
                'masterSku' => $product->sku,
                'quantity' => (int)$product->stock_quantity,
                'warehouseId' => config('services.ginee.warehouse_id', 'default'),
                'remark' => 'Stock sync from Laravel at ' . now()->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    private function markProductsAsSynced($products)
    {
        $productIds = $products->pluck('id')->toArray();
        
        Product::whereIn('id', $productIds)->update([
            'ginee_last_sync' => now(),
            'ginee_sync_status' => 'synced'
        ]);
    }
}

