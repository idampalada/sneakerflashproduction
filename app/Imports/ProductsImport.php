<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Exception;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private $importErrors = [];
    private $successCount = 0;
    private $skipCount = 0;

    public function collection(Collection $rows)
    {
        Log::info('Import started', ['total_rows' => $rows->count()]);

        foreach ($rows as $index => $row) {
            try {
                Log::debug('Processing row', ['row' => $index + 2, 'data' => $row->toArray()]);
                $this->processRow($row, $index + 2);
            } catch (Exception $e) {
                $this->importErrors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray()
                ];
                Log::error('Import error on row ' . ($index + 2), [
                    'error' => $e->getMessage(),
                    'data' => $row->toArray()
                ]);
            }
        }

        Log::info('Import completed', [
            'success' => $this->successCount,
            'errors' => count($this->importErrors),
            'skipped' => $this->skipCount
        ]);
    }

    private function processRow($row, $rowNumber)
    {
        // Skip empty rows
        if ($this->isEmptyRow($row)) {
            $this->skipCount++;
            Log::debug('Skipped empty row', ['row' => $rowNumber]);
            return;
        }

        // Validate required fields
        if (empty($row['name'])) {
            throw new Exception("Product name is required");
        }

        if (empty($row['price']) || !is_numeric($row['price'])) {
            throw new Exception("Valid price is required");
        }

        try {
            // Find or create category
            $categoryId = null;
            if (!empty($row['category'])) {
                $category = Category::firstOrCreate(
                    ['name' => trim($row['category'])],
                    [
                        'slug' => Str::slug($row['category']),
                        'is_active' => true
                    ]
                );
                $categoryId = $category->id;
            }

            // Process images
            $images = $this->processImages($row['images'] ?? '');

            // Process JSON arrays 
            $genderTarget = $this->processArrayField($row['gender_target'] ?? '');
            $availableColors = $this->processArrayField($row['available_colors'] ?? '');
            $availableSizes = $this->processArrayField($row['available_sizes'] ?? '');
            $features = $this->processArrayField($row['features'] ?? '');
            $searchKeywords = $this->processArrayField($row['search_keywords'] ?? '');

            // Generate SKU if not provided
            $sku = !empty($row['sku']) ? $row['sku'] : $this->generateSKU($row['name'], $row['brand'] ?? '');

            // Convert boolean fields
            $isActive = $this->convertToBoolean($row['is_active'] ?? true);
            $isFeatured = $this->convertToBoolean($row['is_featured'] ?? false);
            $isFeaturedSale = $this->convertToBoolean($row['is_featured_sale'] ?? false);

            // Process numeric fields
            $price = (float) $row['price'];
            $salePrice = !empty($row['sale_price']) && is_numeric($row['sale_price']) ? (float) $row['sale_price'] : null;
            $stockQuantity = isset($row['stock_quantity']) && is_numeric($row['stock_quantity']) ? (int) $row['stock_quantity'] : 0;
            $weight = !empty($row['weight']) && is_numeric($row['weight']) ? (float) $row['weight'] : null;

            // Prepare product data
            $productData = [
                'name' => trim($row['name']),
                'slug' => Product::generateUniqueSlug($row['name']),
                'short_description' => $row['short_description'] ?? '',
                'description' => $row['description'] ?? $row['short_description'] ?? '',
                'price' => $price,
                'sale_price' => $salePrice,
                'sku' => $sku,
                'category_id' => $categoryId,
                'brand' => !empty($row['brand']) ? trim($row['brand']) : null,
                'images' => $images,
                'features' => $features,
                'is_active' => $isActive,
                'is_featured' => $isFeatured,
                'is_featured_sale' => $isFeaturedSale,
                'stock_quantity' => $stockQuantity,
                'weight' => $weight,
                'published_at' => now(),
                'gender_target' => $genderTarget,
                'product_type' => !empty($row['product_type']) ? trim($row['product_type']) : null,
                'search_keywords' => $searchKeywords,
                'available_sizes' => $availableSizes,
                'available_colors' => $availableColors,
            ];

            // Process sale dates if provided
            if (!empty($row['sale_start_date'])) {
                $productData['sale_start_date'] = $this->processDate($row['sale_start_date']);
            }
            if (!empty($row['sale_end_date'])) {
                $productData['sale_end_date'] = $this->processDate($row['sale_end_date']);
            }

            // Check if product already exists (by SKU)
            $existingProduct = Product::where('sku', $sku)->first();
            
            if ($existingProduct) {
                $existingProduct->update($productData);
                Log::info('Updated product', ['sku' => $sku, 'name' => $productData['name']]);
            } else {
                Product::create($productData);
                Log::info('Created product', ['sku' => $sku, 'name' => $productData['name']]);
            }

            $this->successCount++;

        } catch (Exception $e) {
            throw new Exception("Failed to process product: " . $e->getMessage());
        }
    }

    private function isEmptyRow($row): bool
    {
        return empty($row['name']) && empty($row['price']);
    }

    private function processImages(string $imagesString): array
{
    if (empty($imagesString)) {
        return [];
    }

    // Split by comma for multiple images
    $imageUrls = array_map('trim', explode(',', $imagesString));
    $processedImages = [];

    foreach ($imageUrls as $imageUrl) {
        if (empty($imageUrl)) continue;

        // Case 1: Full URL (http/https) - simpan langsung
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $processedImages[] = $imageUrl;
        } 
        // Case 2: URL yang starts dengan http tapi mungkin tidak valid menurut filter_var
        elseif (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            $processedImages[] = $imageUrl;
        }
        // Case 3: Local filename atau path
        else {
            // Jika hanya filename, asumsikan ada di products/
            if (!str_contains($imageUrl, '/')) {
                $processedImages[] = 'products/' . $imageUrl;
            } 
            // Jika sudah ada path, gunakan as-is
            else {
                $processedImages[] = $imageUrl;
            }
        }
    }

    return $processedImages;
}

    private function processArrayField(string $field): array
    {
        if (empty($field)) {
            return [];
        }

        if (str_starts_with(trim($field), '[') || str_starts_with(trim($field), '{')) {
            try {
                return json_decode($field, true) ?? [];
            } catch (Exception $e) {
                Log::warning('Failed to parse JSON field', ['field' => $field, 'error' => $e->getMessage()]);
            }
        }

        return array_filter(array_map('trim', explode(',', $field)));
    }

    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'ya', 'active', 'aktif']);
        }

        return (bool) $value;
    }

    private function processDate($dateString)
    {
        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (Exception $e) {
            Log::warning('Failed to parse date', ['date' => $dateString, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function generateSKU(string $name, string $brand = ''): string
    {
        $brandPart = $brand ? strtoupper(substr($brand, 0, 3)) : 'PRD';
        $namePart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        $randomPart = strtoupper(Str::random(4));
        
        $sku = $brandPart . '-' . $namePart . '-' . $randomPart;
        
        // Ensure SKU is unique
        $counter = 1;
        $originalSku = $sku;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter;
            $counter++;
        }
        
        return $sku;
    }

    // Getter methods for results
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function hasErrors(): bool
    {
        return count($this->importErrors) > 0;
    }
}