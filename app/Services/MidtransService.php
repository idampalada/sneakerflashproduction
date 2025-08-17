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

            // Prepare authorization header
            $authorization = base64_encode($this->serverKey . ':');
            
            Log::info('Creating Midtrans Snap Token', [
                'order_id' => $orderData['transaction_details']['order_id'] ?? 'unknown',
                'gross_amount' => $orderData['transaction_details']['gross_amount'] ?? 0,
                'items_count' => count($orderData['item_details'] ?? []),
                'server_key_length' => strlen($this->serverKey),
                'snap_url' => $this->snapUrl
            ]);

            // Enhanced request payload
            $payload = [
                'transaction_details' => [
                    'order_id' => $orderData['transaction_details']['order_id'],
                    'gross_amount' => (int) $orderData['transaction_details']['gross_amount']
                ],
                'item_details' => $orderData['item_details'] ?? [],
                'customer_details' => $orderData['customer_details'] ?? [],
                'enabled_payments' => [
                    'credit_card', 'cimb_clicks', 'bca_klikbca', 'bca_klikpay', 
                    'bri_epay', 'echannel', 'permata_va', 'bca_va', 'bni_va', 
                    'bri_va', 'other_va', 'gopay', 'indomaret', 'alfamart', 
                    'danamon_online', 'akulaku'
                ],
                'credit_card' => [
                    'secure' => true,
                    'bank' => 'bca',
                    'installment' => [
                        'required' => false,
                        'terms' => [
                            'bni' => [3, 6, 12],
                            'mandiri' => [3, 6, 12],
                            'cimb' => [3],
                            'bca' => [3, 6, 12],
                            'offline' => [6, 12]
                        ]
                    ]
                ],
                // Callback URLs
                'callbacks' => [
                    'finish' => config('app.url') . '/checkout/payment-success',
                    'unfinish' => config('app.url') . '/checkout/payment-pending', 
                    'error' => config('app.url') . '/checkout/payment-error'
                ]
            ];

            Log::info('Midtrans payload prepared', [
                'payload_keys' => array_keys($payload),
                'order_id' => $payload['transaction_details']['order_id'],
                'gross_amount' => $payload['transaction_details']['gross_amount'],
                'callbacks' => $payload['callbacks']
            ]);

            // Make HTTP request to Midtrans
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authorization,
                'User-Agent' => 'SneakerFlash/1.0'
            ])->timeout(30)->post($this->snapUrl, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            
            Log::info('Midtrans API response received', [
                'status_code' => $statusCode,
                'response_size' => strlen($responseBody),
                'order_id' => $payload['transaction_details']['order_id']
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Midtrans API success response', [
                    'order_id' => $payload['transaction_details']['order_id'],
                    'has_token' => isset($responseData['token']),
                    'has_redirect_url' => isset($responseData['redirect_url']),
                    'response_keys' => array_keys($responseData)
                ]);

                if (isset($responseData['token'])) {
                    return [
                        'success' => true,
                        'token' => $responseData['token'],
                        'redirect_url' => $responseData['redirect_url'] ?? null
                    ];
                } else {
                    Log::error('Midtrans response missing token', [
                        'response_data' => $responseData
                    ]);
                    return ['error' => 'Token not found in Midtrans response'];
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
                    'details' => $errorData
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
                'error' => 'Midtrans service error: ' . $e->getMessage()
            ];
        }
    }

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

            // Double check with Midtrans API
            $verifiedData = $this->getTransactionStatus($notification['order_id']);
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
     * Get transaction status from Midtrans API
     */
    public function getTransactionStatus($orderId)
    {
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
                
                Log::info('✅ Transaction status retrieved', [
                    'order_id' => $orderId,
                    'status' => $data['transaction_status'] ?? 'unknown',
                    'fraud_status' => $data['fraud_status'] ?? 'unknown'
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
     * Test connection ke Midtrans
     */
    public function testConnection()
    {
        try {
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
                'result' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}