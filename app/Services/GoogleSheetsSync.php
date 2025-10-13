<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class GoogleSheetsSync
{
    private $spreadsheetId;
    private $errors = [];
    private $successCount = 0;
    private $updateCount = 0;
    private $createCount = 0;
    private $deleteCount = 0;
    private $skipCount = 0;
    private $processedSkus = [];

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID', '1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg');
    }

    public function syncProducts(array $options = []): array
    {
        $syncLog = null;
        
        try {
            $startTime = now();
            
            $syncLog = \App\Models\GoogleSheetsSyncLog::createForSync(
                $this->spreadsheetId,
                \Illuminate\Support\Facades\Auth::id(),
                array_merge($options, ['sync_strategy' => 'smart_individual_sku'])
            );
            
            $syncLog->update([
                'status' => 'running',
                'started_at' => $startTime
            ]);

            $existingSkus = Product::pluck('sku')->toArray();
            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                throw new Exception('No data retrieved from Google Sheets');
            }

            $skuParents = array_unique(array_filter(array_column($data, 'sku_parent')));
            $spreadsheetSkus = array_unique(array_filter(array_column($data, 'sku')));

            DB::transaction(function () use ($data, $existingSkus, $spreadsheetSkus) {
                $this->processEachRowAsProduct($data);
                $this->deleteProductsNotInSpreadsheet($existingSkus, $spreadsheetSkus);
            });

            $endTime = now();
            $duration = max(0, (int) round(abs($endTime->diffInSeconds($startTime))));

            $syncLog->update([
                'status' => 'completed',
                'completed_at' => $endTime,
                'duration_seconds' => $duration,
                'sync_results' => [
                    'sync_strategy' => 'smart_individual_sku',
                    'sku_parents_processed' => count($skuParents),
                    'individual_products_created' => $this->createCount + $this->updateCount,
                    'old_products_deleted' => $this->deleteCount,
                    'final_product_count' => Product::count()
                ],
                'summary' => $this->generateSummaryForLog()
            ]);

            return [
                'success' => true,
                'message' => 'Smart sync completed successfully',
                'sync_id' => $syncLog->sync_id,
                'stats' => [
                    'created' => $this->createCount,
                    'updated' => $this->updateCount,
                    'deleted' => $this->deleteCount,
                    'skipped' => $this->skipCount,
                    'errors' => count($this->errors),
                    'duration' => $duration
                ],
                'errors' => $this->errors
            ];

        } catch (Exception $e) {
            if ($syncLog) {
                $endTime = now();
                $duration = max(0, (int) round(abs($endTime->diffInSeconds($syncLog->started_at ?? now()))));

                $syncLog->update([
                    'status' => 'failed',
                    'completed_at' => $endTime,
                    'duration_seconds' => $duration,
                    'error_message' => $e->getMessage(),
                    'error_details' => $this->errors
                ]);
            }

            return [
                'success' => false,
                'message' => 'Smart sync failed: ' . $e->getMessage(),
                'sync_id' => $syncLog->sync_id ?? null,
                'errors' => $this->errors
            ];
        }
    }

    public function syncProductsSafeMode(array $options = []): array
    {
        $syncLog = null;
        
        try {
            $startTime = now();
            
            $syncLog = \App\Models\GoogleSheetsSyncLog::createForSync(
                $this->spreadsheetId,
                \Illuminate\Support\Facades\Auth::id(),
                array_merge($options, ['sync_strategy' => 'safe_mode_no_delete'])
            );
            
            $syncLog->update([
                'status' => 'running',
                'started_at' => $startTime
            ]);

            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                throw new Exception('No data retrieved from Google Sheets');
            }

            DB::transaction(function () use ($data) {
                $this->processEachRowAsProduct($data);
            });

            $endTime = now();
            $duration = max(0, (int) round(abs($endTime->diffInSeconds($startTime))));

            $syncLog->update([
                'status' => 'completed',
                'completed_at' => $endTime,
                'duration_seconds' => $duration,
                'sync_results' => [
                    'sync_strategy' => 'safe_mode_no_delete',
                    'products_created' => $this->createCount,
                    'products_updated' => $this->updateCount,
                    'products_deleted' => 0,
                    'final_product_count' => Product::count()
                ],
                'summary' => "Safe mode: Created {$this->createCount}, Updated {$this->updateCount}, No deletions"
            ]);

            return [
                'success' => true,
                'message' => 'Safe mode sync completed',
                'sync_id' => $syncLog->sync_id,
                'stats' => [
                    'created' => $this->createCount,
                    'updated' => $this->updateCount,
                    'deleted' => 0,
                    'skipped' => $this->skipCount,
                    'errors' => count($this->errors),
                    'duration' => $duration
                ],
                'errors' => $this->errors
            ];

        } catch (Exception $e) {
            if ($syncLog) {
                $endTime = now();
                $duration = max(0, (int) round(abs($endTime->diffInSeconds($syncLog->started_at ?? now()))));

                $syncLog->update([
                    'status' => 'failed',
                    'completed_at' => $endTime,
                    'duration_seconds' => $duration,
                    'error_message' => $e->getMessage(),
                    'error_details' => $this->errors
                ]);
            }

            return [
                'success' => false,
                'message' => 'Safe mode sync failed: ' . $e->getMessage(),
                'sync_id' => $syncLog->sync_id ?? null,
                'errors' => $this->errors
            ];
        }
    }

    private function generateSummaryForLog(): string
    {
        $summary = [];
        
        if ($this->createCount > 0) {
            $summary[] = "Created {$this->createCount} products";
        }
        
        if ($this->updateCount > 0) {
            $summary[] = "Updated {$this->updateCount} products";
        }
        
        if ($this->deleteCount > 0) {
            $summary[] = "Deleted {$this->deleteCount} old products";
        }
        
        if ($this->skipCount > 0) {
            $summary[] = "Skipped {$this->skipCount} rows";
        }
        
        if (count($this->errors) > 0) {
            $summary[] = "Encountered " . count($this->errors) . " errors";
        }

        return implode(', ', $summary) ?: 'No changes made';
    }

    private function deleteProductsNotInSpreadsheet(array $existingSkus, array $spreadsheetSkus): void
    {
        $skusToDelete = array_diff($existingSkus, $spreadsheetSkus);
        
        if (empty($skusToDelete)) {
            return;
        }

        foreach ($skusToDelete as $sku) {
            try {
                $product = Product::where('sku', $sku)->first();
                
                if ($product) {
                    $product->delete();
                    $this->deleteCount++;
                }
            } catch (Exception $e) {
                $this->errors[] = [
                    'sku' => $sku,
                    'operation' => 'delete',
                    'error' => 'Failed to delete product: ' . $e->getMessage()
                ];
            }
        }
    }

    private function fetchGoogleSheetsData(): array
    {
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv";
        
        $response = Http::timeout(30)->retry(3, 2000)->get($csvUrl);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch data from Google Sheets: ' . $response->status());
        }

        $csvContent = $response->body();
        
        $temp = tmpfile();
        fwrite($temp, $csvContent);
        rewind($temp);
        
        $data = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($temp, 0, ',', '"')) !== false) {
            $lineNumber++;
            
            if ($lineNumber === 1) {
                $headers = array_map('trim', $row);
                continue;
            }

            if (empty($row) || count($row) < 5) {
                continue;
            }

            $row = array_pad($row, count($headers), '');
            $rowData = array_combine($headers, $row);
            
            if ($rowData === false) {
                continue;
            }
            
            $rowData = array_map('trim', $rowData);
            
            $hasData = false;
            foreach ($rowData as $value) {
                if (!empty($value)) {
                    $hasData = true;
                    break;
                }
            }
            
            if ($hasData) {
                $data[] = $rowData;
            }
        }

        fclose($temp);

        if (empty($data)) {
            throw new Exception('No valid data rows found in CSV');
        }

        return $data;
    }

    private function processEachRowAsProduct(array $data): void
    {
        foreach ($data as $index => $row) {
            try {
                $rowNumber = $index + 2;

                $name = trim($row['name'] ?? '');
                $sku = trim($row['sku'] ?? '');

                if (empty($name) && empty($sku)) {
                    $this->skipCount++;
                    continue;
                }

                if (empty($sku)) {
                    $sku = 'SKU-' . uniqid();
                    $row['sku'] = $sku;
                }

                $this->processedSkus[] = $sku;
                $this->createOrUpdateIndividualProduct($row, $rowNumber);

            } catch (Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => [
                        'name' => substr($row['name'] ?? '', 0, 50),
                        'sku' => $row['sku'] ?? 'missing'
                    ]
                ];
                continue;
            }
        }
    }

    private function createOrUpdateIndividualProduct(array $row, int $rowNumber): void
    {
        try {
            $sku = trim($row['sku']);
            $size = trim($row['available_sizes'] ?? '');
            
            $productData = $this->extractProductData($row);
            
            $productData['name'] = $this->generateSizeSpecificName($productData['name'], $size);
            $productData['sku'] = $sku;
            $productData['available_sizes'] = !empty($size) ? [$size] : [];
            $productData['stock_quantity'] = (int) ($row['stock_quantity'] ?? 0);

            if (empty($productData['sku_parent'])) {
                $productData['sku_parent'] = trim($row['sku_parent'] ?? '');
            }

            $existingProduct = Product::where('sku', $sku)->first();
            
            $category = $this->findOrCreateCategory($productData['product_type']);
            $productData['category_id'] = $category->id;

            if ($existingProduct) {
                $existingProduct->update($productData);
                $this->updateCount++;
            } else {
                $productData['slug'] = $this->generateUniqueSlug($productData['name']);
                Product::create($productData);
                $this->createCount++;
            }

            $this->successCount++;
            
        } catch (Exception $e) {
            $this->errors[] = [
                'row' => $rowNumber,
                'sku' => $row['sku'] ?? 'unknown',
                'operation' => 'create_or_update',
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function generateSizeSpecificName(string $baseName, string $size): string
    {
        if (empty($size)) {
            return $baseName;
        }
        
        if (str_contains(strtolower($baseName), strtolower($size))) {
            return $baseName;
        }
        
        return $baseName . ' - Size ' . $size;
    }

    private function extractProductData(array $row): array
    {
        $name = trim($row['name'] ?? '') ?: 'Product ' . ($row['sku'] ?? uniqid());
        $brand = trim($row['brand'] ?? '') ?: 'Unknown';
        $skuParent = trim($row['sku_parent'] ?? '') ?: 'PARENT-' . uniqid();
        $sku = trim($row['sku'] ?? '') ?: 'SKU-' . uniqid();
        
        $priceRaw = trim($row['price'] ?? '');
        $price = $this->parseFloat($priceRaw);
        if ($price <= 0) {
            $price = 1000000;
        }

        $productTypeString = trim($row['product_type'] ?? '');
        $parsedData = $this->parseProductTypeColumn($productTypeString);
        
        $genderTarget = $parsedData['gender_target'];
        $productType = $parsedData['product_type'];
        
        if (empty($genderTarget)) {
            $genderTarget = $this->detectGenderFromProductName($name);
        }

        $images = $this->extractImages($row);
        $shortDescription = $this->generateShortDescription($name);
        
        // Parse is_featured from Excel
        $isFeatured = $this->parseFeaturedFlag($row['is_featured'] ?? '');
        
        return [
            'name' => $name,
            'description' => trim($row['description'] ?? ''),
            'short_description' => $shortDescription,
            'brand' => $brand,
            'sku_parent' => $skuParent,
            'sku' => $sku,
            'price' => $price,
            'sale_price' => $this->parseFloat($row['sale_price'] ?? ''),
            'weight' => $this->parseFloat($row['weight'] ?? '') ?: 500,
            'length' => $this->extractDimension($row, ['length', 'lengh']),
            'width' => $this->extractDimension($row, ['width', 'wide']),
            'height' => $this->extractDimension($row, ['height', 'high']),
            'images' => $images,
            'gender_target' => $genderTarget,
            'product_type' => $productType,
            'available_sizes' => !empty($row['available_sizes']) ? [$row['available_sizes']] : [],
            'is_featured_sale' => $this->parseSaleShow($row['sale_show'] ?? ''),
            'sale_start_date' => $this->parseSaleDate($row['sale_start_date'] ?? ''),
            'sale_end_date' => $this->parseSaleDate($row['sale_end_date'] ?? ''),
            'is_active' => true,
            'is_featured' => $isFeatured,
            'published_at' => now(),
        ];
    }

    /**
     * Parse featured flag from Excel column
     */
    private function parseFeaturedFlag(string $featuredFlag): bool
    {
        $normalized = trim(strtolower($featuredFlag));
        return in_array($normalized, ['1', 'true', 'yes', 'ya', 'featured', 'on']);
    }

    private function parseProductTypeColumn(string $productTypeString): array
    {
        // Log for debugging
        Log::info('Parsing product type: ' . $productTypeString);
        
        if (empty($productTypeString)) {
            return [
                'gender_target' => [],
                'product_type' => 'lifestyle_casual'
            ];
        }

        // Clean the input string
        $productTypeString = trim($productTypeString, '"');
        $productTypeString = str_replace('""', '"', $productTypeString);

        // Split by comma and clean each part
        $parts = explode(',', $productTypeString);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, function($part) {
            return !empty($part);
        });

        Log::info('Split parts: ' . json_encode($parts));

        $genderTarget = [];
        $productType = 'lifestyle_casual';

        // Parse GENDER from first part
        if (!empty($parts[0])) {
            $firstPart = strtolower(trim($parts[0]));
            
            switch ($firstPart) {
                case 'mens':
                case 'men':
                    $genderTarget[] = 'mens';
                    break;
                case 'womens':
                case 'women':
                    $genderTarget[] = 'womens';
                    break;
                case 'unisex':
                    $genderTarget[] = 'unisex';
                    break;
                case 'accessories':
                    $genderTarget[] = 'unisex';
                    break;
            }
        }

        // Parse CATEGORY and TYPE from remaining parts
        if (count($parts) >= 2) {
            $secondPart = strtolower(trim($parts[1]));
            
            // Handle exact cases from your data
            if ($secondPart === 'footwear') {
                // This is definitely footwear
                if (count($parts) >= 3) {
                    $thirdPart = strtolower(trim($parts[2]));
                    
                    switch ($thirdPart) {
                        case 'running':
                            $productType = 'running';
                            break;
                        case 'basketball':
                            $productType = 'basketball';
                            break;
                        case 'tennis':
                            $productType = 'tennis';
                            break;
                        case 'badminton':
                            $productType = 'badminton';
                            break;
                        case 'lifestyle/casual':
                        case 'lifestyle':
                        case 'casual':
                            $productType = 'lifestyle_casual';
                            break;
                        case 'training':
                            $productType = 'training';
                            break;
                        default:
                            $productType = 'lifestyle_casual'; // Default for unknown footwear types
                            break;
                    }
                } else {
                    $productType = 'lifestyle_casual'; // Default for just "Footwear"
                }
            } elseif ($secondPart === 'apparel') {
                // This is definitely apparel
                $productType = 'apparel';
            } elseif ($secondPart === 'caps') {
                $productType = 'caps';
            } elseif ($secondPart === 'bags') {
                $productType = 'bags';
            } else {
                // Handle edge cases - might be old format or direct type
                switch ($secondPart) {
                    case 'running':
                        $productType = 'running';
                        break;
                    case 'basketball':
                        $productType = 'basketball';
                        break;
                    case 'tennis':
                        $productType = 'tennis';
                        break;
                    case 'badminton':
                        $productType = 'badminton';
                        break;
                    case 'training':
                        $productType = 'training';
                        break;
                    case 'accessories':
                        $productType = 'accessories';
                        break;
                    default:
                        $productType = 'lifestyle_casual';
                        break;
                }
            }
        } else if (count($parts) == 1) {
            // Only one part - could be just gender or just type
            $singlePart = strtolower(trim($parts[0]));
            
            // If not a gender, treat as product type
            if (!in_array($singlePart, ['mens', 'men', 'womens', 'women', 'unisex', 'accessories'])) {
                switch ($singlePart) {
                    case 'apparel':
                        $productType = 'apparel';
                        break;
                    case 'footwear':
                        $productType = 'lifestyle_casual';
                        break;
                    case 'running':
                        $productType = 'running';
                        break;
                    case 'basketball':
                        $productType = 'basketball';
                        break;
                    case 'tennis':
                        $productType = 'tennis';
                        break;
                    case 'badminton':
                        $productType = 'badminton';
                        break;
                    case 'training':
                        $productType = 'training';
                        break;
                    case 'caps':
                        $productType = 'caps';
                        break;
                    case 'bags':
                        $productType = 'bags';
                        break;
                    default:
                        $productType = 'lifestyle_casual';
                        break;
                }
            }
        }

        // Special handling for accessories categories
        if (empty($genderTarget) && in_array($productType, ['caps', 'bags', 'accessories'])) {
            $genderTarget[] = 'unisex';
        }

        $result = [
            'gender_target' => array_values(array_unique($genderTarget)),
            'product_type' => $productType
        ];

        Log::info('Parsed result: ' . json_encode($result));
        
        return $result;
    }

    private function detectGenderFromProductName(string $productName): array
    {
        $name = strtolower($productName);
        $genderTarget = [];
        
        if (str_contains($name, 'pria') || str_contains($name, ' men ') || str_contains($name, 'male')) {
            $genderTarget[] = 'mens';
        }
        
        if (str_contains($name, 'wanita') || str_contains($name, 'women') || str_contains($name, 'female')) {
            $genderTarget[] = 'womens';
        }
        
        if (count($genderTarget) > 1) {
            return ['unisex'];
        }
        
        return array_values(array_unique($genderTarget));
    }

    private function parseSaleShow(string $saleShow): bool
    {
        $normalized = trim(strtolower($saleShow));
        return in_array($normalized, ['on', 'true', '1', 'yes', 'ya']);
    }

    private function generateShortDescription(string $name): string
    {
        if (empty($name)) return '';
        return Str::limit($name, 100);
    }

    private function parseSaleDate(string $dateString): ?string
    {
        if (empty($dateString) || 
            $dateString === 'dd/mm/yyyy,00:00:00,PM' || 
            $dateString === 'dd/mm/yyyy,00:00:00,AM') {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractImages(array $row): array
    {
        $images = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $imageKey = "images_{$i}";
            if (!empty($row[$imageKey])) {
                $imageUrl = trim($row[$imageKey]);
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $images[] = $imageUrl;
                }
            }
        }

        return array_values($images);
    }

    private function findOrCreateCategory(string $productType): Category
    {
        $categoryName = match($productType) {
            'running' => 'Running',
            'basketball' => 'Basketball', 
            'tennis' => 'Tennis',
            'badminton' => 'Badminton',
            'lifestyle_casual' => 'Lifestyle',
            'sneakers' => 'Sneakers',
            'training' => 'Training',
            'formal' => 'Formal',
            'sandals' => 'Sandals',
            'boots' => 'Boots',
            'apparel' => 'Apparel',
            'caps' => 'Caps & Hats',
            'bags' => 'Bags',
            'accessories' => 'Accessories',
            default => 'Lifestyle'
        };

        return Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => Str::slug($categoryName),
                'description' => $categoryName . ' products',
                'is_active' => true
            ]
        );
    }

    private function parseFloat($value): float
    {
        if (empty($value)) return 0.0;
        
        $value = str_replace(',', '.', $value);
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        return (float) $cleaned;
    }

    private function extractDimension(array $row, array $possibleColumnNames): ?float
    {
        foreach ($possibleColumnNames as $columnName) {
            if (isset($row[$columnName]) && !empty($row[$columnName])) {
                $value = $this->parseFloat($row[$columnName]);
                if ($value > 0) {
                    return $value;
                }
            }
        }
        return null;
    }

    public function testConnection(): array
    {
        try {
            $csvUrl = "https://docs.google.com/spreadsheets/d/{$this->spreadsheetId}/export?format=csv";
            $response = Http::timeout(10)->head($csvUrl);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to Google Sheets',
                    'status_code' => $response->status()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to Google Sheets',
                    'status_code' => $response->status()
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    public function previewData(int $limit = 5): array
    {
        try {
            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'No data found in Google Sheets'
                ];
            }

            $preview = array_slice($data, 0, $limit);
            $processed = [];

            foreach ($preview as $row) {
                $productTypeString = trim($row['product_type'] ?? '');
                $parsedData = $this->parseProductTypeColumn($productTypeString);

                $processed[] = [
                    'name' => $row['name'] ?? 'No name',
                    'brand' => $row['brand'] ?? 'No brand',
                    'sku_parent' => $row['sku_parent'] ?? 'No SKU parent',
                    'sku' => $row['sku'] ?? 'No SKU',
                    'price' => $this->parseFloat($row['price'] ?? 0),
                    'stock' => (int) ($row['stock_quantity'] ?? 0),
                    'size' => $row['available_sizes'] ?? 'No size',
                    'product_type_raw' => $productTypeString,
                    'product_type' => $parsedData['product_type'],
                    'gender_target' => $parsedData['gender_target'],
                    'images_count' => count($this->extractImages($row)),
                    'is_featured' => $this->parseFeaturedFlag($row['is_featured'] ?? ''),
                ];
            }

            return [
                'success' => true,
                'total_rows' => count($data),
                'preview_count' => count($processed),
                'data' => $processed,
                'headers' => !empty($data) ? array_keys($data[0]) : []
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ];
        }
    }

    public function analyzeProductTypes(): array
    {
        try {
            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'No data found in Google Sheets'
                ];
            }

            $productTypeStats = [];
            $genderStats = ['mens' => 0, 'womens' => 0, 'unisex' => 0, 'none' => 0];
            $featuredStats = ['featured' => 0, 'not_featured' => 0];
            $rawFormats = [];
            
            foreach ($data as $row) {
                $productTypeString = trim($row['product_type'] ?? '');
                $parsedData = $this->parseProductTypeColumn($productTypeString);
                
                $rawFormats[$productTypeString] = ($rawFormats[$productTypeString] ?? 0) + 1;
                
                $productType = $parsedData['product_type'];
                $productTypeStats[$productType] = ($productTypeStats[$productType] ?? 0) + 1;
                
                $genders = $parsedData['gender_target'];
                if (!empty($genders)) {
                    foreach ($genders as $gender) {
                        if (in_array($gender, ['mens', 'womens', 'unisex'])) {
                            $genderStats[$gender]++;
                        }
                    }
                } else {
                    $genderStats['none']++;
                }
                
                // Count featured products
                $isFeatured = $this->parseFeaturedFlag($row['is_featured'] ?? '');
                if ($isFeatured) {
                    $featuredStats['featured']++;
                } else {
                    $featuredStats['not_featured']++;
                }
            }
            
            arsort($productTypeStats);
            arsort($rawFormats);
            
            return [
                'success' => true,
                'total_rows' => count($data),
                'product_type_distribution' => $productTypeStats,
                'gender_distribution' => $genderStats,
                'featured_distribution' => $featuredStats,
                'raw_formats' => array_slice($rawFormats, 0, 10),
                'unique_product_types' => count($productTypeStats)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage()
            ];
        }
    }

    public function testParsingLogic(string $productTypeString): array
    {
        $result = $this->parseProductTypeColumn($productTypeString);
        
        $parts = explode(',', $productTypeString);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts);
        
        $breakdown = [
            'input_string' => $productTypeString,
            'split_parts' => $parts,
            'first_part' => $parts[0] ?? 'none',
            'last_part' => $parts[count($parts) - 1] ?? 'none',
            'total_parts' => count($parts),
            'parsed_gender' => $result['gender_target'],
            'parsed_type' => $result['product_type']
        ];
        
        return [
            'success' => true,
            'breakdown' => $breakdown,
            'result' => $result
        ];
    }

    /**
     * Debug method to analyze featured products
     */
    public function analyzeFeaturedProducts(): array
    {
        try {
            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'No data found in Google Sheets'
                ];
            }

            $featuredProducts = [];
            $featuredCount = 0;
            $notFeaturedCount = 0;
            
            foreach ($data as $index => $row) {
                $isFeatured = $this->parseFeaturedFlag($row['is_featured'] ?? '');
                
                if ($isFeatured) {
                    $featuredCount++;
                    $featuredProducts[] = [
                        'row' => $index + 2,
                        'name' => $row['name'] ?? 'No name',
                        'sku' => $row['sku'] ?? 'No SKU',
                        'brand' => $row['brand'] ?? 'No brand',
                        'price' => $this->parseFloat($row['price'] ?? 0),
                        'featured_flag' => $row['is_featured'] ?? '',
                        'parsed_featured' => $isFeatured
                    ];
                } else {
                    $notFeaturedCount++;
                }
            }
            
            return [
                'success' => true,
                'total_rows' => count($data),
                'featured_count' => $featuredCount,
                'not_featured_count' => $notFeaturedCount,
                'featured_percentage' => round(($featuredCount / count($data)) * 100, 2),
                'featured_products' => $featuredProducts
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Featured analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get column mapping with featured support
     */
    public function getColumnMapping(): array
    {
        return [
            // Basic product info
            'name' => 'Product Name',
            'description' => 'Product Description', 
            'brand' => 'Brand',
            'sku_parent' => 'SKU Parent (Product Family)',
            'sku' => 'SKU (Individual Product)',
            
            // Pricing
            'price' => 'Regular Price',
            'sale_price' => 'Sale Price (Optional)',
            
            // Inventory
            'stock_quantity' => 'Stock Quantity',
            'weight' => 'Weight (kg)',
            'length' => 'Length (cm) - or "lengh" in sheets',
            'width' => 'Width (cm) - or "wide" in sheets', 
            'height' => 'Height (cm) - or "high" in sheets',
            
            // Product classification
            'product_type' => 'Product Type (Gender,Category,Type)',
            'available_sizes' => 'Available Sizes',
            
            // Images
            'images_1' => 'Image 1 URL',
            'images_2' => 'Image 2 URL',
            'images_3' => 'Image 3 URL',
            'images_4' => 'Image 4 URL',
            'images_5' => 'Image 5 URL',
            
            // Sale management
            'sale_show' => 'Featured in Sale (on/off)',
            'sale_start_date' => 'Sale Start Date',
            'sale_end_date' => 'Sale End Date',
            
            // Featured product
            'is_featured' => 'Featured Product (1/0, true/false, yes/no)'
        ];
    }

    /**
     * Validate Excel headers
     */
    public function validateHeaders(): array
    {
        try {
            $data = $this->fetchGoogleSheetsData();
            
            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'No data found in Google Sheets'
                ];
            }

            $actualHeaders = !empty($data) ? array_keys($data[0]) : [];
            $expectedHeaders = array_keys($this->getColumnMapping());
            
            $missingHeaders = array_diff($expectedHeaders, $actualHeaders);
            $extraHeaders = array_diff($actualHeaders, $expectedHeaders);
            $validHeaders = array_intersect($expectedHeaders, $actualHeaders);
            
            return [
                'success' => true,
                'total_headers' => count($actualHeaders),
                'actual_headers' => $actualHeaders,
                'expected_headers' => $expectedHeaders,
                'valid_headers' => $validHeaders,
                'missing_headers' => $missingHeaders,
                'extra_headers' => $extraHeaders,
                'validation_score' => round((count($validHeaders) / count($expectedHeaders)) * 100, 2) . '%'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Header validation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate Excel template with all required columns
     */
    public function generateExcelTemplate(): array
    {
        $columns = $this->getColumnMapping();
        $sampleData = [
            [
                'name' => 'Nike Air Force 1 Triple White',
                'description' => 'Classic white sneakers for everyday wear',
                'brand' => 'Nike',
                'sku_parent' => 'AF1-TRIPLE-WHITE',
                'sku' => 'AF1-TW-001-42',
                'price' => '1500000',
                'sale_price' => '',
                'stock_quantity' => '10',
                'weight' => '0.8',
                'length' => '30',
                'width' => '12',
                'height' => '11',
                'product_type' => 'Mens,Footwear,Lifestyle/Casual',
                'available_sizes' => '42',
                'images_1' => 'https://example.com/image1.jpg',
                'images_2' => '',
                'images_3' => '',
                'images_4' => '',
                'images_5' => '',
                'sale_show' => 'off',
                'sale_start_date' => '',
                'sale_end_date' => '',
                'is_featured' => '1'
            ],
            [
                'name' => 'Adidas Stan Smith Classic',
                'description' => 'Timeless white tennis shoes',
                'brand' => 'Adidas',
                'sku_parent' => 'STAN-SMITH-CLASSIC',
                'sku' => 'SS-CL-002-41',
                'price' => '1200000',
                'sale_price' => '1000000',
                'stock_quantity' => '5',
                'weight' => '0.7',
                'length' => '29',
                'width' => '11',
                'height' => '10',
                'product_type' => 'Womens,Footwear,Tennis',
                'available_sizes' => '41',
                'images_1' => 'https://example.com/image2.jpg',
                'images_2' => '',
                'images_3' => '',
                'images_4' => '',
                'images_5' => '',
                'sale_show' => 'on',
                'sale_start_date' => '2025-01-01',
                'sale_end_date' => '2025-01-31',
                'is_featured' => '0'
            ]
        ];
        
        return [
            'success' => true,
            'columns' => $columns,
            'sample_data' => $sampleData,
            'instructions' => [
                '1. Copy the column headers to your Google Sheets',
                '2. Fill in your product data following the sample format',
                '3. Use the product_type format: "Gender,Category,Type"',
                '4. Set is_featured to 1 for featured products, 0 for normal products',
                '5. Use sale_show "on" to feature in sale section',
                '6. Run sync from admin panel after updating'
            ]
        ];
    }
}