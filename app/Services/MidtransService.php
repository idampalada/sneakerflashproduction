<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    private $serverKey;
    private $clientKey;
    private $isProduction;
    private $snapUrl;
    private $apiUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->clientKey = config('services.midtrans.client_key');
        $this->isProduction = config('services.midtrans.is_production', false);
        
        // Set URL berdasarkan environment
        $this->snapUrl = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
            
        // API URL untuk check status
        $this->apiUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
            
        Log::info('MidtransService initialized', [
            'server_key_set' => !empty($this->serverKey),
            'client_key_set' => !empty($this->clientKey),
            'is_production' => $this->isProduction,
            'snap_url' => $this->snapUrl,
            'api_url' => $this->apiUrl
        ]);
    }

    public function createSnapToken($orderData)
    {
        try {
            // Validasi server key
            if (empty($this->serverKey)) {
                Log::error('Midtrans server key is not configured');
                return ['error' => 'Midtrans server key not configured'];
            }

            // 🆕 Network connectivity check
            $networkCheck = $this->checkMidtransConnectivity();
            $preferHosted = !$networkCheck['can_load_popup'];

            // Prepare authorization header
            $authorization = base64_encode($this->serverKey . ':');
            
            Log::info('Creating Midtrans Snap Token with network detection', [
                'order_id' => $orderData['transaction_details']['order_id'] ?? 'unknown',
                'gross_amount' => $orderData['transaction_details']['gross_amount'] ?? 0,
                'items_count' => count($orderData['item_details'] ?? []),
                'server_key_length' => strlen($this->serverKey),
                'snap_url' => $this->snapUrl,
                'network_check' => $networkCheck,
                'prefer_hosted' => $preferHosted
            ]);

            // 🆕 Safe callback URL building
            $baseUrl = rtrim(config('app.url'), '/');
            $callbacks = [
                'finish' => $baseUrl . '/checkout/payment-success',
                'unfinish' => $baseUrl . '/checkout/payment-pending',
                'error' => $baseUrl . '/checkout/payment-error'
            ];

            // Enhanced request payload with optimizations
            $payload = [
                'transaction_details' => [
                    'order_id' => $orderData['transaction_details']['order_id'],
                    'gross_amount' => (int) $orderData['transaction_details']['gross_amount']
                ],
                'item_details' => $orderData['item_details'] ?? [],
                'customer_details' => $orderData['customer_details'] ?? [],
                
                // 🆕 Optimized payment methods untuk faster loading
                'enabled_payments' => $this->getOptimizedPaymentMethods(),
                
                'credit_card' => [
                    'secure' => true,
                    'bank' => 'bca',
                    'installment' => [
                        'required' => false,
                        'terms' => [
                            'bni' => [3, 6, 12],
                            'mandiri' => [3, 6, 12],
                            'bca' => [3, 6, 12]
                        ]
                    ]
                ],
                
                // 🆕 Safe callback URLs
                'callbacks' => $callbacks,
                
                // 🆕 Performance optimizations
                'page_expiry' => [
                    'duration' => 30,
                    'unit' => 'minutes'
                ],
                
                // 🆕 Custom expiry untuk faster processing
                'custom_expiry' => [
                    'expiry_duration' => 30,
                    'unit' => 'minutes'
                ]
            ];

            // 🆕 Log with safe URLs
            Log::info('Midtrans payload prepared with optimizations', [
                'payload_keys' => array_keys($payload),
                'order_id' => $payload['transaction_details']['order_id'],
                'gross_amount' => $payload['transaction_details']['gross_amount'],
                'callbacks' => $callbacks,
                'base_url' => $baseUrl,
                'payments_count' => count($payload['enabled_payments'])
            ]);

            // 🆕 Timeout berdasarkan network condition
            $timeout = $networkCheck['has_slow_connection'] ? 45 : 30;

            // Make HTTP request to Midtrans with retry logic
            $response = $this->makeRequestWithRetry($payload, $authorization, $timeout);

            if (!$response) {
                return [
                    'error' => 'Failed to connect to Midtrans after retries',
                    'prefer_hosted' => true
                ];
            }

            $statusCode = $response->status();
            $responseBody = $response->body();
            
            Log::info('Midtrans API response received', [
                'status_code' => $statusCode,
                'response_size' => strlen($responseBody),
                'order_id' => $payload['transaction_details']['order_id'],
                'timeout_used' => $timeout
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Midtrans API success response', [
                    'order_id' => $payload['transaction_details']['order_id'],
                    'has_token' => isset($responseData['token']),
                    'has_redirect_url' => isset($responseData['redirect_url']),
                    'response_keys' => array_keys($responseData),
                    'prefer_hosted' => $preferHosted
                ]);

                if (isset($responseData['token'])) {
                    return [
                        'success' => true,
                        'token' => $responseData['token'],
                        'redirect_url' => $responseData['redirect_url'] ?? null,
                        'prefer_hosted' => $preferHosted, // 🆕 Signal untuk frontend
                        'network_info' => $networkCheck    // 🆕 Network info
                    ];
                } else {
                    Log::error('Midtrans response missing token', [
                        'response_data' => $responseData
                    ]);
                    return [
                        'error' => 'Token not found in Midtrans response',
                        'prefer_hosted' => true
                    ];
                }
            } else {
                // Handle API errors
                $errorData = $response->json();
                
                Log::error('Midtrans API error response', [
                    'status_code' => $statusCode,
                    'error_data' => $errorData,
                    'order_id' => $payload['transaction_details']['order_id']
                ]);

                $errorMessage = 'Midtrans API error';
                if (isset($errorData['error_messages'])) {
                    $errorMessage = implode(', ', $errorData['error_messages']);
                } elseif (isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                }

                return [
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'details' => $errorData,
                    'prefer_hosted' => true
                ];
            }

        } catch (\Exception $e) {
            Log::error('Midtrans service exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => 'Midtrans service error: ' . $e->getMessage(),
                'prefer_hosted' => true
            ];
        }
    }

    /**
     * 🆕 Check Midtrans connectivity and performance
     */
    private function checkMidtransConnectivity()
    {
        $startTime = microtime(true);
        $canConnect = false;
        $responseTime = 0;
        
        try {
            // Quick connectivity test
            $response = Http::timeout(5)->get($this->isProduction 
                ? 'https://app.midtrans.com' 
                : 'https://app.sandbox.midtrans.com');
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            $canConnect = $response->successful();
            
        } catch (\Exception $e) {
            Log::warning('Midtrans connectivity check failed', [
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime
            ]);
        }
        
        $hasSlowConnection = $responseTime > 3000; // > 3 seconds
        $canLoadPopup = $canConnect && !$hasSlowConnection;
        
        Log::info('Midtrans connectivity check', [
            'can_connect' => $canConnect,
            'response_time_ms' => round($responseTime, 2),
            'has_slow_connection' => $hasSlowConnection,
            'can_load_popup' => $canLoadPopup
        ]);
        
        return [
            'can_connect' => $canConnect,
            'response_time_ms' => round($responseTime, 2),
            'has_slow_connection' => $hasSlowConnection,
            'can_load_popup' => $canLoadPopup
        ];
    }

    /**
     * 🆕 Get optimized payment methods based on region
     */
    private function getOptimizedPaymentMethods()
    {
        // Prioritas payment methods yang load cepat
        return [
            'credit_card',
            'bca_va', 'bni_va', 'bri_va', 'permata_va', // Virtual Account (fast)
            'gopay', 'shopeepay',                        // E-wallet (popular)
            'indomaret', 'alfamart',                     // Convenience store
            'echannel', 'bca_klikbca',                   // Online banking
            'other_va'                                   // Other VA
        ];
    }

    /**
     * 🆕 Make request with retry logic
     */
    private function makeRequestWithRetry($payload, $authorization, $timeout = 30, $maxRetries = 2)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $authorization,
                    'User-Agent' => 'SneakerFlash/1.0'
                ])
                ->timeout($timeout)
                ->retry(2, 1000) // Built-in retry
                ->post($this->snapUrl, $payload);

                if ($response->successful()) {
                    Log::info('Midtrans request successful', [
                        'attempt' => $attempt + 1,
                        'timeout' => $timeout
                    ]);
                    return $response;
                }
                
                // If not successful but got response, return it for error handling
                if ($response->status() !== 0) {
                    return $response;
                }
                
            } catch (\Exception $e) {
                Log::warning('Midtrans request attempt failed', [
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                    'timeout' => $timeout
                ]);
                
                if ($attempt === $maxRetries - 1) {
                    throw $e;
                }
            }
            
            $attempt++;
            
            // Exponential backoff
            if ($attempt < $maxRetries) {
                usleep(1000000 * $attempt); // 1s, 2s delay
            }
        }
        
        return null;
    }

    /**
     * 🆕 Enhanced transaction status check with caching
     */
    public function getTransactionStatus($orderId, $useCache = true)
    {
        $cacheKey = "midtrans_status_{$orderId}";
        
        // Check cache first (valid for 30 seconds)
        if ($useCache && cache()->has($cacheKey)) {
            $cached = cache()->get($cacheKey);
            Log::info('Transaction status from cache', [
                'order_id' => $orderId,
                'cached_status' => $cached['transaction_status'] ?? 'unknown'
            ]);
            return $cached;
        }
        
        try {
            Log::info('🔍 Checking transaction status via API', ['order_id' => $orderId]);

            $authorization = base64_encode($this->serverKey . ':');
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authorization
            ])->timeout(15)->get($this->apiUrl . '/' . $orderId . '/status');

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache for 30 seconds
                if ($useCache) {
                    cache()->put($cacheKey, $data, 30);
                }
                
                Log::info('✅ Transaction status retrieved', [
                    'order_id' => $orderId,
                    'status' => $data['transaction_status'] ?? 'unknown',
                    'fraud_status' => $data['fraud_status'] ?? 'unknown',
                    'cached' => $useCache
                ]);

                return $data;
            } else {
                Log::warning('⚠️ Failed to get transaction status', [
                    'order_id' => $orderId,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception getting transaction status', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ... [Keep all other existing methods unchanged] ...

    /**
     * UPDATED: Handle notification webhook - now returns simplified payment status
     */
    public function handleNotification($notification)
    {
        try {
            Log::info('=== PROCESSING MIDTRANS NOTIFICATION ===', [
                'order_id' => $notification['order_id'] ?? 'unknown',
                'transaction_status' => $notification['transaction_status'] ?? 'unknown',
                'payment_type' => $notification['payment_type'] ?? 'unknown',
                'fraud_status' => $notification['fraud_status'] ?? 'unknown',
                'signature_key' => isset($notification['signature_key']) ? 'present' : 'missing'
            ]);

            // Verify notification signature
            if (!$this->verifySignature($notification)) {
                Log::error('❌ Invalid Midtrans notification signature');
                return null;
            }

            Log::info('✅ Signature verification passed');

            // Double check with Midtrans API (with caching)
            $verifiedData = $this->getTransactionStatus($notification['order_id'], true);
            if ($verifiedData) {
                Log::info('✅ Transaction verified with Midtrans API', [
                    'api_status' => $verifiedData['transaction_status'],
                    'notification_status' => $notification['transaction_status']
                ]);
                // Use API data if available (more reliable)
                $notification = array_merge($notification, $verifiedData);
            }

            // UPDATED: Map transaction status to simplified payment status
            $transactionStatus = $notification['transaction_status'] ?? '';
            $fraudStatus = $notification['fraud_status'] ?? 'accept';
            $paymentType = $notification['payment_type'] ?? '';

            $paymentStatus = $this->mapToSimplePaymentStatus($transactionStatus, $fraudStatus);

            Log::info('✅ Payment status mapped', [
                'order_id' => $notification['order_id'],
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'mapped_payment_status' => $paymentStatus
            ]);

            return [
                'order_id' => $notification['order_id'] ?? '',
                'payment_status' => $paymentStatus,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $paymentType,
                'gross_amount' => $notification['gross_amount'] ?? null,
                'transaction_time' => $notification['transaction_time'] ?? null,
                'raw_notification' => $notification
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error processing Midtrans notification', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'notification' => $notification
            ]);
            return null;
        }
    }

    /**
     * UPDATED: Map to simplified payment status for single status system
     */
    private function mapToSimplePaymentStatus($transactionStatus, $fraudStatus = 'accept')
    {
        // Handle fraud status first
        if ($fraudStatus === 'challenge') {
            return 'challenge'; // Will be mapped to 'pending' in controller
        }
        
        if ($fraudStatus === 'deny') {
            return 'failed';
        }

        // Map transaction status to simple payment status
        switch ($transactionStatus) {
            case 'capture':
                return ($fraudStatus === 'accept') ? 'paid' : 'pending';
                
            case 'settlement':
                return 'paid';
                
            case 'pending':
                return 'pending';
                
            case 'deny':
            case 'cancel':
            case 'expire':
                return 'failed';
                
            case 'refund':
            case 'partial_refund':
                return 'refunded';
                
            default:
                Log::warning('⚠️ Unknown transaction status', [
                    'transaction_status' => $transactionStatus,
                    'fraud_status' => $fraudStatus
                ]);
                return 'pending';
        }
    }

    /**
     * Verify signature
     */
    private function verifySignature($notification)
    {
        try {
            $orderId = $notification['order_id'] ?? '';
            $statusCode = $notification['status_code'] ?? '';
            $grossAmount = $notification['gross_amount'] ?? '';
            $serverKey = $this->serverKey;

            $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            $providedSignature = $notification['signature_key'] ?? '';

            $isValid = hash_equals($signatureKey, $providedSignature);

            Log::info('🔐 Signature verification', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'gross_amount' => $grossAmount,
                'calculated_signature' => substr($signatureKey, 0, 20) . '...',
                'provided_signature' => substr($providedSignature, 0, 20) . '...',
                'is_valid' => $isValid
            ]);

            return $isValid;

        } catch (\Exception $e) {
            Log::error('❌ Error verifying signature', [
                'error' => $e->getMessage(),
                'notification' => $notification
            ]);
            return false;
        }
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction($orderId)
    {
        try {
            Log::info('🚫 Cancelling transaction', ['order_id' => $orderId]);

            $authorization = base64_encode($this->serverKey . ':');
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authorization
            ])->timeout(15)->post($this->apiUrl . '/' . $orderId . '/cancel');

            if ($response->successful()) {
                Log::info('✅ Transaction cancelled successfully', ['order_id' => $orderId]);
                return $response->json();
            } else {
                Log::warning('⚠️ Failed to cancel transaction', [
                    'order_id' => $orderId,
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception cancelling transaction', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if service is properly configured
     */
    public function isConfigured()
    {
        return !empty($this->serverKey) && !empty($this->clientKey);
    }

    /**
     * 🆕 Enhanced connection test
     */
    public function testConnection()
    {
        try {
            // Test connectivity first
            $networkCheck = $this->checkMidtransConnectivity();
            
            $testPayload = [
                'transaction_details' => [
                    'order_id' => 'TEST-' . time(),
                    'gross_amount' => 100000
                ],
                'item_details' => [
                    [
                        'id' => 'test-item',
                        'price' => 100000,
                        'quantity' => 1,
                        'name' => 'Test Item'
                    ]
                ],
                'customer_details' => [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'phone' => '08123456789'
                ]
            ];

            $result = $this->createSnapToken($testPayload);
            
            return [
                'success' => isset($result['token']),
                'result' => $result,
                'network_check' => $networkCheck
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'network_check' => $this->checkMidtransConnectivity()
            ];
        }
    }
}