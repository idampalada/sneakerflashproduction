<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Imports\ProductsImport;
use App\Exports\ProductTemplateExport;
use App\Services\GoogleSheetsSync;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $schema = [
            // Basic Information Section
            Forms\Components\Section::make('Basic Information')
                ->description('Essential product details that will appear on your website')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $context, $state, callable $set) {
                            if ($context === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->placeholder('e.g., Nike Air Jordan 1 Retro High')
                        ->helperText('This will be the main product title shown to customers'),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Product::class, 'slug', ignoreRecord: true)
                        ->helperText('Auto-generated from product name. This creates the product URL: /products/your-slug'),

                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(function () {
                            return Category::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->placeholder('Select a category')
                        ->native(true)
                        ->helperText('Main category for product organization and filtering'),

                    Forms\Components\TextInput::make('brand')
                        ->label('Brand')
                        ->maxLength(255)
                        ->placeholder('e.g., Nike, Adidas, Puma')
                        ->helperText('Brand name for filtering in BRAND menu')
                        ->datalist([
                            'Nike',
                            'Adidas', 
                            'Puma',
                            'Converse',
                            'Vans',
                            'New Balance',
                            'Jordan',
                            'Reebok',
                            'ASICS',
                            'Under Armour',
                            'Skechers',
                            'Fila',
                            'DC Shoes',
                            'Timberland',
                        ]),
                ])->columns(2),

            // ⭐ SKU Information Section - From Excel Columns
            Forms\Components\Section::make('SKU Information')
                ->description('SKU Parent groups similar products, individual SKU identifies specific variants')
                ->schema([
                    Forms\Components\TextInput::make('sku_parent')
                        ->label('SKU Parent (Product Family)')
                        ->maxLength(255)
                        ->placeholder('e.g., AIR-FORCE-1-TRIPLE-WHITE')
                        ->helperText('Parent SKU that groups similar products together (same design, different sizes)')
                        ->datalist(function () {
                            return Product::whereNotNull('sku_parent')
                                ->distinct('sku_parent')
                                ->pluck('sku_parent')
                                ->take(10)
                                ->toArray();
                        }),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU (Individual Product)')
                        ->maxLength(255)
                        ->unique(Product::class, 'sku', ignoreRecord: true)
                        ->placeholder('Auto-generated if empty')
                        ->helperText('Unique identifier for this specific product variant'),
                ])->columns(2),

            // Product Images Section
            Forms\Components\Section::make('Product Images')
                ->description('Upload high-quality product images. First image becomes the featured image.')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('Product Images')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                            '4:3', 
                            '16:9',
                        ])
                        ->directory('products')
                        ->visibility('public')
                        ->maxFiles(10)
                        ->reorderable()
                        ->appendFiles()
                        ->imagePreviewHeight('250')
                        ->uploadingMessage('Uploading images...')
                        ->helperText('Upload up to 10 images. First image will be the main featured image.')
                        ->columnSpanFull(),
                ]),

            // Product Description Section
            Forms\Components\Section::make('Product Description')
                ->description('Detailed information about your product')
                ->schema([
                    Forms\Components\Textarea::make('short_description')
                        ->label('Short Description')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('Brief product summary...')
                        ->helperText('Brief description for product cards (max 500 characters)'),

                    Forms\Components\RichEditor::make('description')
                        ->label('Full Product Description')
                        ->required()
                        ->columnSpanFull()
                        ->placeholder('Detailed product information, features, materials, etc...')
                        ->helperText('Detailed product description with formatting.')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                            'blockquote',
                        ]),
                ]),

            // ⭐ Pricing Section - From Excel Columns (price, sale_price)
            Forms\Components\Section::make('Pricing & Sales')
                ->description('Set product pricing. Sale price enables SALE menu display.')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Regular Price')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('1000000')
                        ->helperText('Base price of the product'),

                    Forms\Components\TextInput::make('sale_price')
                        ->label('Sale Price (Optional)')
                        ->numeric()
                        ->prefix('Rp')
                        ->step(1000)
                        ->placeholder('800000')
                        ->helperText('Set this to show product in SALE menu and display discount badge'),
                ])->columns(2),

            // ⭐ Inventory Section - From Excel Columns (stock_quantity, weight)
            Forms\Components\Section::make('Inventory & Specifications')
                ->description('Stock levels and product specifications from Excel columns')
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock Quantity')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Current stock level from Excel'),

                    Forms\Components\TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('0.5')
                        ->helperText('Product weight from Excel'),

                    // ⭐ Excel Column: length (mapped from 'lengh' - typo in sheets)
                    Forms\Components\TextInput::make('length')
                        ->label('Length (cm)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('30.0')
                        ->helperText('Product length from Excel'),

                    // ⭐ Excel Column: width (mapped from 'wide')
                    Forms\Components\TextInput::make('width')
                        ->label('Width (cm)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('20.0')
                        ->helperText('Product width from Excel'),

                    // ⭐ Excel Column: height (mapped from 'high')
                    Forms\Components\TextInput::make('height')
                        ->label('Height (cm)')
                        ->numeric()
                        ->step(0.1)
                        ->placeholder('15.0')
                        ->helperText('Product height from Excel'),
                ])->columns(3),

            // ⭐ Product Classification - From Excel Category Column
            Forms\Components\Section::make('Product Classification')
                ->description('Gender and product type parsed from Excel category column')
                ->schema([
                    Forms\Components\CheckboxList::make('gender_target')
                        ->label('Target Gender')
                        ->options([
                            'mens' => "👨 Men's",
                            'womens' => "👩 Women's", 
                            'kids' => '👶 Kids',
                            'unisex' => '🌐 Unisex',
                        ])
                        ->columns(4)
                        ->helperText('Parsed from Excel category column (e.g., "mens,Footwear,Lifestyle")')
                        ->required(),

                    Forms\Components\Select::make('product_type')
                        ->label('Product Type')
                        ->options([
                            // ⭐ UPDATED: Footwear Types
                            'running' => '🏃 Running',
                            'basketball' => '🏀 Basketball',
                            'tennis' => '🎾 Tennis',
                            'badminton' => '🏸 Badminton',
                            'lifestyle_casual' => '🚶 Lifestyle/Casual',
                            'sneakers' => '👟 Sneakers',
                            'training' => '💪 Training',
                            'formal' => '👔 Formal',
                            'sandals' => '🩴 Sandals',
                            'boots' => '🥾 Boots',
                            
                            // ⭐ UPDATED: Apparel
                            'apparel' => '👕 Apparel',
                            
                            // ⭐ UPDATED: Accessories
                            'caps' => '🧢 Caps & Hats',
                            'bags' => '👜 Bags',
                            'accessories' => '🎒 Accessories',
                        ])
                        ->placeholder('Select product type')
                        ->helperText('Parsed from Excel category column'),

                    // ⭐ Excel Column: available_sizes
                    Forms\Components\TagsInput::make('available_sizes')
                        ->label('Available Sizes')
                        ->placeholder('Add size')
                        ->helperText('Available sizes from Excel (e.g., 40, 41, 42, 43)')
                        ->columnSpanFull(),
                ])->columns(2),

            // ⭐ Sale Management - From Excel Columns
            Forms\Components\Section::make('Sale Management')
                ->description('Sale settings from Excel columns')
                ->schema([
                    // ⭐ Excel Column: sale_show
                    Forms\Components\Toggle::make('is_featured_sale')
                        ->label('Featured in Sale (sale_show from Excel)')
                        ->helperText('Show prominently in SALE menu (from Excel sale_show column)')
                        ->default(false),

                    // ⭐ Excel Column: sale_start_date
                    Forms\Components\DatePicker::make('sale_start_date')
                        ->label('Sale Start Date')
                        ->helperText('When the sale begins (from Excel)'),

                    // ⭐ Excel Column: sale_end_date
                    Forms\Components\DatePicker::make('sale_end_date')
                        ->label('Sale End Date')  
                        ->helperText('When the sale ends (from Excel)'),
                ])->columns(3),

            // Status & Visibility Section
            Forms\Components\Section::make('Product Status & Visibility')
                ->description('Control how this product appears on your website')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active Product')
                        ->default(true)
                        ->helperText('When enabled, product is visible on website'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Product')
                        ->default(false)
                        ->helperText('Featured products appear in "Featured Products" section'),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Publish Date')
                        ->default(now())
                        ->helperText('When this product becomes available'),
                ])->columns(3),
        ];

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            // Image Column
            Tables\Columns\ImageColumn::make('featured_image')
                ->label('Image')
                ->getStateUsing(function (Product $record): ?string {
                    return $record->featured_image;
                })
                ->size(60)
                ->circular(),

            // Basic Product Info
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('name')
                ->label('Product Name')
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(30)
                ->tooltip(function (Product $record): ?string {
                    return $record->name;
                }),

            Tables\Columns\TextColumn::make('brand')
                ->label('Brand')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('info')
                ->formatStateUsing(fn ($state) => $state ?? 'No Brand'),

            // ⭐ EXCEL COLUMN: product_type (from category parsing) - UPDATED OPTIONS
            Tables\Columns\TextColumn::make('product_type')
                ->label('Product Type')
                ->badge()
                ->formatStateUsing(function ($state) {
                    return match($state) {
                        // ⭐ UPDATED: Footwear
                        'running' => '🏃 Running',
                        'basketball' => '🏀 Basketball',
                        'tennis' => '🎾 Tennis',
                        'badminton' => '🏸 Badminton',
                        'lifestyle_casual' => '🚶 Lifestyle',
                        'sneakers' => '👟 Sneakers',
                        'training' => '💪 Training',
                        'formal' => '👔 Formal',
                        'sandals' => '🩴 Sandals',
                        'boots' => '🥾 Boots',
                        
                        // ⭐ UPDATED: Apparel & Accessories
                        'apparel' => '👕 Apparel',
                        'caps' => '🧢 Caps',
                        'bags' => '👜 Bags',
                        'accessories' => '🎒 Accessories',
                        
                        default => $state ?? 'Unknown'
                    };
                })
                ->color(fn ($state) => match($state) {
                    'running' => 'success',
                    'basketball' => 'warning',
                    'tennis' => 'info',
                    'badminton' => 'primary',
                    'training' => 'danger',
                    'lifestyle_casual' => 'info',
                    'sneakers' => 'primary',
                    'apparel' => 'gray',
                    'caps' => 'purple',
                    'bags' => 'orange',
                    'accessories' => 'pink',
                    default => 'secondary'
                })
                ->sortable()
                ->searchable(),

            // ⭐ EXCEL COLUMN: sku_parent
            Tables\Columns\TextColumn::make('sku_parent')
                ->label('SKU Parent')
                ->searchable()
                ->sortable()
                ->copyable()
                ->badge()
                ->color('warning')
                ->formatStateUsing(fn ($state) => $state ?? 'No SKU Parent')
                ->tooltip(function (Product $record): ?string {
                    return "SKU Parent: " . ($record->sku_parent ?? 'Not set');
                })
                ->limit(20)
                ->toggleable(),

            // ⭐ EXCEL COLUMN: sku (individual)
            Tables\Columns\TextColumn::make('sku')
                ->label('SKU')
                ->searchable()
                ->sortable()
                ->copyable()
                ->badge()
                ->color('primary')
                ->formatStateUsing(fn ($state) => $state ?? 'No SKU')
                ->tooltip(function (Product $record): ?string {
                    return "Individual SKU: " . ($record->sku ?? 'Not set');
                })
                ->limit(20)
                ->toggleable(),

            // ⭐ EXCEL COLUMN: gender_target (from category parsing)
            Tables\Columns\TextColumn::make('gender_target')
                ->label('Gender')
                ->badge()
                ->formatStateUsing(function ($state) {
                    if (!$state) return 'No Gender';
                    
                    if (is_array($state)) {
                        $genders = [];
                        foreach ($state as $gender) {
                            $genders[] = match($gender) {
                                'mens' => '👨 Men\'s',
                                'womens' => '👩 Women\'s',
                                'kids' => '👶 Kids',
                                'unisex' => '🌐 Unisex',
                                default => $gender
                            };
                        }
                        return implode(', ', $genders);
                    }
                    
                    return match($state) {
                        'mens' => '👨 Men\'s',
                        'womens' => '👩 Women\'s',
                        'kids' => '👶 Kids',
                        'unisex' => '🌐 Unisex',
                        default => $state
                    };
                })
                ->color(fn ($state) => is_array($state) && count($state) > 1 ? 'warning' : 'info')
                ->searchable(),

            // ⭐ EXCEL COLUMN: available_sizes
            Tables\Columns\TextColumn::make('available_sizes')
                ->label('Sizes')
                ->badge()
                ->formatStateUsing(function ($state) {
                    if (!$state) return 'No Sizes';
                    
                    if (is_array($state)) {
                        $count = count($state);
                        if ($count <= 3) {
                            return implode(', ', $state);
                        }
                        return $count . ' sizes: ' . implode(', ', array_slice($state, 0, 2)) . '...';
                    }
                    
                    return $state;
                })
                ->color('secondary')
                ->tooltip(function (Product $record): ?string {
                    if (is_array($record->available_sizes)) {
                        return 'Available sizes: ' . implode(', ', $record->available_sizes);
                    }
                    return 'No sizes available';
                }),

            // ⭐ EXCEL COLUMN: price
            Tables\Columns\TextColumn::make('price')
                ->label('Price')
                ->money('IDR')
                ->sortable()
                ->alignEnd(),

            // ⭐ EXCEL COLUMN: sale_price
            Tables\Columns\TextColumn::make('sale_price')
                ->label('Sale Price')
                ->money('IDR')
                ->sortable()
                ->alignEnd()
                ->color('danger')
                ->weight('bold')
                ->formatStateUsing(function ($state, Product $record) {
                    if (!$state) return '-';
                    $discount = round((($record->price - $state) / $record->price) * 100);
                    return "Rp " . number_format($state, 0, ',', '.') . " (-{$discount}%)";
                }),

            // ⭐ EXCEL COLUMN: stock_quantity
            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('Stock')
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => match(true) {
                    $state == 0 => 'danger',
                    $state < 10 => 'warning', 
                    default => 'success'
                })
                ->formatStateUsing(fn ($state) => $state == 0 ? 'Out of Stock' : $state),

            // ⭐ EXCEL COLUMN: weight
            Tables\Columns\TextColumn::make('weight')
                ->label('Weight (kg)')
                ->sortable()
                ->alignEnd()
                ->formatStateUsing(fn ($state) => $state ? $state . ' kg' : '-')
                ->toggleable(isToggledHiddenByDefault: true),

            // ⭐ EXCEL COLUMNS: Dimensions (length, width, height)
            Tables\Columns\TextColumn::make('dimensions_display')
                ->label('Dimensions (L×W×H)')
                ->getStateUsing(function (Product $record): string {
                    $length = $record->length ?? '-';
                    $width = $record->width ?? '-';
                    $height = $record->height ?? '-';
                    return "{$length}×{$width}×{$height} cm";
                })
                ->toggleable(isToggledHiddenByDefault: true),

            // ⭐ EXCEL COLUMN: sale_show (mapped to is_featured_sale)
            Tables\Columns\IconColumn::make('is_featured_sale')
                ->label('Featured Sale')
                ->boolean()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            // ⭐ EXCEL COLUMNS: Sale Dates
            Tables\Columns\TextColumn::make('sale_start_date')
                ->label('Sale Start')
                ->date('d M Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('sale_end_date')
                ->label('Sale End')
                ->date('d M Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            // Status Columns
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable(),

            Tables\Columns\IconColumn::make('is_featured')
                ->label('Featured')
                ->boolean()
                ->sortable(),

            // Timestamps
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Last Updated')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        return $table
            ->columns($columns)
            ->filters([
                SelectFilter::make('brand')
                    ->options(function () {
                        return Product::query()
                            ->whereNotNull('brand')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray();
                    })
                    ->multiple(),

                // ⭐ MAIN FILTER: Product Type (UPDATED OPTIONS)
                SelectFilter::make('product_type')
                    ->label('Product Type (from Excel)')
                    ->options([
                        // ⭐ UPDATED: Footwear
                        'running' => '🏃 Running',
                        'basketball' => '🏀 Basketball', 
                        'tennis' => '🎾 Tennis',
                        'badminton' => '🏸 Badminton',
                        'lifestyle_casual' => '🚶 Lifestyle/Casual',
                        'sneakers' => '👟 Sneakers',
                        'training' => '💪 Training',
                        'formal' => '👔 Formal',
                        'sandals' => '🩴 Sandals',
                        'boots' => '🥾 Boots',
                        
                        // ⭐ UPDATED: Apparel & Accessories
                        'apparel' => '👕 Apparel',
                        'caps' => '🧢 Caps & Hats',
                        'bags' => '👜 Bags', 
                        'accessories' => '🎒 Accessories',
                    ])
                    ->multiple(),

                // ⭐ FILTER: Gender Target (from Excel category)
                SelectFilter::make('gender_target')
                    ->label('Gender Target')
                    ->options([
                        'mens' => '👨 Men\'s',
                        'womens' => '👩 Women\'s', 
                        'kids' => '👶 Kids',
                        'unisex' => '🌐 Unisex',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->whereJsonContains('gender_target', $data['value']);
                        }
                        return $query;
                    }),

                // ⭐ FILTER: SKU Parent
                Filter::make('has_sku_parent')
                    ->label('Has SKU Parent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('sku_parent')),

                Filter::make('missing_sku_parent')
                    ->label('Missing SKU Parent')
                    ->query(fn (Builder $query): Builder => $query->whereNull('sku_parent')),

                // ⭐ FILTER: Sale Status
                Filter::make('on_sale')
                    ->label('On Sale')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('sale_price')),

                Filter::make('featured_sale')
                    ->label('Featured Sale')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured_sale', true)),

                // Stock Filters
                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', 0)),

                Filter::make('low_stock')
                    ->label('Low Stock (< 10)')
                    ->query(fn (Builder $query): Builder => $query->where('stock_quantity', '<', 10)->where('stock_quantity', '>', 0)),

                Filter::make('is_featured')
                    ->label('Featured Products')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),

                // ⭐ FILTER: Same SKU Parent Products
                Filter::make('duplicate_sku_parents')
                    ->label('Duplicate SKU Parents')
                    ->query(function (Builder $query): Builder {
                        return $query->whereIn('sku_parent', function ($subQuery) {
                            $subQuery->select('sku_parent')
                                ->from('products')
                                ->whereNotNull('sku_parent')
                                ->groupBy('sku_parent')
                                ->havingRaw('COUNT(*) > 1');
                        });
                    }),
            ])
            ->headerActions([
                // 🧠 SMART SYNC - Primary sync method
                Tables\Actions\Action::make('smart_sync_google_sheets')
                    ->label('🧠 Smart Sync Google Sheets')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Smart Sync Products from Google Sheets')
                    ->modalDescription('This will intelligently sync products: update existing, create new, and DELETE products not in spreadsheet anymore.')
                    ->modalSubmitActionLabel('Start Smart Sync')
                    ->form([
                        Forms\Components\Placeholder::make('warning')
                            ->label('⚠️ Important Warning')
                            ->content(function () {
                                $currentCount = Product::count();
                                return "Current products in database: {$currentCount}. This smart sync will DELETE products that are no longer in the Google Sheets.";
                            }),

                        Forms\Components\Placeholder::make('excel_columns_info')
                            ->label('📋 Excel Columns Being Synced')
                            ->content('Main columns: name, brand, sku_parent, sku, price, sale_price, stock_quantity, weight, length, width, height, available_sizes, category→product_type+gender_target, sale_show→is_featured_sale, sale_dates, images_1-5'),

                        Forms\Components\Placeholder::make('spreadsheet_info')
                            ->label('📊 Spreadsheet Preview')
                            ->content(function () {
                                try {
                                    $service = new GoogleSheetsSync();
                                    $preview = $service->previewData(3);
                                    
                                    if ($preview['success']) {
                                        $skus = array_unique(array_column($preview['data'], 'sku'));
                                        $skuParents = array_unique(array_column($preview['data'], 'sku_parent'));
                                        $existingSkus = Product::pluck('sku')->toArray();
                                        $toDelete = array_diff($existingSkus, $skus);
                                        $toCreate = array_diff($skus, $existingSkus);
                                        $toUpdate = array_intersect($existingSkus, $skus);
                                        
                                        return "
                                            <div style='background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #d1ecf1;'>
                                                <h4 style='margin: 0 0 10px 0; color: #0c5460;'>📈 Sync Preview</h4>
                                                <table style='width: 100%; font-size: 13px;'>
                                                    <tr><td><strong>📊 Total rows in spreadsheet:</strong></td><td>{$preview['total_rows']}</td></tr>
                                                    <tr><td><strong>🏷️ Unique SKU Parents:</strong></td><td>" . count($skuParents) . "</td></tr>
                                                    <tr><td><strong>🔢 Individual SKUs (with sizes):</strong></td><td>" . count($skus) . "</td></tr>
                                                    <tr><td><strong>➕ Products to CREATE:</strong></td><td style='color: green;'>" . count($toCreate) . "</td></tr>
                                                    <tr><td><strong>🔄 Products to UPDATE:</strong></td><td style='color: blue;'>" . count($toUpdate) . "</td></tr>
                                                    <tr><td><strong>🗑️ Products to DELETE:</strong></td><td style='color: red;'>" . count($toDelete) . "</td></tr>
                                                    <tr><td><strong>📊 Final product count:</strong></td><td><strong>" . count($skus) . "</strong></td></tr>
                                                </table>
                                            </div>
                                        ";
                                    } else {
                                        return "<div style='color: red; padding: 10px; background: #f8d7da; border-radius: 5px;'>❌ Failed to preview: {$preview['message']}</div>";
                                    }
                                } catch (Exception $e) {
                                    return "<div style='color: red; padding: 10px; background: #f8d7da; border-radius: 5px;'>💥 Error: {$e->getMessage()}</div>";
                                }
                            }),

                        Forms\Components\Toggle::make('confirm_delete')
                            ->label('✅ I understand products will be deleted')
                            ->helperText('Check this to confirm you understand that products not in spreadsheet will be permanently deleted')
                            ->required(),

                        Forms\Components\Textarea::make('sync_notes')
                            ->label('Sync Notes (Optional)')
                            ->placeholder('Add notes about this smart sync operation...')
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        if (!($data['confirm_delete'] ?? false)) {
                            Notification::make()
                                ->title('❌ Confirmation Required')
                                ->body('You must confirm that you understand products will be deleted.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            Log::info('Smart Google Sheets sync started from admin', [
                                'user_id' => Auth::id(),
                                'options' => $data
                            ]);

                            $syncService = new GoogleSheetsSync();
                            $result = $syncService->syncProducts([
                                'sync_strategy' => 'smart_individual_sku',
                                'notes' => $data['sync_notes'] ?? null
                            ]);

                            if ($result['success']) {
                                $stats = $result['stats'];
                                
                                Notification::make()
                                    ->title('🎉 Smart Sync Successful!')
                                    ->body("✅ Created: {$stats['created']}, 🔄 Updated: {$stats['updated']}, 🗑️ Deleted: {$stats['deleted']} products" . 
                                          ($stats['errors'] > 0 ? ", ❌ Errors: {$stats['errors']}" : ""))
                                    ->success()
                                    ->duration(15000)
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view_sync_log')
                                            ->label('View Sync Log')
                                            ->url('/admin/google-sheets-sync-logs')
                                            ->openUrlInNewTab(),
                                    ])
                                    ->send();

                                if ($stats['deleted'] > 0) {
                                    Notification::make()
                                        ->title('🗑️ Products Deleted')
                                        ->body("Successfully deleted {$stats['deleted']} products that were no longer in the spreadsheet.")
                                        ->warning()
                                        ->duration(10000)
                                        ->send();
                                }

                                if ($stats['errors'] > 0) {
                                    Notification::make()
                                        ->title('⚠️ Sync Warnings')
                                        ->body("Sync completed with {$stats['errors']} errors. Check sync logs for details.")
                                        ->warning()
                                        ->duration(15000)
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('❌ Smart Sync Failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->duration(15000)
                                    ->send();
                            }

                        } catch (Exception $e) {
                            Log::error('Smart Google Sheets sync failed from admin', [
                                'error' => $e->getMessage(),
                                'user_id' => Auth::id(),
                                'trace' => $e->getTraceAsString()
                            ]);

                            Notification::make()
                                ->title('❌ Smart Sync Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->duration(15000)
                                ->send();
                        }
                    }),

                // 🔄 SAFE MODE SYNC - No delete
                Tables\Actions\Action::make('safe_sync_google_sheets')
                    ->label('🔄 Safe Sync (No Delete)')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Safe Sync - No Products Will Be Deleted')
                    ->modalDescription('This will only create new and update existing products. No products will be deleted from your database.')
                    ->modalSubmitActionLabel('Start Safe Sync')
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->label('ℹ️ Safe Sync Information')
                            ->content('This sync method is safe - it will not delete any existing products. It will only create new products and update existing ones based on SKU matching. All Excel columns will be synced.'),
                    ])
                    ->action(function () {
                        try {
                            $syncService = new GoogleSheetsSync();
                            $result = $syncService->syncProductsSafeMode(['sync_strategy' => 'safe_mode_no_delete']);

                            if ($result['success']) {
                                $stats = $result['stats'];
                                
                                Notification::make()
                                    ->title('✅ Safe Sync Successful!')
                                    ->body("Created: {$stats['created']}, Updated: {$stats['updated']} products. No products deleted.")
                                    ->success()
                                    ->duration(10000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('❌ Safe Sync Failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }

                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Sync Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 🔗 TEST CONNECTION
                Tables\Actions\Action::make('test_connection')
                    ->label('🔗 Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('gray')
                    ->action(function () {
                        try {
                            $syncService = new GoogleSheetsSync();
                            $result = $syncService->testConnection();
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->title('✅ Connection Successful')
                                    ->body('Successfully connected to Google Sheets')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('❌ Connection Failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Test Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 👁️ PREVIEW DATA
                Tables\Actions\Action::make('preview_data')
                    ->label('👁️ Preview Data')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->action(function () {
                        try {
                            $syncService = new GoogleSheetsSync();
                            $result = $syncService->previewData(5);
                            
                            if ($result['success']) {
                                $message = "Found {$result['total_rows']} total rows. Preview of first {$result['preview_count']} rows:\n\n";
                                foreach ($result['data'] as $index => $row) {
                                    $genderStr = empty($row['gender_target']) ? 'No Gender' : implode(',', $row['gender_target']);
                                    $message .= ($index + 1) . ". {$row['name']} ({$row['brand']})\n";
                                    $message .= "   Price: Rp " . number_format($row['price']) . " | Size: {$row['size']} | Gender: {$genderStr}\n";
                                    $message .= "   SKU Parent: {$row['sku_parent']} | SKU: {$row['sku']}\n";
                                    $message .= "   Stock: {$row['stock']} | Images: {$row['images_count']}\n\n";
                                }
                                
                                Notification::make()
                                    ->title('📊 Data Preview (All Excel Columns)')
                                    ->body($message)
                                    ->info()
                                    ->duration(20000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('❌ Preview Failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Preview Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ⚡ ANALYZE SKU PATTERNS
                Tables\Actions\Action::make('analyze_sku_patterns')
                    ->label('⚡ Analyze SKU Patterns')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function () {
                        try {
                            $skuParentCounts = Product::selectRaw('sku_parent, COUNT(*) as count')
                                ->whereNotNull('sku_parent')
                                ->groupBy('sku_parent')
                                ->orderBy('count', 'desc')
                                ->limit(10)
                                ->get();
                                
                            $message = "SKU Parent Analysis (Top 10):\n\n";
                            foreach ($skuParentCounts as $item) {
                                $message .= "• {$item->sku_parent}: {$item->count} variants\n";
                            }
                            
                            $totalSkuParents = Product::whereNotNull('sku_parent')->distinct('sku_parent')->count();
                            $totalProducts = Product::count();
                            $message .= "\nSummary:\n";
                            $message .= "• Total SKU Parents: {$totalSkuParents}\n";
                            $message .= "• Total Individual Products: {$totalProducts}\n";
                            $message .= "• Average variants per SKU Parent: " . round($totalProducts / max($totalSkuParents, 1), 1);
                            
                            Notification::make()
                                ->title('📊 SKU Pattern Analysis')
                                ->body($message)
                                ->info()
                                ->duration(15000)
                                ->send();
                                
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Analysis Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 🔍 ANALYZE PRODUCT TYPES  
                Tables\Actions\Action::make('analyze_product_types')
                    ->label('🔍 Analyze Product Types')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('warning')
                    ->action(function () {
                        try {
                            $syncService = new GoogleSheetsSync();
                            
                            // Check if method exists
                            if (!method_exists($syncService, 'analyzeProductTypes')) {
                                Notification::make()
                                    ->title('❌ Method Not Found')
                                    ->body('analyzeProductTypes method not found in GoogleSheetsSync. Please update the file.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $analysis = $syncService->analyzeProductTypes();
                            
                            if ($analysis['success']) {
                                $message = "Product Type Analysis (Parsed from Excel):\n\n";
                                $message .= "📊 Total rows: {$analysis['total_rows']}\n";
                                $message .= "🔢 Unique product types: {$analysis['unique_product_types']}\n\n";
                                
                                $message .= "🏷️ Product Type Distribution (from last part):\n";
                                foreach ($analysis['product_type_distribution'] as $type => $count) {
                                    $emoji = match($type) {
                                        'running' => '🏃',
                                        'basketball' => '🏀',
                                        'tennis' => '🎾',
                                        'badminton' => '🏸',
                                        'training' => '💪',
                                        'lifestyle_casual' => '🚶',
                                        'sneakers' => '👟',
                                        'apparel' => '👕',
                                        'caps' => '🧢',
                                        'bags' => '👜',
                                        'accessories' => '🎒',
                                        default => '•'
                                    };
                                    $message .= "  {$emoji} {$type}: {$count}\n";
                                }
                                
                                $message .= "\n👥 Gender Distribution (from first part):\n";
                                foreach ($analysis['gender_distribution'] as $gender => $count) {
                                    $emoji = match($gender) {
                                        'mens' => '👨',
                                        'womens' => '👩',
                                        'kids' => '👶',
                                        'unisex' => '🌐',
                                        'none' => '❓',
                                        default => '•'
                                    };
                                    $message .= "  {$emoji} {$gender}: {$count}\n";
                                }
                                
                                // Show raw formats
                                if (!empty($analysis['raw_formats'])) {
                                    $message .= "\n📋 Raw Formats (Top 5):\n";
                                    $count = 0;
                                    foreach ($analysis['raw_formats'] as $format => $freq) {
                                        if ($count >= 5) break;
                                        $message .= "  • \"{$format}\" ({$freq}x)\n";
                                        $count++;
                                    }
                                }
                                
                                Notification::make()
                                    ->title('🔍 Product Type Analysis Complete')
                                    ->body($message)
                                    ->info()
                                    ->duration(25000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('❌ Analysis Failed')
                                    ->body($analysis['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Analysis Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                    
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Product')
                    ->modalDescription('Are you sure you want to delete this product? This action cannot be undone.')
                    ->color('danger'),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('success')
                    ->action(function (Product $record) {
                        $newProduct = $record->replicate();
                        $newProduct->name = $record->name . ' (Copy)';
                        $newProduct->sku = $record->sku . '-copy-' . time();
                        $newProduct->slug = Product::generateUniqueSlug($newProduct->name);
                        $newProduct->save();

                        Notification::make()
                            ->title('✅ Product Duplicated')
                            ->body("Product '{$newProduct->name}' has been created")
                            ->success()
                            ->send();
                    }),

                // ⭐ NEW: Show All Excel Data Action
                Tables\Actions\Action::make('show_excel_data')
                    ->label('Excel Data')
                    ->icon('heroicon-m-table-cells')
                    ->color('info')
                    ->modalHeading(fn (Product $record) => 'All Excel Data: ' . $record->name)
                    ->modalContent(function (Product $record) {
                        $relatedProducts = Product::where('sku_parent', $record->sku_parent)
                            ->where('id', '!=', $record->id)
                            ->get(['id', 'name', 'sku', 'available_sizes', 'stock_quantity']);
                            
                        $content = "<div style='font-family: monospace; font-size: 12px;'>";
                        $content .= "<h4>📋 Complete Excel Column Data</h4>";
                        $content .= "<table style='width: 100%; border-collapse: collapse;'>";
                        
                        // Excel columns data
                        $excelData = [
                            'Product Name' => $record->name,
                            'Brand' => $record->brand ?? 'Not set',
                            'SKU Parent' => $record->sku_parent ?? 'Not set',
                            'Individual SKU' => $record->sku ?? 'Not set',
                            'Price' => 'Rp ' . number_format($record->price ?? 0),
                            'Sale Price' => $record->sale_price ? 'Rp ' . number_format($record->sale_price) : 'Not set',
                            'Stock Quantity' => $record->stock_quantity ?? 0,
                            'Weight (kg)' => $record->weight ?? 'Not set',
                            'Length (cm)' => $record->length ?? 'Not set',
                            'Width (cm)' => $record->width ?? 'Not set',
                            'Height (cm)' => $record->height ?? 'Not set',
                            'Available Sizes' => is_array($record->available_sizes) ? implode(', ', $record->available_sizes) : 'No sizes',
                            'Gender Target' => is_array($record->gender_target) ? implode(', ', $record->gender_target) : 'No gender',
                            'Product Type (from Excel)' => $record->product_type ?? 'Not set',
                            'Featured Sale' => $record->is_featured_sale ? 'Yes' : 'No',
                            'Sale Start Date' => $record->sale_start_date ?? 'Not set',
                            'Sale End Date' => $record->sale_end_date ?? 'Not set',
                            'Images Count' => is_array($record->images) ? count($record->images) : 0,
                            'Active' => $record->is_active ? 'Yes' : 'No',
                            'Featured' => $record->is_featured ? 'Yes' : 'No',
                            'Created' => $record->created_at->format('d M Y H:i'),
                            'Last Updated' => $record->updated_at->format('d M Y H:i'),
                        ];
                        
                        foreach ($excelData as $label => $value) {
                            $content .= "<tr>";
                            $content .= "<td style='border: 1px solid #ddd; padding: 8px; background: #f5f5f5; font-weight: bold; width: 30%;'>{$label}</td>";
                            $content .= "<td style='border: 1px solid #ddd; padding: 8px; width: 70%;'>{$value}</td>";
                            $content .= "</tr>";
                        }
                        $content .= "</table>";
                        
                        if ($record->images && is_array($record->images) && count($record->images) > 0) {
                            $content .= "<h4>🖼️ Images from Excel</h4>";
                            foreach ($record->images as $index => $imageUrl) {
                                $content .= "<p><strong>Image " . ($index + 1) . ":</strong> <a href='{$imageUrl}' target='_blank'>" . substr($imageUrl, 0, 60) . "...</a></p>";
                            }
                        }
                        
                        if ($relatedProducts->count() > 0) {
                            $content .= "<h4>🔗 Related Products (Same SKU Parent)</h4>";
                            $content .= "<table style='width: 100%; border-collapse: collapse;'>";
                            $content .= "<tr style='background: #f5f5f5;'>";
                            $content .= "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
                            $content .= "<th style='border: 1px solid #ddd; padding: 8px;'>Name</th>";
                            $content .= "<th style='border: 1px solid #ddd; padding: 8px;'>SKU</th>";
                            $content .= "<th style='border: 1px solid #ddd; padding: 8px;'>Size</th>";
                            $content .= "<th style='border: 1px solid #ddd; padding: 8px;'>Stock</th>";
                            $content .= "</tr>";
                            
                            foreach ($relatedProducts as $related) {
                                $content .= "<tr>";
                                $content .= "<td style='border: 1px solid #ddd; padding: 8px;'>{$related->id}</td>";
                                $content .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . substr($related->name, 0, 40) . "...</td>";
                                $content .= "<td style='border: 1px solid #ddd; padding: 8px;'>{$related->sku}</td>";
                                $content .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . (is_array($related->available_sizes) ? implode(', ', $related->available_sizes) : 'No sizes') . "</td>";
                                $content .= "<td style='border: 1px solid #ddd; padding: 8px;'>{$related->stock_quantity}</td>";
                                $content .= "</tr>";
                            }
                            $content .= "</table>";
                            $content .= "<p><small>Total variants for this SKU Parent: " . ($relatedProducts->count() + 1) . "</small></p>";
                        }
                        
                        $content .= "</div>";
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Products')
                        ->modalDescription('Are you sure you want to delete these products? This action cannot be undone.')
                        ->color('danger'),
                    
                    Tables\Actions\BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => !$record->is_featured]);
                            }
                            
                            Notification::make()
                                ->title('✅ Featured Status Updated')
                                ->body('Featured status has been toggled for selected products')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Toggle Active')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => !$record->is_active]);
                            }
                            
                            Notification::make()
                                ->title('✅ Active Status Updated')
                                ->body('Active status has been toggled for selected products')
                                ->success()
                                ->send();
                        }),

                    // ⭐ NEW: Bulk Update Gender Target
                    Tables\Actions\BulkAction::make('update_gender')
                        ->label('Update Gender Target')
                        ->icon('heroicon-m-users')
                        ->color('warning')
                        ->form([
                            Forms\Components\CheckboxList::make('gender_target')
                                ->label('Gender Target (from Excel category column)')
                                ->options([
                                    'mens' => '👨 Men\'s',
                                    'womens' => '👩 Women\'s',
                                    'kids' => '👶 Kids',
                                    'unisex' => '🌐 Unisex',
                                ])
                                ->required()
                                ->helperText('Select the target gender(s) for selected products'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['gender_target' => $data['gender_target']]);
                            }
                            
                            $genderLabels = [];
                            foreach ($data['gender_target'] as $gender) {
                                $genderLabels[] = match($gender) {
                                    'mens' => "Men's",
                                    'womens' => "Women's", 
                                    'kids' => 'Kids',
                                    'unisex' => 'Unisex',
                                    default => $gender
                                };
                            }
                            
                            Notification::make()
                                ->title('✅ Gender Target Updated')
                                ->body("Gender updated to '" . implode(', ', $genderLabels) . "' for " . count($records) . " products")
                                ->success()
                                ->send();
                        }),

                    // ⭐ NEW: Bulk Update Product Type
                    Tables\Actions\BulkAction::make('update_product_type')
                        ->label('Update Product Type')
                        ->icon('heroicon-m-tag')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('product_type')
                                ->label('Product Type')
                                ->options([
                                    // ⭐ UPDATED: All supported product types
                                    'running' => '🏃 Running',
                                    'basketball' => '🏀 Basketball',
                                    'tennis' => '🎾 Tennis',
                                    'badminton' => '🏸 Badminton',
                                    'lifestyle_casual' => '🚶 Lifestyle/Casual',
                                    'sneakers' => '👟 Sneakers',
                                    'training' => '💪 Training',
                                    'formal' => '👔 Formal',
                                    'sandals' => '🩴 Sandals',
                                    'boots' => '🥾 Boots',
                                    'apparel' => '👕 Apparel',
                                    'caps' => '🧢 Caps & Hats',
                                    'bags' => '👜 Bags',
                                    'accessories' => '🎒 Accessories',
                                ])
                                ->required()
                                ->helperText('Select the product type for selected products'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['product_type' => $data['product_type']]);
                            }
                            
                            $typeLabel = match($data['product_type']) {
                                'running' => 'Running',
                                'basketball' => 'Basketball',
                                'tennis' => 'Tennis',
                                'badminton' => 'Badminton',
                                'lifestyle_casual' => 'Lifestyle/Casual',
                                'sneakers' => 'Sneakers',
                                'training' => 'Training',
                                'formal' => 'Formal',
                                'sandals' => 'Sandals',
                                'boots' => 'Boots',
                                'apparel' => 'Apparel',
                                'caps' => 'Caps & Hats',
                                'bags' => 'Bags',
                                'accessories' => 'Accessories',
                                default => $data['product_type']
                            };
                            
                            Notification::make()
                                ->title('✅ Product Type Updated')
                                ->body("Product type updated to '{$typeLabel}' for " . count($records) . " products")
                                ->success()
                                ->send();
                        }),

                    // ⭐ NEW: Bulk Fix Gender from Names
                    Tables\Actions\BulkAction::make('fix_gender_from_names')
                        ->label('Auto-Fix Gender from Names')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Auto-Fix Gender from Product Names')
                        ->modalDescription('This will automatically detect and set gender target based on keywords in product names (Pria → Men\'s, Wanita → Women\'s)')
                        ->action(function ($records) {
                            $fixedCount = 0;
                            
                            foreach ($records as $record) {
                                $name = strtolower($record->name);
                                $detectedGender = [];
                                
                                if (str_contains($name, 'pria') || str_contains($name, ' men ') || str_contains($name, 'male')) {
                                    $detectedGender[] = 'mens';
                                }
                                if (str_contains($name, 'wanita') || str_contains($name, 'women') || str_contains($name, 'female')) {
                                    $detectedGender[] = 'womens';
                                }
                                if (str_contains($name, 'anak') || str_contains($name, 'kids') || str_contains($name, 'child')) {
                                    $detectedGender[] = 'kids';
                                }
                                
                                if (!empty($detectedGender)) {
                                    if (count($detectedGender) > 1) {
                                        $detectedGender = ['unisex'];
                                    }
                                    
                                    $record->update(['gender_target' => $detectedGender]);
                                    $fixedCount++;
                                }
                            }
                            
                            Notification::make()
                                ->title('✅ Gender Auto-Fix Complete')
                                ->body("Successfully auto-fixed gender for {$fixedCount} out of " . count($records) . " selected products")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::count();
        return match(true) {
            $count > 100 => 'success',
            $count > 50 => 'warning',
            $count > 0 => 'info',
            default => 'danger'
        };
    }
}