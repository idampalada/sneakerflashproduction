<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GineeWebhookController extends Controller
{
    /**
     * Global webhook endpoint for all Ginee events
     */
    public function global(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('🔔 Ginee Webhook Received', [
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'raw_body' => $request->getContent(),
                'timestamp' => now()
            ]);

            // Verify webhook signature if enabled
            if (config('services.ginee.verify_webhooks', false)) {
                if (!$this->verifyWebhookSignature($request)) {
                    Log::warning('❌ Invalid webhook signature');
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            }

            $data = $request->all();
            $eventType = $data['event_type'] ?? $data['type'] ?? 'unknown';

            // Store webhook event for debugging
            $this->storeWebhookEvent($request, $eventType);

            // Route to appropriate handler based on event type
            switch ($eventType) {
                case 'master_product_updated':
                case 'product_updated':
                    return $this->handleProductUpdate($data);

                case 'stock_updated':
                case 'inventory_updated':
                    return $this->handleStockUpdate($data);

                case 'order_created':
                case 'order_updated':
                    return $this->handleOrderUpdate($data);

                default:
                    Log::info("📝 Unhandled webhook event: {$eventType}", ['data' => $data]);
                    return $this->successResponse('Event received but not processed');
            }

        } catch (\Exception $e) {
            Log::error('❌ Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle specific order events
     */
    public function orders(Request $request)
    {
        Log::info('📋 Ginee Order Webhook', [
            'data' => $request->all(),
            'timestamp' => now()
        ]);

        try {
            $data = $request->all();
            return $this->handleOrderUpdate($data);

        } catch (\Exception $e) {
            Log::error('❌ Order webhook failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle specific master product events
     */
    public function masterProducts(Request $request)
    {
        Log::info('📦 Ginee Master Product Webhook', [
            'data' => $request->all(),
            'timestamp' => now()
        ]);

        try {
            $data = $request->all();
            return $this->handleProductUpdate($data);

        } catch (\Exception $e) {
            Log::error('❌ Master product webhook failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* ===================== WEBHOOK HANDLERS ===================== */

    /**
     * Handle product update webhook
     */
    private function handleProductUpdate(array $data): \Illuminate\Http\JsonResponse
    {
        Log::info('🔄 Processing product update webhook', ['data' => $data]);

        try {
            $productData = $data['product'] ?? $data['data'] ?? $data;
            $sku = $productData['masterSku'] ?? $productData['sku'] ?? null;

            if (!$sku) {
                Log::warning('⚠️ Product webhook missing SKU', ['data' => $data]);
                return $this->errorResponse('Missing product SKU');
            }

            DB::beginTransaction();

            $product = Product::where('sku', $sku)->first();

            if (!$product) {
                Log::info("📦 Creating new product from webhook: {$sku}");
                $product = new Product();
                $product->sku = $sku;
                $product->slug = \Str::slug(($productData['productName'] ?? 'product') . '-' . $sku);
            } else {
                Log::info("📝 Updating existing product from webhook: {$sku}");
            }

            // Update product fields
            $product->fill([
                'name' => $productData['productName'] ?? $product->name,
                'description' => $productData['description'] ?? $product->description,
                'price' => isset($productData['price']) ? (float)$productData['price'] : $product->price,
                'stock_quantity' => isset($productData['stock']) ? (int)$productData['stock'] : $product->stock_quantity,
                'weight' => isset($productData['weight']) ? (float)$productData['weight'] : $product->weight,
                'brand' => $productData['brand'] ?? $product->brand,
                'is_active' => isset($productData['status']) ? ($productData['status'] === 'ACTIVE') : $product->is_active,
                'ginee_last_sync' => now(),
                'ginee_sync_status' => 'synced',
                'ginee_data' => json_encode($productData)
            ]);

            $product->save();

            DB::commit();

            Log::info("✅ Product updated successfully: {$sku}");

            return $this->successResponse('Product updated successfully', [
                'sku' => $sku,
                'product_id' => $product->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Product update failed', [
                'error' => $e->getMessage(),
                'sku' => $sku ?? 'unknown'
            ]);

            return $this->errorResponse('Product update failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle stock update webhook
     */
    private function handleStockUpdate(array $data): \Illuminate\Http\JsonResponse
    {
        Log::info('📊 Processing stock update webhook', ['data' => $data]);

        try {
            $stockData = $data['stock'] ?? $data['inventory'] ?? $data['data'] ?? $data;
            
            // Handle multiple stock updates
            $updates = [];
            if (isset($stockData['items']) && is_array($stockData['items'])) {
                $updates = $stockData['items'];
            } elseif (isset($stockData['masterSku'])) {
                $updates = [$stockData];
            }

            if (empty($updates)) {
                Log::warning('⚠️ Stock webhook has no valid stock data', ['data' => $data]);
                return $this->errorResponse('No valid stock data found');
            }

            DB::beginTransaction();

            $updatedCount = 0;
            foreach ($updates as $item) {
                $sku = $item['masterSku'] ?? $item['sku'] ?? null;
                $quantity = $item['quantity'] ?? $item['stock'] ?? null;

                if (!$sku || $quantity === null) {
                    Log::warning('⚠️ Skipping invalid stock item', ['item' => $item]);
                    continue;
                }

                $product = Product::where('sku', $sku)->first();
                if ($product) {
                    $oldStock = $product->stock_quantity;
                    $product->stock_quantity = (int)$quantity;
                    $product->ginee_last_sync = now();
                    $product->save();

                    Log::info("📊 Stock updated: {$sku} ({$oldStock} → {$quantity})");
                    $updatedCount++;
                } else {
                    Log::warning("📦 Product not found for stock update: {$sku}");
                }
            }

            DB::commit();

            Log::info("✅ Stock update completed", ['updated_count' => $updatedCount]);

            return $this->successResponse('Stock updated successfully', [
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Stock update failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Stock update failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle order update webhook
     */
    private function handleOrderUpdate(array $data): \Illuminate\Http\JsonResponse
    {
        Log::info('📋 Processing order update webhook', ['data' => $data]);

        try {
            $orderData = $data['order'] ?? $data['data'] ?? $data;
            $gineeOrderId = $orderData['orderId'] ?? $orderData['id'] ?? null;

            if (!$gineeOrderId) {
                Log::warning('⚠️ Order webhook missing order ID', ['data' => $data]);
                return $this->errorResponse('Missing order ID');
            }

            // You can implement order sync logic here if needed
            // For now, just log the order data
            Log::info("📋 Ginee order event: {$gineeOrderId}", ['order_data' => $orderData]);

            return $this->successResponse('Order event processed', [
                'ginee_order_id' => $gineeOrderId
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Order update failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Order update failed: ' . $e->getMessage());
        }
    }

    /* ===================== HELPER METHODS ===================== */

    /**
     * Verify webhook signature (if configured)
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $secretKey = config('services.ginee.webhook_secret');
        if (!$secretKey) {
            return true; // Skip verification if no secret configured
        }

        $signature = $request->header('X-Ginee-Signature') ?? $request->header('X-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Store webhook event for debugging and audit
     */
    private function storeWebhookEvent(Request $request, string $eventType): void
    {
        try {
            DB::table('webhook_events')->insert([
                'source' => 'ginee',
                'event_type' => $eventType,
                'payload' => json_encode($request->all()),
                'headers' => json_encode($request->headers->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'processed' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('⚠️ Failed to store webhook event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Standard success response
     */
    private function successResponse(string $message, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()
        ]);
    }

    /**
     * Standard error response
     */
    private function errorResponse(string $message, int $code = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'timestamp' => now()
        ], $code);
    }
}