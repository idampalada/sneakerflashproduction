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
        
        $this->snapUrl = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
            
        $this->apiUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createSnapToken($order)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order['order_id'],
                'gross_amount' => $order['gross_amount']
            ],
            'customer_details' => [
                'first_name' => $order['customer']['first_name'],
                'last_name' => $order['customer']['last_name'],
                'email' => $order['customer']['email'],
                'phone' => $order['customer']['phone'],
                'billing_address' => [
                    'first_name' => $order['customer']['first_name'],
                    'last_name' => $order['customer']['last_name'],
                    'email' => $order['customer']['email'],
                    'phone' => $order['customer']['phone'],
                    'address' => $order['billing_address']['address'],
                    'city' => $order['billing_address']['city'],
                    'postal_code' => $order['billing_address']['postal_code'],
                    'country_code' => 'IDN'
                ],
                'shipping_address' => [
                    'first_name' => $order['customer']['first_name'],
                    'last_name' => $order['customer']['last_name'],
                    'email' => $order['customer']['email'],
                    'phone' => $order['customer']['phone'],
                    'address' => $order['shipping_address']['address'],
                    'city' => $order['shipping_address']['city'],
                    'postal_code' => $order['shipping_address']['postal_code'],
                    'country_code' => 'IDN'
                ]
            ],
            'item_details' => $order['items'],
            'callbacks' => [
                'finish' => route('checkout.finish'),
                'unfinish' => route('checkout.unfinish'),
                'error' => route('checkout.error')
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->serverKey . ':')
            ])->post($this->snapUrl, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Midtrans Snap Token Error', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Midtrans Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    public function getTransactionStatus($orderId)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->serverKey . ':')
            ])->get($this->apiUrl . '/' . $orderId . '/status');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Get Transaction Status Error', [
                'order_id' => $orderId,
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    public function handleNotification($notification)
    {
        $orderId = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $fraudStatus = $notification['fraud_status'] ?? null;

        // Verify signature
        $signatureKey = hash('sha512', 
            $orderId . 
            $notification['status_code'] . 
            $notification['gross_amount'] . 
            $this->serverKey
        );

        if ($signatureKey !== $notification['signature_key']) {
            Log::warning('Invalid Midtrans signature', ['order_id' => $orderId]);
            return false;
        }

        // Determine payment status
        $paymentStatus = 'pending';
        
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $paymentStatus = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $paymentStatus = 'paid';
            }
        } else if ($transactionStatus == 'settlement') {
            $paymentStatus = 'paid';
        } else if ($transactionStatus == 'deny') {
            $paymentStatus = 'failed';
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'expire') {
            $paymentStatus = 'cancelled';
        } else if ($transactionStatus == 'pending') {
            $paymentStatus = 'pending';
        }

        return [
            'order_id' => $orderId,
            'payment_status' => $paymentStatus,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'raw_notification' => $notification
        ];
    }
}