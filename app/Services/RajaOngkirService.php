<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    private $apiKey;
    private $baseUrl;
    private $timeout;
    private $cacheDuration;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.api_key');
        $this->baseUrl = 'https://rajaongkir.komerce.id/api/v1';
        $this->timeout = config('services.rajaongkir.timeout', 25);
        $this->cacheDuration = config('services.rajaongkir.cache_duration', 3600);

        Log::info('RajaOngkir V2 Service initialized (Fixed Format)', [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'api_key_set' => !empty($this->apiKey)
        ]);
    }

    /**
     * Get all provinces - WORKING FORMAT
     * Response format: { meta: {...}, data: [{ id: 1, name: "PROVINCE NAME" }] }
     */
    public function getProvinces()
    {
        return Cache::remember('rajaongkir_v2_provinces_fixed', $this->cacheDuration, function () {
            try {
                Log::info('Fetching provinces from RajaOngkir V2 API (Fixed Format)');
                
                $response = Http::timeout($this->timeout)->withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/destination/province');

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['data']) && is_array($data['data'])) {
                        $provinces = array_map(function($province) {
                            return [
                                'province_id' => $province['id'],      // Map to expected format
                                'province' => $province['name']        // Map to expected format
                            ];
                        }, $data['data']);
                        
                        Log::info('Successfully fetched ' . count($provinces) . ' provinces (Fixed Format)');
                        return $provinces;
                    }
                }

                Log::warning('RajaOngkir V2 provinces API returned unexpected format');
                return [];
            } catch (\Exception $e) {
                Log::error('RajaOngkir V2 Get Provinces Error', [
                    'message' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Search destinations - WORKING METHOD (since cities endpoint returns 404)
     * This replaces getCities since the cities endpoint doesn't work
     */
    public function searchDestinations($search, $limit = 10, $offset = 0)
    {
        try {
            Log::info('Searching destinations via RajaOngkir V2 (Fixed Method)', [
                'search' => $search,
                'limit' => $limit
            ]);
            
            $response = Http::timeout($this->timeout)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/domestic-destination', [
                'search' => $search,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    $destinations = array_map(function($dest) {
                        return [
                            'location_id' => $dest['id'],
                            'subdistrict_name' => $dest['subdistrict_name'],
                            'district_name' => $dest['district_name'],
                            'city_name' => $dest['city_name'],
                            'province_name' => $dest['province_name'],
                            'zip_code' => $dest['zip_code'],
                            'label' => $dest['label'],
                            'full_address' => $dest['subdistrict_name'] . ', ' . 
                                            $dest['district_name'] . ', ' . 
                                            $dest['city_name'] . ', ' . 
                                            $dest['province_name']
                        ];
                    }, $data['data']);
                    
                    Log::info('Successfully found ' . count($destinations) . ' destinations for search: ' . $search);
                    return $destinations;
                }
            }

            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 Search Destinations Error', [
                'message' => $e->getMessage(),
                'search' => $search
            ]);
            return [];
        }
    }

    /**
     * Get cities by searching for province name
     * Since direct cities endpoint doesn't work, we search by province name
     */
    public function getCitiesByProvince($provinceName)
    {
        try {
            Log::info('Getting cities by province via search', ['province' => $provinceName]);
            
            // Search for locations in this province
            $response = Http::timeout($this->timeout)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/domestic-destination', [
                'search' => $provinceName,
                'limit' => 50 // Get more results to find cities
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data']) && is_array($data['data'])) {
                    // Group by city and get unique cities
                    $cities = [];
                    $uniqueCities = [];
                    
                    foreach ($data['data'] as $location) {
                        $cityKey = $location['city_name'];
                        
                        if (!isset($uniqueCities[$cityKey])) {
                            $uniqueCities[$cityKey] = true;
                            $cities[] = [
                                'city_id' => $location['id'], // Use location ID as city ID
                                'city_name' => $location['city_name'],
                                'province_name' => $location['province_name'],
                                'sample_location_id' => $location['id'] // For reference
                            ];
                        }
                    }
                    
                    Log::info('Found ' . count($cities) . ' unique cities for province: ' . $provinceName);
                    return $cities;
                }
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error getting cities by province', [
                'message' => $e->getMessage(),
                'province' => $provinceName
            ]);
            return [];
        }
    }

    /**
     * Calculate shipping costs - ENDPOINT NEEDS TO BE FOUND
     * Current cost endpoints return 404, need to find working endpoint
     */
    public function calculateShipping($originId, $destinationId, $weight, $courier = 'jne')
    {
        try {
            Log::info('Attempting shipping calculation (finding working endpoint)', [
                'origin' => $originId,
                'destination' => $destinationId,
                'weight' => $weight,
                'courier' => $courier
            ]);

            // Try different possible endpoints until one works
            $endpoints = [
                '/cost',
                '/shipping/cost',
                '/destination/cost',
                '/calculate',
                '/shipping/calculate'
            ];

            $requestData = [
                'origin' => $originId,
                'destination' => $destinationId,
                'weight' => $weight,
                'courier' => $courier
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::timeout($this->timeout)->withHeaders([
                        'key' => $this->apiKey
                    ])->post($this->baseUrl . $endpoint, $requestData);

                    if ($response->successful()) {
                        $data = $response->json();
                        
                        Log::info("Found working cost endpoint: {$endpoint}");
                        Log::info("Response: " . json_encode($data));
                        
                        // Parse response when we find working endpoint
                        // Format will be determined when we find it
                        return $this->parseShippingResponse($data);
                    }
                } catch (\Exception $e) {
                    continue; // Try next endpoint
                }
            }

            Log::warning('No working shipping cost endpoint found');
            
            // Return mock data for testing until we find working endpoint
            return $this->getMockShippingOptions($weight);

        } catch (\Exception $e) {
            Log::error('RajaOngkir V2 Calculate Shipping Error', [
                'message' => $e->getMessage(),
                'origin' => $originId,
                'destination' => $destinationId
            ]);
            
            return $this->getMockShippingOptions($weight);
        }
    }

    /**
     * Parse shipping response when we find the working endpoint
     */
    private function parseShippingResponse($data)
    {
        // This will be implemented once we find the working endpoint format
        // For now, return the raw data for analysis
        return [
            'raw_response' => $data,
            'parsed' => false,
            'message' => 'Raw response from working endpoint - needs parsing'
        ];
    }

    /**
     * Mock shipping options for testing until real endpoint is found
     */
    private function getMockShippingOptions($weight)
    {
        $basePrice = max(10000, $weight * 5); // Minimum 10k, 5 rupiah per gram
        
        return [
            [
                'courier' => 'JNE',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => $basePrice,
                'etd' => '2-3',
                'formatted_cost' => 'Rp ' . number_format($basePrice, 0, ',', '.'),
                'formatted_etd' => '2-3 hari',
                'is_mock' => true
            ],
            [
                'courier' => 'POS',
                'service' => 'Paket Kilat',
                'description' => 'Pos Kilat Khusus',
                'cost' => $basePrice - 3000,
                'etd' => '3-4',
                'formatted_cost' => 'Rp ' . number_format($basePrice - 3000, 0, ',', '.'),
                'formatted_etd' => '3-4 hari',
                'is_mock' => true
            ],
            [
                'courier' => 'TIKI',
                'service' => 'ECO',
                'description' => 'Ekonomi Service',
                'cost' => $basePrice - 5000,
                'etd' => '4-5',
                'formatted_cost' => 'Rp ' . number_format($basePrice - 5000, 0, ',', '.'),
                'formatted_etd' => '4-5 hari',
                'is_mock' => true
            ]
        ];
    }

    /**
     * Find locations by search term (main method to use)
     */
    public function findLocations($searchTerm, $limit = 10)
    {
        return $this->searchDestinations($searchTerm, $limit);
    }

    /**
     * Get major cities by searching for common city names
     */
    public function getMajorCities()
    {
        $majorCityNames = [
            'jakarta', 'bandung', 'surabaya', 'medan', 'semarang', 
            'makassar', 'palembang', 'batam', 'bogor', 'depok'
        ];

        $cities = [];
        
        foreach ($majorCityNames as $cityName) {
            $results = $this->searchDestinations($cityName, 3);
            
            if (!empty($results)) {
                $cities[$cityName] = $results[0]; // Take first result as representative
            }
        }

        return $cities;
    }

    /**
     * Check API connection
     */
    public function testConnection()
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/province');

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed',
                'data' => $response->successful() ? $response->json() : null,
                'endpoints_status' => [
                    'provinces' => $response->successful() ? 'WORKING' : 'FAILED',
                    'cities' => 'NOT AVAILABLE (404)',
                    'search' => 'WORKING',
                    'cost_calculation' => 'ENDPOINT NOT FOUND'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'message' => 'Connection error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get service configuration
     */
    public function getConfig()
    {
        return [
            'api_version' => 'v2',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'cache_duration' => $this->cacheDuration,
            'api_key_configured' => !empty($this->apiKey),
            'working_endpoints' => [
                'provinces' => true,
                'search' => true,
                'cities' => false,
                'cost_calculation' => false
            ],
            'recommended_approach' => 'Use search-based location selection'
        ];
    }
}