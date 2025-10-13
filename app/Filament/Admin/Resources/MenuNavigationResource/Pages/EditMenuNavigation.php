<?php

namespace App\Filament\Admin\Resources\MenuNavigationResource\Pages;

use App\Filament\Admin\Resources\MenuNavigationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuNavigation extends EditRecord
{
    protected static string $resource = MenuNavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            
            Actions\Action::make('view_menu')
                ->label('View Menu')
                ->icon('heroicon-o-eye')
                ->url(function () {
                    return url("/{$this->record->menu_key}");
                })
                ->openUrlInNewTab(),

            Actions\DeleteAction::make()
                ->before(function () {
                    // Prevent deletion of core menu items
                    $coreMenus = ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale'];
                    if (in_array($this->record->menu_key, $coreMenus)) {
                        throw new \Exception('Cannot delete core menu items');
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent changing menu_key for core menus
        $coreMenus = ['mens', 'womens', 'kids', 'brand', 'accessories', 'sale'];
        if (in_array($this->record->menu_key, $coreMenus)) {
            $data['menu_key'] = $this->record->menu_key; // Keep original
        }

        return $data;
    }
}