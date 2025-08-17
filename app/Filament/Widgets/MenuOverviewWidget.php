<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Product;
use App\Models\Category;
use App\Models\MenuNavigation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class MenuOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Products Distribution by Menu';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        try {
            $data = [
                'MENS' => $this->getMensCount(),
                'WOMENS' => $this->getWomensCount(),
                'KIDS' => $this->getKidsCount(),
                'ACCESSORIES' => $this->getAccessoriesCount(),
                'SALE' => $this->getSaleCount(),
                'BRAND' => $this->getBrandCount(),
            ];

            return [
                'datasets' => [
                    [
                        'label' => 'Products Count',
                        'data' => array_values($data),
                        'backgroundColor' => [
                            '#3B82F6', // blue for mens
                            '#EC4899', // pink for womens
                            '#F59E0B', // yellow for kids
                            '#10B981', // green for accessories
                            '#EF4444', // red for sale
                            '#6366F1', // indigo for brand
                        ],
                        'borderColor' => [
                            '#1D4ED8',
                            '#BE185D',
                            '#D97706',
                            '#047857',
                            '#DC2626',
                            '#4338CA',
                        ],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => array_keys($data),
            ];
        } catch (\Exception $e) {
            Log::error('MenuOverviewWidget error: ' . $e->getMessage());
            
            // Return safe fallback data
            return [
                'datasets' => [
                    [
                        'label' => 'Error loading data',
                        'data' => [0],
                        'backgroundColor' => ['#dc2626'],
                        'borderColor' => ['#b91c1c'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => ['Error'],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
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
            Log::error('Chart getMensCount error: ' . $e->getMessage());
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
            Log::error('Chart getWomensCount error: ' . $e->getMessage());
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
            Log::error('Chart getKidsCount error: ' . $e->getMessage());
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
            Log::error('Chart getAccessoriesCount error: ' . $e->getMessage());
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
            Log::error('Chart getSaleCount error: ' . $e->getMessage());
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
            Log::error('Chart getBrandCount error: ' . $e->getMessage());
            return 0;
        }
    }
}