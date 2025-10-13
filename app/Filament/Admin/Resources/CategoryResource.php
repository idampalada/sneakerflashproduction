<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Shop';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $schema = [
            // Basic Information Section
            Forms\Components\Section::make('Category Information')
                ->description('Basic category details for your sneaker store')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Category Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $context, $state, callable $set) {
                            if ($context === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->placeholder('e.g., Basketball Shoes, Running, Lifestyle/Casual')
                        ->helperText('This is how the category appears in navigation and filters'),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Category::class, 'slug', ignoreRecord: true)
                        ->helperText('Auto-generated URL-friendly version. Creates: /categories/your-slug'),

                    Forms\Components\Textarea::make('description')
                        ->label('Category Description')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe what products belong in this category...')
                        ->helperText('Description for SEO and category pages'),

                    Forms\Components\FileUpload::make('image')
                        ->label('Category Image')
                        ->image()
                        ->imageEditor()
                        ->directory('categories')
                        ->visibility('public')
                        ->imagePreviewHeight('200')
                        ->helperText('Optional category banner or icon'),
                ])->columns(2),

            // Display Settings Section
            Forms\Components\Section::make('Display Settings')
                ->description('Control how this category appears on your website')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active Category')
                        ->default(true)
                        ->helperText('Active categories are visible to customers'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first (0 = first position)'),
                ])->columns(2),
        ];

        // Add optional fields if columns exist
        if (Schema::hasColumn('categories', 'show_in_menu')) {
            $schema[1]['schema'][] = Forms\Components\Toggle::make('show_in_menu')
                ->label('Show in Navigation Menu')
                ->default(true)
                ->helperText('Display in main website navigation');
        }

        if (Schema::hasColumn('categories', 'is_featured')) {
            $schema[1]['schema'][] = Forms\Components\Toggle::make('is_featured')
                ->label('Featured Category')
                ->default(false)
                ->helperText('Featured categories appear on homepage');
        }

        if (Schema::hasColumn('categories', 'menu_placement')) {
            $schema[1]['schema'][] = Forms\Components\Select::make('menu_placement')
                ->label('Menu Section')
                ->options([
                    'general' => '🌟 General (All Menus)',
                    'mens' => '👨 MENS Section',
                    'womens' => '👩 WOMENS Section',
                    'kids' => '👶 KIDS Section',
                    'accessories' => '🎒 ACCESSORIES Section',
                ])
                ->default('general')
                ->helperText('Which main menu section should show this category');
        }

        // Category Analytics Section (for edit mode)
        $schema[] = Forms\Components\Section::make('Category Statistics')
            ->description('Product counts and category performance')
            ->schema([
                Forms\Components\Placeholder::make('products_count')
                    ->label('Total Products')
                    ->content(function (?Category $record): string {
                        if (!$record) return '0';
                        return (string) $record->products()->count();
                    }),

                Forms\Components\Placeholder::make('active_products_count')
                    ->label('Active Products')
                    ->content(function (?Category $record): string {
                        if (!$record) return '0';
                        return (string) $record->products()->where('is_active', true)->count();
                    }),

                Forms\Components\Placeholder::make('featured_products_count')
                    ->label('Featured Products')
                    ->content(function (?Category $record): string {
                        if (!$record) return '0';
                        return (string) $record->products()->where('is_featured', true)->count();
                    }),

                Forms\Components\Placeholder::make('sale_products_count')
                    ->label('Products on Sale')
                    ->content(function (?Category $record): string {
                        if (!$record) return '0';
                        return (string) $record->products()->whereNotNull('sale_price')->count();
                    }),
            ])->columns(4)->hiddenOn('create');

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->size(50)
                    ->circular()
                    ->defaultImageUrl(url('/images/default-category.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('semibold')
                    ->description(fn (Category $record): ?string => $record->description),

                Tables\Columns\TextColumn::make('slug')
                    ->label('URL Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->prefix('/categories/'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->getStateUsing(function (Category $record): int {
                        return $record->products()->count();
                    })
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('active_products_count')
                    ->label('Active')
                    ->getStateUsing(function (Category $record): int {
                        return $record->products()->where('is_active', true)->count();
                    })
                    ->alignEnd()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Active Categories')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder => $query->has('products')),

                Filter::make('no_products')
                    ->label('Empty Categories')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('products')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this category? This will also affect products in this category.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Toggle Active')
                        ->icon('heroicon-m-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => !$record->is_active]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->emptyStateHeading('No categories yet')
            ->emptyStateDescription('Create your first category to start organizing products.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::count();
        return $count > 5 ? 'success' : ($count > 0 ? 'warning' : 'danger');
    }
}