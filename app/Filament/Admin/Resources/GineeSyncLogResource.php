<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GineeSyncLogResource\Pages;
use App\Filament\Admin\Resources\GineeSyncLogResource\RelationManagers;
use App\Models\GineeSyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GineeSyncLogResource extends Resource
{
    protected static ?string $model = GineeSyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGineeSyncLogs::route('/'),
            'create' => Pages\CreateGineeSyncLog::route('/create'),
            'edit' => Pages\EditGineeSyncLog::route('/{record}/edit'),
        ];
    }
}
