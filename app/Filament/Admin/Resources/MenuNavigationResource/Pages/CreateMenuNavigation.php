<?php

namespace App\Filament\Admin\Resources\MenuNavigationResource\Pages;

use App\Filament\Admin\Resources\MenuNavigationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuNavigation extends CreateRecord
{
    protected static string $resource = MenuNavigationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default sort order if not provided
        if (empty($data['sort_order'])) {
            $maxOrder = \App\Models\MenuNavigation::max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        }

        return $data;
    }
}