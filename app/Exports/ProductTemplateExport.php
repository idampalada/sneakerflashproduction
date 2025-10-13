<?php

namespace App\Exports;

// Alternative approach - tanpa styling dulu
class ProductTemplateExport implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function array(): array
    {
        // Sample data untuk template
        return [
            [
                'Nike Air Jordan 1 Retro High',
                'nike-air-jordan-1-retro-high',
                'Classic basketball shoe with premium materials',
                'The Nike Air Jordan 1 Retro High brings classic style and premium comfort. Perfect for both casual wear and basketball.',
                1200000,
                1000000,
                'NIKE-AIR001',
                'Sneakers',
                'Nike',
                'https://example.com/image1.jpg,https://example.com/image2.jpg',
                'mens,womens',
                'sneakers',
                'Black,White,Red',
                '40,41,42,43,44',
                'Premium Quality,Air Cushioning,Durable',
                'basketball,sport,casual,premium',
                15,
                0.8,
                'true',
                'true',
                'false',
                '2024-01-01',
                '2024-12-31'
            ],
            [
                'Adidas Ultraboost 22',
                '',
                'Performance running shoe',
                'Advanced running shoe with Boost technology for maximum energy return.',
                1500000,
                '',
                '',
                'Running Shoes', 
                'Adidas',
                'https://example.com/adidas1.jpg',
                'mens,womens',
                'running',
                'Black,Blue,White',
                '39,40,41,42,43',
                'Boost Technology,Breathable,Lightweight',
                'running,sport,performance',
                20,
                0.9,
                '1',
                '0',
                '0',
                '',
                ''
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'name',                 // A - Product Name (REQUIRED)
            'slug',                 // B - URL Slug (optional, auto-generated)
            'short_description',    // C - Short Description  
            'description',          // D - Full Description
            'price',               // E - Regular Price (REQUIRED)
            'sale_price',          // F - Sale Price (optional)
            'sku',                 // G - SKU (optional, auto-generated)
            'category',            // H - Category Name
            'brand',               // I - Brand Name
            'images',              // J - Image URLs (comma separated)
            'gender_target',       // K - Gender Target (mens,womens,kids)
            'product_type',        // L - Product Type
            'available_colors',    // M - Available Colors (comma separated)
            'available_sizes',     // N - Available Sizes (comma separated)
            'features',            // O - Key Features (comma separated)
            'search_keywords',     // P - Search Keywords (comma separated)
            'stock_quantity',      // Q - Stock Quantity
            'weight',              // R - Weight in KG
            'is_active',           // S - Active (true/false or 1/0)
            'is_featured',         // T - Featured (true/false or 1/0)
            'is_featured_sale',    // U - Featured Sale (true/false or 1/0)
            'sale_start_date',     // V - Sale Start Date (YYYY-MM-DD)
            'sale_end_date',       // W - Sale End Date (YYYY-MM-DD)
        ];
    }
}