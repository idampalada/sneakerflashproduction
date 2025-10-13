<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestRealRajaOngkirCommand extends Command
{
    protected $signature = 'rajaongkir:test-real 
                            {--destination=66274 : Destination ID to test (default: Bandung)}
                            {--weight=1000 : Package weight in grams}
                            {--debug : Show detailed debug information}';
    
    protected $description = 'Test REAL RajaOngkir API shipping calculation - No Fallback';

    private $apiKey;
    private $baseUrl;
    private $originId;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = env('RAJAONGKIR_API_KEY');
        $this->baseUrl = env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1');
        $this->originId = env('STORE_ORIGIN_CITY_ID', 17549);
    }

    public function handle()
    {
        $this->info('🔍 REAL RajaOngkir API Test - No Fallback Mode');
        $this->newLine();

        // Configuration check
        if (!$this->validateConfiguration()) {
            return 1;
        }

        $destinationId = $this->option('destination');
        $weight = $this->option('weight');
        $debug = $this->option('debug');

        $this->info("🧪 Test Parameters:");
        $this->line("   Origin ID: {$this->originId}");
        $this->line("   Destination ID: {$destinationId}");
        $this->line("   Weight: {$weight}g");
        $this->line("   API URL: {$this->baseUrl}/calculate/domestic-cost");
        $this->newLine();

        // Test 1: Basic connection
        if (!$this->testBasicConnection()) {
            $this->error('❌ Basic connection failed. Stopping tests.');
            return 1;
        }

        // Test 2: Search destination to verify ID
        $this->testDestinationSearch($destinationId);

        // Test 3: Real shipping calculation
        $result = $this->testRealShippingCalculation($destinationId, $weight, $debug);

        if ($result) {
            $this->newLine();
            $this->info('✅ All tests completed successfully!');
            $this->line('🎯 Your RajaOngkir integration is working correctly.');
            return 0;
        } else {
            $this->newLine();
            $this->error('❌ Shipping calculation test failed!');
            $this->line('💡 Check the error details above and verify your configuration.');
            return 1;
        }
    }

    private function validateConfiguration()
    {
        $this->line('📋 Configuration Check:');
        
        $checks = [
            ['API Key', !empty($this->apiKey), $this->apiKey ? 'Set (' . substr($this->apiKey, 0, 8) . '...)' : 'NOT SET'],
            ['Base URL', !empty($this->baseUrl), $this->baseUrl],
            ['Origin ID', !empty($this->originId), $this->originId],
            ['Origin Name', !empty(env('STORE_ORIGIN_CITY_NAME')), env('STORE_ORIGIN_CITY_NAME', 'NOT SET')],
        ];

        $allValid = true;
        foreach ($checks as [$name, $valid, $value]) {
            $icon = $valid ? '✅' : '❌';
            $this->line("   {$icon} {$name}: {$value}");
            if (!$valid) $allValid = false;
        }

        $this->newLine();
        return $allValid;
    }

    private function testBasicConnection()
    {
        $this->info('🔗 Testing Basic API Connection...');
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/province');

            if ($response->successful()) {
                $data = $response->json();
                $provinceCount = isset($data['data']) ? count($data['data']) : 0;
                
                $this->line("   ✅ Connection successful");
                $this->line("   📊 Status: {$response->status()}");
                $this->line("   🏛️ Provinces loaded: {$provinceCount}");
                return true;
            } else {
                $this->line("   ❌ Connection failed");
                $this->line("   📊 Status: {$response->status()}");
                $this->line("   📄 Response: " . substr($response->body(), 0, 200));
                return false;
            }
        } catch (\Exception $e) {
            $this->line("   ❌ Exception: " . $e->getMessage());
            return false;
        }
    }

    private function testDestinationSearch($destinationId)
    {
        $this->info('🔍 Verifying Destination ID...');
        
        try {
            // Search for common terms to find the destination
            $searchTerms = ['bandung', 'jakarta', 'surabaya'];
            
            foreach ($searchTerms as $term) {
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/destination/domestic-destination', [
                    'search' => $term,
                    'limit' => 5
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data'])) {
                        foreach ($data['data'] as $location) {
                            if ($location['id'] == $destinationId) {
                                $this->line("   ✅ Destination found: {$location['label']} (ID: {$destinationId})");
                                return true;
                            }
                        }
                    }
                }
            }
            
            $this->line("   ⚠️ Destination ID {$destinationId} not found in search results");
            $this->line("   💡 This might still work if the ID is valid");
            
        } catch (\Exception $e) {
            $this->line("   ⚠️ Search failed: " . $e->getMessage());
        }
    }

    private function testRealShippingCalculation($destinationId, $weight, $debug)
    {
        $this->info('🚚 Testing REAL Shipping Calculation...');
        
        try {
            $startTime = microtime(true);
            
            $this->line("   📡 Making API request...");
            
            $response = Http::asForm()
                ->withHeaders([
                    'accept' => 'application/json',
                    'key' => $this->apiKey,
                    'user-agent' => 'Laravel-Test-Command'
                ])
                ->timeout(30)
                ->post($this->baseUrl . '/calculate/domestic-cost', [
                    'origin' => $this->originId,
                    'destination' => $destinationId,
                    'weight' => $weight,
                    'courier' => 'jne'
                ]);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->line("   ⏱️ Execution time: {$executionTime}ms");
            $this->line("   📊 Response status: {$response->status()}");

            if ($debug) {
                $this->line("   🔍 Response headers: " . json_encode($response->headers()));
            }

            if (!$response->successful()) {
                $this->line("   ❌ API request failed");
                $this->line("   📄 Error response: " . $response->body());
                return false;
            }

            $data = $response->json();
            
            if ($debug) {
                $this->line("   🔍 Raw response: " . json_encode($data, JSON_PRETTY_PRINT));
            }

            // Validate response structure
            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->line("   ❌ Invalid response structure");
                $this->line("   📄 Response keys: " . implode(', ', array_keys($data)));
                return false;
            }

            if (empty($data['data'])) {
                $this->line("   ❌ No shipping options returned");
                $this->line("   📄 Response meta: " . json_encode($data['meta'] ?? 'No meta'));
                return false;
            }

            // Success! Display options
            $optionCount = count($data['data']);
            $this->line("   ✅ SUCCESS! Found {$optionCount} shipping options:");

            foreach ($data['data'] as $index => $option) {
                $cost = number_format($option['cost'] ?? 0);
                $service = $option['service'] ?? 'Unknown';
                $etd = $option['etd'] ?? 'N/A';
                $courier = $option['code'] ?? 'JNE';
                
                $this->line("      " . ($index + 1) . ". {$courier} {$service}: Rp {$cost} ({$etd})");
                
                if ($debug && isset($option['description'])) {
                    $this->line("         Description: {$option['description']}");
                }
            }

            return true;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->line("   ❌ Connection timeout");
            $this->line("   🔍 Error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->line("   ❌ Exception occurred");
            $this->line("   🔍 Error: " . $e->getMessage());
            
            if ($debug) {
                $this->line("   📄 Stack trace: " . $e->getTraceAsString());
            }
            
            return false;
        }
    }
}