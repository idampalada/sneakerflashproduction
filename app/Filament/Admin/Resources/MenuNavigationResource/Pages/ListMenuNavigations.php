<?php

namespace App\Filament\Admin\Resources\MenuNavigationResource\Pages;

use App\Filament\Admin\Resources\MenuNavigationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMenuNavigations extends ListRecords
{
    protected static string $resource = MenuNavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Custom Menu'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Menus'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('is_active', true);
                })
                ->badge(function () {
                    return \App\Models\MenuNavigation::where('is_active', true)->count();
                }),
            'core' => Tab::make('Core Menus')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereIn('menu_key', ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale']);
                })
                ->badge(6),
            'custom' => Tab::make('Custom Menus')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereNotIn('menu_key', ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale']);
                }),
        ];
    }
}