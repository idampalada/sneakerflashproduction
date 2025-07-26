<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    private $apiKey;
    private $baseUrl;
    private $accountType; // starter, basic, pro

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.api_key');
        $this->accountType = config('services.rajaongkir.account_type', 'starter');
        
        // Set base URL based on account type
        switch ($this->accountType) {
            case 'pro':
                $this->baseUrl = 'https://pro.rajaongkir.com/api';
                break;
            case 'basic':
                $this->baseUrl = 'https://api.rajaongkir.com/basic';
                break;
            default:
                $this->baseUrl = 'https://api.rajaongkir.com/starter';
        }
    }

    public function getProvinces()
    {
        return Cache::remember('rajaongkir_provinces', 3600, function () {
            try {
                $response = Http::withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/province');

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rajaongkir']['results'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('RajaOngkir Get Provinces Error', [
                    'message' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    public function getCities($provinceId = null)
    {
        $cacheKey = $provinceId ? "rajaongkir_cities_{$provinceId}" : 'rajaongkir_cities_all';
        
        return Cache::remember($cacheKey, 3600, function () use ($provinceId) {
            try {
                $url = $this->baseUrl . '/city';
                $params = [];
                
                if ($provinceId) {
                    $params['province'] = $provinceId;
                }

                $response = Http::withHeaders([
                    'key' => $this->apiKey
                ])->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rajaongkir']['results'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('RajaOngkir Get Cities Error', [
                    'province_id' => $provinceId,
                    'message' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    public function getSubdistricts($cityId)
    {
        // Only available for Pro account
        if ($this->accountType !== 'pro') {
            return [];
        }

        return Cache::remember("rajaongkir_subdistricts_{$cityId}", 3600, function () use ($cityId) {
            try {
                $response = Http::withHeaders([
                    'key' => $this->apiKey
                ])->get($this->baseUrl . '/subdistrict', [
                    'city' => $cityId
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rajaongkir']['results'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('RajaOngkir Get Subdistricts Error', [
                    'city_id' => $cityId,
                    'message' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    public function calculateShipping($origin, $destination, $weight, $courier = 'jne')
    {
        try {
            $params = [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight, // in grams
                'courier' => $courier
            ];

            // For Pro account, you can use originType and destinationType
            if ($this->accountType === 'pro') {
                $params['originType'] = 'city'; // or 'subdistrict'
                $params['destinationType'] = 'city'; // or 'subdistrict'
            }

            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'content-type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post($this->baseUrl . '/cost', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rajaongkir']['results'][0]['costs'])) {
                    return $this->formatShippingResults($data['rajaongkir']['results'][0]['costs'], $courier);
                }
            }

            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir Calculate Shipping Error', [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier,
                'message' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    public function getAllShippingOptions($origin, $destination, $weight)
    {
        $couriers = ['jne', 'pos', 'tiki'];
        $allOptions = [];

        foreach ($couriers as $courier) {
            $results = $this->calculateShipping($origin, $destination, $weight, $courier);
            $allOptions = array_merge($allOptions, $results);
        }

        // Sort by cost
        usort($allOptions, function ($a, $b) {
            return $a['cost'] - $b['cost'];
        });

        return $allOptions;
    }

    private function formatShippingResults($costs, $courier)
    {
        $results = [];

        foreach ($costs as $cost) {
            $results[] = [
                'courier' => strtoupper($courier),
                'service' => $cost['service'],
                'description' => $cost['description'],
                'cost' => $cost['cost'][0]['value'],
                'etd' => $cost['cost'][0]['etd'],
                'formatted_cost' => 'Rp ' . number_format($cost['cost'][0]['value'], 0, ',', '.'),
                'formatted_etd' => $this->formatEtd($cost['cost'][0]['etd'])
            ];
        }

        return $results;
    }

    private function formatEtd($etd)
    {
        // Convert "1-2" to "1-2 hari"
        if (is_numeric($etd)) {
            return $etd . ' hari';
        }

        if (strpos($etd, '-') !== false) {
            return $etd . ' hari';
        }

        return $etd;
    }

    // Helper method to get city by name (useful for seeding)
    public function findCityByName($cityName, $provinceName = null)
    {
        $cities = $this->getCities();
        
        foreach ($cities as $city) {
            if (stripos($city['city_name'], $cityName) !== false) {
                if ($provinceName) {
                    if (stripos($city['province'], $provinceName) !== false) {
                        return $city;
                    }
                } else {
                    return $city;
                }
            }
        }

        return null;
    }
}