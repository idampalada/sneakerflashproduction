<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RajaOngkirService;

class TestHierarchicalRajaOngkir extends Command
{
    protected $signature = 'test:rajaongkir-hierarchical 
                            {--province= : Test specific province ID}
                            {--city= : Test specific city ID}
                            {--district= : Test specific district ID}
                            {--detailed : Show detailed results}';

    protected $description = 'Test RajaOngkir hierarchical endpoints (Province → City → District → Sub-District)';

    private $rajaOngkirService;

    public function __construct(RajaOngkirService $rajaOngkirService)
    {
        parent::__construct();
        $this->rajaOngkirService = $rajaOngkirService;
    }

    public function handle()
    {
        $this->info('🚀 Testing RajaOngkir Hierarchical Address System');
        $this->info('📅 Test Date: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // Configuration check
        $this->checkConfiguration();
        $this->newLine();

        // Run comprehensive test
        if ($this->option('province') || $this->option('city') || $this->option('district')) {
            $this->runSpecificTest();
        } else {
            $this->runFullTest();
        }

        $this->newLine();
        $this->info('✅ Test completed!');
        
        return 0;
    }

    private function checkConfiguration()
    {
        $this->info('🔧 Configuration Check:');
        
        $apiKey = config('services.rajaongkir.api_key');
        $baseUrl = config('services.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
        
        $this->line('   API Key: ' . ($apiKey ? '✅ Set (' . substr($apiKey, 0, 8) . '...)' : '❌ Not set'));
        $this->line('   Base URL: ' . $baseUrl);
        $this->line('   Service: ' . (class_exists('App\Services\RajaOngkirService') ? '✅ Available' : '❌ Not found'));
    }

    private function runSpecificTest()
    {
        $this->info('🎯 Running Specific Test');

        if ($provinceId = $this->option('province')) {
            $this->testSpecificCities($provinceId);
        }

        if ($cityId = $this->option('city')) {
            $this->testSpecificDistricts($cityId);
        }

        if ($districtId = $this->option('district')) {
            $this->testSpecificSubDistricts($districtId);
        }
    }

    private function runFullTest()
    {
        $this->info('🏗️ Running Full Hierarchical Test');
        
        // Step 1: Test Provinces
        $this->line('Step 1: Testing Provinces...');
        $provinces = $this->testHierarchicalProvinces();
        
        if (empty($provinces)) {
            $this->error('❌ Cannot continue - no provinces loaded');
            return;
        }

        // Step 2: Test Cities (using first province)
        $this->line('Step 2: Testing Cities...');
        $testProvince = $provinces[0];
        $cities = $this->testHierarchicalCities($testProvince['id']);
        
        if (empty($cities)) {
            $this->error('❌ Cannot continue - no cities loaded');
            return;
        }

        // Step 3: Test Districts (using first city)
        $this->line('Step 3: Testing Districts...');
        $testCity = $cities[0];
        $districts = $this->testHierarchicalDistricts($testCity['id']);
        
        if (empty($districts)) {
            $this->error('❌ Cannot continue - no districts loaded');
            return;
        }

        // Step 4: Test Sub-Districts (using first district)
        $this->line('Step 4: Testing Sub-Districts...');
        $testDistrict = $districts[0];
        $subDistricts = $this->testHierarchicalSubDistricts($testDistrict['id']);

        // Summary
        $this->newLine();
        $this->info('📊 Test Summary:');
        $this->line("   ✅ Provinces: " . count($provinces) . " loaded");
        $this->line("   ✅ Cities (Province '{$testProvince['name']}'): " . count($cities) . " loaded");
        $this->line("   ✅ Districts (City '{$testCity['name']}'): " . count($districts) . " loaded");
        $this->line("   ✅ Sub-Districts (District '{$testDistrict['name']}'): " . count($subDistricts) . " loaded");
        
        if ($this->option('detailed')) {
            $this->showDetailedHierarchy($provinces, $cities, $districts, $subDistricts);
        }
    }

    private function testHierarchicalProvinces()
    {
        try {
            $startTime = microtime(true);
            $provinces = $this->rajaOngkirService->getProvincesHierarchical();
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000);
            
            if (!empty($provinces)) {
                $this->line("   ✅ Loaded " . count($provinces) . " provinces ({$duration}ms)");
                
                if ($this->option('detailed')) {
                    $samples = array_slice($provinces, 0, 3);
                    foreach ($samples as $province) {
                        $this->line("      - {$province['name']} (ID: {$province['id']})");
                    }
                    if (count($provinces) > 3) {
                        $this->line("      ... and " . (count($provinces) - 3) . " more");
                    }
                }
                
                return $provinces;
            } else {
                $this->line("   ❌ No provinces loaded");
                return [];
            }
            
        } catch (\Exception $e) {
            $this->line("   ❌ Error: " . $e->getMessage());
            return [];
        }
    }

    private function testHierarchicalCities($provinceId)
    {
        try {
            $startTime = microtime(true);
            $cities = $this->rajaOngkirService->getCitiesByProvinceId($provinceId);
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000);
            
            if (!empty($cities)) {
                $this->line("   ✅ Loaded " . count($cities) . " cities for province {$provinceId} ({$duration}ms)");
                
                if ($this->option('detailed')) {
                    $samples = array_slice($cities, 0, 3);
                    foreach ($samples as $city) {
                        $zipInfo = $city['zip_code'] && $city['zip_code'] !== '0' ? " - {$city['zip_code']}" : '';
                        $this->line("      - {$city['name']} (ID: {$city['id']}{$zipInfo})");
                    }
                    if (count($cities) > 3) {
                        $this->line("      ... and " . (count($cities) - 3) . " more");
                    }
                }
                
                return $cities;
            } else {
                $this->line("   ❌ No cities loaded for province {$provinceId}");
                return [];
            }
            
        } catch (\Exception $e) {
            $this->line("   ❌ Error loading cities: " . $e->getMessage());
            return [];
        }
    }

