<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Safe version - hanya field core yang pasti ada
     */
    public function run(): void
    {
        // Cek kolom yang tersedia
        $availableColumns = Schema::getColumnListing('categories');
        $this->command->info('📋 Available columns: ' . implode(', ', $availableColumns));

        $categories = [
            [
                'name' => 'All Footwear',
                'slug' => 'all-footwear',
                'description' => 'Complete collection of footwear for all occasions',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Lifestyle/Casual',
                'slug' => 'lifestyle-casual',
                'description' => 'Casual sneakers and lifestyle shoes for everyday wear',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Running',
                'slug' => 'running',
                'description' => 'High-performance running shoes with advanced cushioning',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Training',
                'slug' => 'training',
                'description' => 'Cross-training and gym shoes for fitness enthusiasts',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Basketball',
                'slug' => 'basketball',
                'description' => 'Basketball shoes with superior ankle support and grip',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Basketball Shoes',
                'slug' => 'basketball-shoes',
                'description' => 'Professional basketball footwear with premium technology',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Casual Sneakers',
                'slug' => 'casual-sneakers',
                'description' => 'Comfortable sneakers for daily activities and casual outings',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Running Shoes',
                'slug' => 'running-shoes',
                'description' => 'Advanced running footwear with responsive cushioning',
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Sepatu Compass',
                'slug' => 'sepatu-compass',
                'description' => 'Local Indonesian brand - Sepatu Compass collection',
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Sports accessories, bags, and apparel',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Kids Shoes',
                'slug' => 'kids-shoes',
                'description' => 'Comfortable and durable shoes designed for children',
                'sort_order' => 11,
                'is_active' => true,
            ],
        ];

        $this->command->info('🔄 Creating categories...');

        foreach ($categories as $categoryData) {
            try {
                $category = Category::updateOrCreate(
                    ['slug' => $categoryData['slug']],
                    $categoryData
                );

                $this->command->info("✅ {$category->name}");
            } catch (\Exception $e) {
                $this->command->error("❌ Failed to create {$categoryData['name']}: " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info('🎉 Categories created successfully!');
        $this->command->info('📋 Categories now match your frontend filters:');
        $this->command->info('   • All Footwear, Lifestyle/Casual, Running, Training, Basketball');
        $this->command->info('   • Basketball Shoes, Casual Sneakers, Running Shoes, Sepatu Compass');
        $this->command->info('   • Accessories, Kids Shoes');
        $this->command->info('');
        $this->command->info('🚀 Next steps:');
        $this->command->info('   1. Visit /admin/categories to see the categories');
        $this->command->info('   2. Create products and assign them to categories');
        $this->command->info('   3. Test filtering at /products');
    }
}