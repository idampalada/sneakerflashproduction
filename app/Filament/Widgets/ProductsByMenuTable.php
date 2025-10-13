<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\MenuNavigation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProductsByMenuTable extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Menu Performance Overview';

    protected function getTableQuery(): Builder
    {
        // Create a custom query for menu statistics
        return MenuNavigation::query()->where('is_active', true)->orderBy('sort_order');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('menu_label')
                ->label('Menu')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('total_products')
                ->label('Total Products')
                ->getStateUsing(function (MenuNavigation $record): int {
                    return $this->getProductCount($record->menu_key);
                })
                ->badge()
                ->color('info'),

            Tables\Columns\TextColumn::make('active_products')
                ->label('Active Products')
                ->getStateUsing(function (MenuNavigation $record): int {
                    return $this->getActiveProductCount($record->menu_key);
                })
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('sale_products')
                ->label('On Sale')
                ->getStateUsing(function (MenuNavigation $record): int {
                    return $this->getSaleProductCount($record->menu_key);
                })
                ->badge()
                ->color('warning'),

            Tables\Columns\TextColumn::make('low_stock')
                ->label('Low Stock')
                ->getStateUsing(function (MenuNavigation $record): int {
                    return $this->getLowStockCount($record->menu_key);
                })
                ->badge()
                ->color('danger'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view_products')
                ->label('Manage')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(function (MenuNavigation $record): string {
                    $filters = match($record->menu_key) {
                        'mens' => '?tableFilters[gender_target][value]=mens',
                        'womens' => '?tableFilters[gender_target][value]=womens',
                        'kids' => '?tableFilters[gender_target][value]=kids',
                        'accessories' => '?tableFilters[product_type][value]=accessories',
                        'sale' => '?tableFilters[on_sale][isActive]=true',
                        default => '',
                    };
                    return '/admin/products' . $filters;
                })
                ->openUrlInNewTab(),
        ];
    }

    // Helper methods
    private function getProductCount(string $menuKey): int
    {
        return match($menuKey) {
            'mens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
            })->count(),
            'womens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
            })->count(),
            'kids' => Product::active()->where('gender_target', 'kids')->count(),
            'brand' => Product::active()->whereNotNull('brand')->count(),
            'accessories' => Product::active()->whereIn('product_type', ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'])->count(),
            'sale' => Product::active()->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            default => 0,
        };
    }

    private function getActiveProductCount(string $menuKey): int
    {
        return match($menuKey) {
            'mens' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->where(function ($q) {
                $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
            })->count(),
            'womens' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->where(function ($q) {
                $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
            })->count(),
            'kids' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->where('gender_target', 'kids')->count(),
            'brand' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->whereNotNull('brand')->count(),
            'accessories' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->whereIn('product_type', ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'])->count(),
            'sale' => Product::where('is_active', true)->where('stock_quantity', '>', 0)->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            default => 0,
        };
    }

    private function getSaleProductCount(string $menuKey): int
    {
        return match($menuKey) {
            'mens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
            })->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            'womens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
            })->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            'kids' => Product::active()->where('gender_target', 'kids')->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            'brand' => Product::active()->whereNotNull('brand')->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            'accessories' => Product::active()->whereIn('product_type', ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'])->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            'sale' => Product::active()->whereNotNull('sale_price')->whereRaw('sale_price < price')->count(),
            default => 0,
        };
    }

    private function getLowStockCount(string $menuKey): int
    {
        return match($menuKey) {
            'mens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'mens')->orWhere('gender_target', 'unisex');
            })->where('stock_quantity', '<=', 5)->count(),
            'womens' => Product::active()->where(function ($q) {
                $q->where('gender_target', 'womens')->orWhere('gender_target', 'unisex');
            })->where('stock_quantity', '<=', 5)->count(),
            'kids' => Product::active()->where('gender_target', 'kids')->where('stock_quantity', '<=', 5)->count(),
            'brand' => Product::active()->whereNotNull('brand')->where('stock_quantity', '<=', 5)->count(),
            'accessories' => Product::active()->whereIn('product_type', ['backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'])->where('stock_quantity', '<=', 5)->count(),
            'sale' => Product::active()->whereNotNull('sale_price')->whereRaw('sale_price < price')->where('stock_quantity', '<=', 5)->count(),
            default => 0,
        };
    }
}