    private function testHierarchicalDistricts($cityId)
    {
        try {
            $startTime = microtime(true);
            $districts = $this->rajaOngkirService->getDistrictsByCityId($cityId);
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000);
            
            if (!empty($districts)) {
                $this->line("   ✅ Loaded " . count($districts) . " districts for city {$cityId} ({$duration}ms)");
                
                if ($this->option('detailed')) {
                    $samples = array_slice($districts, 0, 3);
                    foreach ($samples as $district) {
                        $zipInfo = $district['zip_code'] && $district['zip_code'] !== '0' ? " - {$district['zip_code']}" : '';
                        $this->line("      - {$district['name']} (ID: {$district['id']}{$zipInfo})");
                    }
                    if (count($districts) > 3) {
                        $this->line("      ... and " . (count($districts) - 3) . " more");
                    }
                }
                
                return $districts;
            } else {
                $this->line("   ❌ No districts loaded for city {$cityId}");
                return [];
            }
            
        } catch (\Exception $e) {
            $this->line("   ❌ Error loading districts: " . $e->getMessage());
            return [];
        }
    }

    private function testHierarchicalSubDistricts($districtId)
    {
        try {
            $startTime = microtime(true);
            $subDistricts = $this->rajaOngkirService->getSubDistrictsByDistrictId($districtId);
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000);
            
            if (!empty($subDistricts)) {
                $this->line("   ✅ Loaded " . count($subDistricts) . " sub-districts for district {$districtId} ({$duration}ms)");
                
                if ($this->option('detailed')) {
                    $samples = array_slice($subDistricts, 0, 3);
                    foreach ($samples as $subDistrict) {
                        $zipInfo = $subDistrict['zip_code'] && $subDistrict['zip_code'] !== '0' ? " - {$subDistrict['zip_code']}" : '';
                        $this->line("      - {$subDistrict['name']} (ID: {$subDistrict['id']}{$zipInfo})");
                    }
                    if (count($subDistricts) > 3) {
                        $this->line("      ... and " . (count($subDistricts) - 3) . " more");
                    }
                }
                
                return $subDistricts;
            } else {
                $this->line("   ❌ No sub-districts loaded for district {$districtId}");
                return [];
            }
            
        } catch (\Exception $e) {
            $this->line("   ❌ Error loading sub-districts: " . $e->getMessage());
            return [];
        }
    }

    private function testSpecificCities($provinceId)
    {
        $this->line("🏙️ Testing cities for province {$provinceId}...");
        $this->testHierarchicalCities($provinceId);
    }

    private function testSpecificDistricts($cityId)
    {
        $this->line("🏘️ Testing districts for city {$cityId}...");
        $this->testHierarchicalDistricts($cityId);
    }

    private function testSpecificSubDistricts($districtId)
    {
        $this->line("🏠 Testing sub-districts for district {$districtId}...");
        $this->testHierarchicalSubDistricts($districtId);
    }

    private function showDetailedHierarchy($provinces, $cities, $districts, $subDistricts)
    {
        $this->newLine();
        $this->info('📋 Detailed Results:');
        
        // Show complete hierarchy path
        if (!empty($provinces) && !empty($cities) && !empty($districts) && !empty($subDistricts)) {
            $this->line('🗺️ Complete Hierarchy Path:');
            $this->line("   Province: {$provinces[0]['name']} (ID: {$provinces[0]['id']})");
            $this->line("   └── City: {$cities[0]['name']} (ID: {$cities[0]['id']})");
            $this->line("       └── District: {$districts[0]['name']} (ID: {$districts[0]['id']})");
            $this->line("           └── Sub-District: {$subDistricts[0]['name']} (ID: {$subDistricts[0]['id']})");
            
            if (!empty($subDistricts[0]['zip_code']) && $subDistricts[0]['zip_code'] !== '0') {
                $this->line("               📮 Postal Code: {$subDistricts[0]['zip_code']}");
            }
        }

        // Show method names for reference
        $this->newLine();
        $this->info('🔧 Method Names:');
        $this->line('   - getProvincesHierarchical()');
        $this->line('   - getCitiesByProvinceId($provinceId)');
        $this->line('   - getDistrictsByCityId($cityId)');
        $this->line('   - getSubDistrictsByDistrictId($districtId)');
    }
}