<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Sheets Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Sheets synchronization with product database.
    | This allows automatic import of products from Google Sheets.
    |
    */

    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', '1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg'),

    'sheet_name' => env('GOOGLE_SHEETS_SHEET_NAME', 'Sheet1'),

    'range' => env('GOOGLE_SHEETS_RANGE', 'A1:Z1000'),

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Settings for how the sync process should behave
    |
    */

    'sync_settings' => [
        'timeout' => 60, // Timeout in seconds for HTTP requests
        'chunk_size' => 100, // Number of rows to process in one batch
        'update_existing' => true, // Whether to update existing products
        'create_categories' => true, // Whether to auto-create categories
        'default_category' => 'Lifestyle', // Default category name
        'default_weight' => 500, // Default weight in grams
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Google Sheets columns to product attributes
    |
    */

    'column_mapping' => [
        'product_type' => 'product_type',
        'brand' => 'brand',
        'name' => 'name',
        'description' => 'description',
        'sku_parent' => 'sku_parent',
        'available_sizes' => 'available_sizes',
        'price' => 'price',
        'sale_price' => 'sale_price',
        'stock_quantity' => 'stock_quantity',
        'sku' => 'sku',
        'weight' => 'weight',
        'length' => 'lengh', // Note: typo in sheets
        'width' => 'wide',
        'height' => 'high',
        'sale_show' => 'sale_show',
        'sale_start_date' => 'sale_start_date',
        'sale_end_date' => 'sale_end_date',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling product images from Google Sheets
    |
    */

    'images' => [
        'columns' => ['images_1', 'images_2', 'images_3', 'images_4', 'images_5'],
        'max_images' => 5,
        'validate_urls' => true,
        'default_image' => 'images/default-product.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Type Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Google Sheets product types to database values
    |
    */

    'product_type_mapping' => [
        'footwear' => 'sneakers',
        'sepatu' => 'sneakers',
        'apparel' => 'apparel',
        'pakaian' => 'apparel',
        'baju' => 'apparel',
        'lifestyle/casual' => 'lifestyle_casual',
        'lifestyle' => 'lifestyle_casual',
        'casual' => 'lifestyle_casual',
        'running' => 'running',
        'basketball' => 'basketball',
        'training' => 'training',
        'formal' => 'formal',
        'sandals' => 'sandals',
        'boots' => 'boots',
        'accessories' => 'accessories',
        'backpack' => 'backpack',
        'bag' => 'bag',
        'hat' => 'hat',
        'socks' => 'socks',
        'laces' => 'laces',
        'care_products' => 'care_products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gender Target Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Google Sheets gender values to database values
    |
    */

    'gender_target_mapping' => [
        'mens' => 'mens',
        'men' => 'mens',
        'pria' => 'mens',
        'male' => 'mens',
        'womens' => 'womens',
        'women' => 'womens',
        'wanita' => 'womens',
        'female' => 'womens',
        'kids' => 'kids',
        'anak' => 'kids',
        'children' => 'kids',
        'unisex' => ['mens', 'womens'], // Special case: unisex = both
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Mapping
    |--------------------------------------------------------------------------
    |
    | Maps product types to category names
    |
    */

    'category_mapping' => [
        'apparel' => 'Apparel',
        'lifestyle_casual' => 'Lifestyle',
        'running' => 'Running',
        'basketball' => 'Basketball',
        'sneakers' => 'Sneakers',
        'training' => 'Training',
        'formal' => 'Formal',
        'sandals' => 'Sandals',
        'boots' => 'Boots',
        'accessories' => 'Accessories',
        'backpack' => 'Bags',
        'bag' => 'Bags',
        'hat' => 'Accessories',
        'socks' => 'Accessories',
        'laces' => 'Accessories',
        'care_products' => 'Care Products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for imported data
    |
    */

    'validation' => [
        'required_fields' => ['name', 'sku_parent', 'price'],
        'numeric_fields' => ['price', 'sale_price', 'stock_quantity', 'weight'],
        'max_name_length' => 255,
        'max_description_length' => 65535,
        'min_price' => 0,
        'max_price' => 999999999,
        'min_stock' => 0,
        'max_stock' => 999999,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Logging configuration for sync operations
    |
    */

    'logging' => [
        'enabled' => true,
        'channel' => 'single', // Use 'single' or create custom channel
        'level' => 'info',
        'log_successful_imports' => true,
        'log_skipped_rows' => true,
        'log_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync History
    |--------------------------------------------------------------------------
    |
    | Whether to keep track of sync history
    |
    */

    'sync_history' => [
        'enabled' => true,
        'table_name' => 'google_sheets_sync_logs',
        'keep_days' => 30, // Keep logs for 30 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | How to handle various error scenarios
    |
    */

    'error_handling' => [
        'continue_on_error' => true, // Continue processing even if some rows fail
        'max_errors' => 50, // Stop processing if more than 50 errors
        'skip_invalid_images' => true, // Skip invalid image URLs
        'skip_invalid_dates' => true, // Skip invalid date formats
        'default_on_parse_error' => true, // Use defaults when parsing fails
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Google Sheets API related settings
    |
    */

    'api' => [
        'base_url' => 'https://docs.google.com/spreadsheets/d',
        'export_format' => 'csv', // csv, xlsx, ods
        'public_access' => true, // Whether sheets are publicly accessible
        'retry_attempts' => 3, // Number of retry attempts on failure
        'retry_delay' => 2, // Delay between retries in seconds
    ],

];