<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\MenuNavigation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class MenuStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            return [
                Stat::make('MENS Products', $this->getMensCount())
                    ->description('Active mens products')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('blue'),

                Stat::make('WOMENS Products', $this->getWomensCount())
                    ->description('Active womens products')
                    ->descriptionIcon('heroicon-m-user')
                    ->color('pink'),

                Stat::make('KIDS Products', $this->getKidsCount())
                    ->description('Active kids products')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('yellow'),

                Stat::make('ACCESSORIES', $this->getAccessoriesCount())
                    ->description('Active accessories')
                    ->descriptionIcon('heroicon-m-shopping-bag')
                    ->color('green'),

                Stat::make('SALE Products', $this->getSaleCount())
                    ->description('Products on sale')
                    ->descriptionIcon('heroicon-m-tag')
                    ->color('red'),

                Stat::make('BRAND Products', $this->getBrandCount())
                    ->description('Products with brands')
                    ->descriptionIcon('heroicon-m-building-storefront')
                    ->color('indigo'),
            ];
        } catch (\Exception $e) {
            Log::error('MenuStatsWidget error: ' . $e->getMessage());
            
            // Return safe fallback stats
            return [
                Stat::make('Error', 'N/A')
                    ->description('Error loading stats')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('gray'),
            ];
        }
    }

    // FIXED: PostgreSQL compatible helper methods
    private function getMensCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->where(function ($q) {
                    // PostgreSQL compatible JSON search with multiple fallbacks
                    try {
                        $q->whereJsonContains('gender_target', 'mens');
                    } catch (\Exception $e) {}
                    
                    try {
                        $q->orWhereRaw("gender_target ? ?", ['mens']);
                    } catch (\Exception $e) {}
                    
                    $q->orWhereRaw("gender_target::text LIKE ?", ['%"mens"%']);
                    $q->orWhere('gender_target', 'LIKE', '%mens%');
                    
                    // Also include unisex products
                    try {
                        $q->orWhereJsonContains('gender_target', 'unisex');
                    } catch (\Exception $e) {}
                    
                    try {
                        $q->orWhereRaw("gender_target ? ?", ['unisex']);
                    } catch (\Exception $e) {}
                    
                    $q->orWhereRaw("gender_target::text LIKE ?", ['%"unisex"%']);
                    $q->orWhere('gender_target', 'LIKE', '%unisex%');
                })
                ->count();
        } catch (\Exception $e) {
            Log::error('getMensCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getWomensCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->where(function ($q) {
                    // PostgreSQL compatible JSON search with multiple fallbacks
                    try {
                        $q->whereJsonContains('gender_target', 'womens');
                    } catch (\Exception $e) {}
                    
                    try {
                        $q->orWhereRaw("gender_target ? ?", ['womens']);
                    } catch (\Exception $e) {}
                    
                    $q->orWhereRaw("gender_target::text LIKE ?", ['%"womens"%']);
                    $q->orWhere('gender_target', 'LIKE', '%womens%');
                    
                    // Also include unisex products
                    try {
                        $q->orWhereJsonContains('gender_target', 'unisex');
                    } catch (\Exception $e) {}
                    
                    try {
                        $q->orWhereRaw("gender_target ? ?", ['unisex']);
                    } catch (\Exception $e) {}
                    
                    $q->orWhereRaw("gender_target::text LIKE ?", ['%"unisex"%']);
                    $q->orWhere('gender_target', 'LIKE', '%unisex%');
                })
                ->count();
        } catch (\Exception $e) {
            Log::error('getWomensCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getKidsCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->where(function ($q) {
                    // PostgreSQL compatible JSON search with multiple fallbacks
                    try {
                        $q->whereJsonContains('gender_target', 'kids');
                    } catch (\Exception $e) {}
                    
                    try {
                        $q->orWhereRaw("gender_target ? ?", ['kids']);
                    } catch (\Exception $e) {}
                    
                    $q->orWhereRaw("gender_target::text LIKE ?", ['%"kids"%']);
                    $q->orWhere('gender_target', 'LIKE', '%kids%');
                })
                ->count();
        } catch (\Exception $e) {
            Log::error('getKidsCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getAccessoriesCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('product_type', [
                        'backpack', 'bag', 'hat', 'cap', 'socks', 'laces', 'care_products', 'accessories'
                    ])
                    // Fallback for products without product_type but with accessory names
                    ->orWhere(function ($subQ) {
                        $subQ->where('name', 'ILIKE', '%bag%')
                             ->orWhere('name', 'ILIKE', '%backpack%')
                             ->orWhere('name', 'ILIKE', '%hat%')
                             ->orWhere('name', 'ILIKE', '%cap%')
                             ->orWhere('name', 'ILIKE', '%sock%')
                             ->orWhere('name', 'ILIKE', '%accessories%')
                             ->orWhere('name', 'ILIKE', '%cleaner%');
                    });
                })
                ->count();
        } catch (\Exception $e) {
            Log::error('getAccessoriesCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getSaleCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->whereNotNull('sale_price')
                ->whereRaw('sale_price < price')
                ->count();
        } catch (\Exception $e) {
            Log::error('getSaleCount error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getBrandCount(): int
    {
        try {
            return Product::where('is_active', true)
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->count();
        } catch (\Exception $e) {
            Log::error('getBrandCount error: ' . $e->getMessage());
            return 0;
        }
    }
}