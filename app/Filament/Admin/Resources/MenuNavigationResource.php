<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MenuNavigationResource\Pages;
use App\Models\MenuNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class MenuNavigationResource extends Resource
{
    protected static ?string $model = MenuNavigation::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'Menu Navigation';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Menu Information')
                    ->schema([
                        Forms\Components\TextInput::make('menu_key')
                            ->label('Menu Key')
                            ->required()
                            ->unique(MenuNavigation::class, 'menu_key', ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., mens, womens, sale')
                            ->helperText('Unique identifier for this menu (used in URLs)')
                            ->disabled(function ($record) {
                                if ($record && in_array($record->menu_key, ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale'])) {
                                    return true;
                                }
                                return false;
                            }),

                        Forms\Components\TextInput::make('menu_label')
                            ->label('Menu Label')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., MENS, SALE')
                            ->helperText('Display name in navigation menu'),

                        Forms\Components\TextInput::make('menu_icon')
                            ->label('Menu Icon')
                            ->maxLength(100)
                            ->placeholder('heroicon-o-user, fas fa-male')
                            ->helperText('Icon class or heroicon name (optional)'),

                        Forms\Components\Textarea::make('menu_description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Brief description of this menu section')
                            ->helperText('Internal description for admin reference'),
                    ])->columns(2),

                Forms\Components\Section::make('Menu Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Show this menu in navigation'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first in navigation'),

                        Forms\Components\KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->keyLabel('Setting Key')
                            ->valueLabel('Setting Value')
                            ->helperText('Additional configuration for this menu')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Menu Statistics')
                    ->schema([
                        Forms\Components\Placeholder::make('total_products')
                            ->label('Total Products')
                            ->content(function ($record) {
                                if (!$record) return '0';
                                return (string) static::getProductCount($record->menu_key);
                            }),

                        Forms\Components\Placeholder::make('active_products')
                            ->label('Active Products')
                            ->content(function ($record) {
                                if (!$record) return '0';
                                return (string) static::getActiveProductCount($record->menu_key);
                            }),

                        Forms\Components\Placeholder::make('menu_url')
                            ->label('Menu URL')
                            ->content(function ($record) {
                                if (!$record) return '-';
                                return url("/{$record->menu_key}");
                            }),
                    ])->columns(3)->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('menu_key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Key copied!')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('menu_label')
                    ->label('Label')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('menu_icon')
                    ->label('Icon')
                    ->limit(20)
                    ->placeholder('No icon'),

                Tables\Columns\TextColumn::make('menu_description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->placeholder('No description'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->getStateUsing(function (MenuNavigation $record): int {
                        return static::getProductCount($record->menu_key);
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Active Only')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true);
                    }),

                Filter::make('inactive')
                    ->label('Inactive Only')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', false);
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('view_menu')
                        ->label('View Menu')
                        ->icon('heroicon-o-eye')
                        ->url(function (MenuNavigation $record): string {
                            return url("/{$record->menu_key}");
                        })
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->before(function (MenuNavigation $record) {
                            // Prevent deletion of core menu items
                            $coreMenus = ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale'];
                            if (in_array($record->menu_key, $coreMenus)) {
                                throw new \Exception('Cannot delete core menu items');
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $coreMenus = ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale'];
                            foreach ($records as $record) {
                                if (in_array($record->menu_key, $coreMenus)) {
                                    throw new \Exception('Cannot delete core menu items');
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuNavigations::route('/'),
            'create' => Pages\CreateMenuNavigation::route('/create'),
            'edit' => Pages\EditMenuNavigation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            // Check if table exists before querying
            if (!Schema::hasTable('menu_navigation')) {
                return '0';
            }
            return static::getModel()::where('is_active', true)->count();
        } catch (\Exception $e) {
            return '0';
        }
    }

    // ========================================
    // HELPER METHODS - Safe with Missing Tables/Columns
    // ========================================

    /**
     * Get product count for specific menu
     */
    protected static function getProductCount(string $menuKey): int
    {
        try {
            // Check if products table and required columns exist
            if (!Schema::hasTable('products')) {
                return 0;
            }

            $hasGenderTarget = Schema::hasColumn('products', 'gender_target');
            $hasProductType = Schema::hasColumn('products', 'product_type');

            return match($menuKey) {
                'mens' => \App\Models\Product::where('is_active', true)->where(function ($q) use ($hasGenderTarget) {
                    if ($hasGenderTarget) {
                        $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
                    } else {
                        $q->where('name', 'ilike', '%mens%')->orWhere('name', 'ilike', '%man%');
                    }
                })->count(),
                'womens' => \App\Models\Product::where('is_active', true)->where(function ($q) use ($hasGenderTarget) {
                    if ($hasGenderTarget) {
                        $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
                    } else {
                        $q->where('name', 'ilike', '%womens%')->orWhere('name', 'ilike', '%women%');
                    }
                })->count(),
                'kids' => \App\Models\Product::where('is_active', true)->where(function ($q) use ($hasGenderTarget) {
                    if ($hasGenderTarget) {
                        $q->where('gender_target', 'kids');
                    } else {
                        $q->where('name', 'ilike', '%kids%')->orWhere('name', 'ilike', '%children%');
                    }
                })->count(),
                'brand' => \App\Models\Product::where('is_active', true)->whereNotNull('brand')->count(),
                'accessories' => \App\Models\Product::where('is_active', true)->where(function ($q) use ($hasProductType) {
                    if ($hasProductType) {
                        $q->whereIn('product_type', ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories']);
                    } else {
                        $q->where('name', 'ilike', '%accessories%')->orWhere('name', 'ilike', '%bag%');
                    }
                })->count(),
                'sale' => \App\Models\Product::where('is_active', true)->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
                default => 0,
            };
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active product count for specific menu
     */
    protected static function getActiveProductCount(string $menuKey): int
    {
        try {
            return static::getProductCount($menuKey);
        } catch (\Exception $e) {
            return 0;
        }
    }

    // Check if the resource should be shown in navigation
    public static function shouldRegisterNavigation(): bool
    {
        try {
            return Schema::hasTable('menu_navigation');
        } catch (\Exception $e) {
            return false;
        }
    }
}