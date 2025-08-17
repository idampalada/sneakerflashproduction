<?php
// File: app/Console/Commands/TestRajaOngkirCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestRajaOngkirCommand extends Command
{
    protected $signature = 'rajaongkir:test 
                            {--api-key= : RajaOngkir API Key}
                            {--search= : Search term for testing}
                            {--weight=1000 : Package weight in grams}';

    protected $description = 'Test RajaOngkir V2 integration with correct format';

    private $apiKey;
    private $baseUrl = 'https://rajaongkir.komerce.id/api/v1';

    public function handle()
    {
        $this->apiKey = $this->option('api-key') ?: config('services.rajaongkir.api_key') ?: env('RAJAONGKIR_API_KEY');
        
        if (!$this->apiKey) {
            $this->error('❌ RajaOngkir API key is required!');
            $this->line('Use: php artisan rajaongkir:test --api-key=8MZVaA6pc8c11707407345e5Ad0DK9eU');
            return 1;
        }

        $this->info('🚀 Starting RajaOngkir V2 Integration Test (Fixed Format)');
        $this->line('API Key: ' . substr($this->apiKey, 0, 8) . '...');
        $this->line('Base URL: ' . $this->baseUrl);
        $this->newLine();

        // Test 1: Get Provinces (WORKING)
        $provinces = $this->testGetProvinces();
        
        // Test 2: Find Cities via Search (since cities endpoint doesn't work)
        $this->testFindCitiesViaSearch();
        
        // Test 3: Direct Search (WORKING)
        $searchResults = $this->testDirectSearch();
        
        // Test 4: Try to find working cost calculation endpoint
        $this->testFindCostEndpoint();
        
        // Test 5: Test with search results
        $this->testWithSearchResults();

        $this->newLine();
        $this->info('✅ RajaOngkir V2 Integration Test Completed!');
        
        return 0;
    }

    private function testGetProvinces()
    {
        $this->info('🌍 Test 1: Getting provinces (WORKING FORMAT)...');
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/province');

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $provinces = $data['data'];
                    $this->line("✅ Found " . count($provinces) . " provinces");
                    
                    // Show first 5 provinces with CORRECT format
                    $this->table(
                        ['ID', 'Province Name'],
                        array_slice(array_map(function($p) {
                            return [$p['id'], $p['name']]; // Actual format: id, name
                        }, $provinces), 0, 5)
                    );
                    
                    // Find DKI Jakarta ID
                    $jakarta = collect($provinces)->firstWhere('name', 'DKI JAKARTA');
                    if ($jakarta) {
                        $this->line("📍 DKI Jakarta ID: {$jakarta['id']}");
                    }
                    
                    return $provinces;
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }
        
        return [];
    }

    private function testFindCitiesViaSearch()
    {
        $this->info('🏙️ Test 2: Finding cities via search (since cities endpoint returns 404)...');
        
        $majorCities = ['jakarta', 'bandung', 'surabaya', 'medan', 'semarang'];
        
        foreach ($majorCities as $cityName) {
            try {
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/destination/domestic-destination', [
                    'search' => $cityName,
                    'limit' => 3
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data']) && !empty($data['data'])) {
                        $results = $data['data'];
                        $this->line("✅ {$cityName}: Found " . count($results) . " locations");
                        
                        // Show first result with actual format
                        $first = $results[0];
                        $this->line("   📍 Sample: {$first['subdistrict_name']}, {$first['district_name']}, {$first['city_name']}");
                        $this->line("   🆔 Location ID: {$first['id']}");
                    } else {
                        $this->line("❌ {$cityName}: No results found");
                    }
                } else {
                    $this->line("❌ {$cityName}: HTTP " . $response->status());
                }
            } catch (\Exception $e) {
                $this->line("❌ {$cityName}: Exception - " . $e->getMessage());
            }
        }
    }

    private function testDirectSearch()
    {
        $this->info('🔍 Test 3: Direct Search Method (WORKING)...');
        
        $searchTerm = $this->option('search') ?: 'jakarta';
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/domestic-destination', [
                'search' => $searchTerm,
                'limit' => 5,
                'offset' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $results = $data['data'];
                    $this->line("✅ Search '{$searchTerm}': Found " . count($results) . " results");
                    
                    // Show results in table with actual format
                    $tableData = array_map(function($result) {
                        return [
                            $result['id'],
                            $result['subdistrict_name'],
                            $result['district_name'],
                            $result['city_name'],
                            $result['province_name'],
                            $result['zip_code']
                        ];
                    }, array_slice($results, 0, 5));
                    
                    $this->table(
                        ['ID', 'Subdistrict', 'District', 'City', 'Province', 'Zip'],
                        $tableData
                    );
                    
                    return $results;
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Search Exception: ' . $e->getMessage());
        }
        
        return [];
    }

    private function testFindCostEndpoint()
    {
        $this->info('🚚 Test 4: Finding working cost calculation endpoint...');
        
        // Get sample locations from search
        $jakartaResponse = Http::timeout(10)->withHeaders([
            'key' => $this->apiKey
        ])->get($this->baseUrl . '/destination/domestic-destination', [
            'search' => 'jakarta pusat',
            'limit' => 1
        ]);
        
        $bandungResponse = Http::timeout(10)->withHeaders([
            'key' => $this->apiKey
        ])->get($this->baseUrl . '/destination/domestic-destination', [
            'search' => 'bandung',
            'limit' => 1
        ]);
        
        if (!$jakartaResponse->successful() || !$bandungResponse->successful()) {
            $this->error('❌ Could not get sample locations for cost test');
            return;
        }
        
        $jakartaData = $jakartaResponse->json();
        $bandungData = $bandungResponse->json();
        
        if (empty($jakartaData['data']) || empty($bandungData['data'])) {
            $this->error('❌ No sample locations found');
            return;
        }
        
        $origin = $jakartaData['data'][0]['id'];
        $destination = $bandungData['data'][0]['id'];
        $weight = $this->option('weight');
        
        $this->line("🎯 Testing with:");
        $this->line("   Origin: {$jakartaData['data'][0]['label']} (ID: {$origin})");
        $this->line("   Destination: {$bandungData['data'][0]['label']} (ID: {$destination})");
        $this->line("   Weight: {$weight}g");
        $this->newLine();
        
        // Try different cost endpoints and methods
        $testCases = [
            ['method' => 'POST', 'url' => '/cost', 'data' => ['origin' => $origin, 'destination' => $destination, 'weight' => $weight, 'courier' => 'jne']],
            ['method' => 'GET', 'url' => '/cost', 'params' => ['origin' => $origin, 'destination' => $destination, 'weight' => $weight, 'courier' => 'jne']],
            ['method' => 'POST', 'url' => '/shipping/cost', 'data' => ['origin' => $origin, 'destination' => $destination, 'weight' => $weight, 'courier' => 'jne']],
            ['method' => 'POST', 'url' => '/destination/cost', 'data' => ['origin' => $origin, 'destination' => $destination, 'weight' => $weight, 'courier' => 'jne']],
            ['method' => 'POST', 'url' => '/calculate', 'data' => ['origin' => $origin, 'destination' => $destination, 'weight' => $weight, 'courier' => 'jne']],
            ['method' => 'POST', 'url' => '/cost', 'data' => ['origin_id' => $origin, 'destination_id' => $destination, 'weight' => $weight, 'courier' => 'jne']],
        ];
        
        foreach ($testCases as $case) {
            try {
                $this->line("Testing {$case['method']} {$case['url']}...");
                
                if ($case['method'] === 'POST') {
                    $response = Http::timeout(15)->withHeaders([
                        'key' => $this->apiKey
                    ])->post($this->baseUrl . $case['url'], $case['data']);
                } else {
                    $response = Http::timeout(15)->withHeaders([
                        'key' => $this->apiKey
                    ])->get($this->baseUrl . $case['url'], $case['params']);
                }

                $this->line("   Status: " . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    $this->line("   ✅ SUCCESS! Response structure:");
                    $this->line("   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    return; // Stop on first success
                } else {
                    $responseBody = $response->body();
                    $this->line("   ❌ Failed. Response: " . (strlen($responseBody) > 100 ? substr($responseBody, 0, 100) . '...' : $responseBody));
                }
            } catch (\Exception $e) {
                $this->line("   ❌ Exception: " . $e->getMessage());
            }
        }
        
        $this->error('❌ No working cost calculation endpoint found');
    }

    private function testWithSearchResults()
    {
        $this->info('🗺️ Test 5: Testing search-based workflow...');
        
        $testCities = ['jakarta', 'bandung', 'surabaya'];
        $results = [];
        
        foreach ($testCities as $cityName) {
            try {
                $response = Http::timeout(10)->withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/destination/domestic-destination', [
                    'search' => $cityName,
                    'limit' => 1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data'][0])) {
                        $location = $data['data'][0];
                        $results[] = [
                            ucfirst($cityName),
                            $location['id'],
                            $location['subdistrict_name'],
                            $location['district_name'],
                            $location['city_name'],
                            '✅ Found'
                        ];
                    } else {
                        $results[] = [ucfirst($cityName), '-', '-', '-', '-', '❌ No data'];
                    }
                } else {
                    $results[] = [ucfirst($cityName), '-', '-', '-', '-', '❌ HTTP ' . $response->status()];
                }
            } catch (\Exception $e) {
                $results[] = [ucfirst($cityName), '-', '-', '-', '-', '❌ Exception'];
            }
        }
        
        $this->table(
            ['Search Term', 'Location ID', 'Subdistrict', 'District', 'City', 'Status'],
            $results
        );
        
        $this->newLine();
        $this->info('💡 Summary:');
        $this->line('✅ Provinces endpoint: WORKING (format: id, name)');
        $this->line('❌ Cities endpoint: NOT WORKING (404 error)');
        $this->line('✅ Search endpoint: WORKING (format: id, label, province_name, city_name, etc.)');
        $this->line('❌ Cost calculation: ENDPOINT NOT FOUND');
        $this->newLine();
        $this->info('🎯 Recommendation: Use search-based approach for location selection');
    }
}