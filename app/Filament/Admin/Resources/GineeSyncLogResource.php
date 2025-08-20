<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GineeSyncLogResource\Pages;
use App\Filament\Admin\Resources\GineeSyncLogResource\Widgets;
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

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Ginee Sync Logs';
    
    protected static ?string $modelLabel = 'Ginee Sync Log';
    
    protected static ?string $pluralModelLabel = 'Ginee Sync Logs';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->disabled(),
                Forms\Components\TextInput::make('product_name')
                    ->label('Product Name')
                    ->disabled(),
                Forms\Components\Select::make('operation_type')
                    ->label('Operation')
                    ->options([
                        'sync' => '📥 Sync from Ginee',
                        'push' => '📤 Push to Ginee',
                    ])
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'success' => '✅ Success',
                        'failed' => '❌ Failed',
                        'skipped' => '⏭️ Skipped',
                        'completed' => '✅ Completed',
                        'error' => '❌ Error',
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('old_stock')
                    ->label('Old Stock')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('new_stock')
                    ->label('New Stock')
                    ->numeric()
                    ->disabled(),
                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('operation_type')
                    ->label('Operation')
                    ->colors([
                        'info' => 'sync',
                        'warning' => 'push',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sync' => '📥 Sync',
                        'push' => '📤 Push',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['success', 'completed']),
                        'danger' => fn ($state) => in_array($state, ['failed', 'error']),
                        'warning' => 'skipped',
                        'info' => 'started',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success', 'completed' => '✅ Success',
                        'failed', 'error' => '❌ Failed',
                        'skipped' => '⏭️ Skipped',
                        'started' => '🔄 Started',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('old_stock')
                    ->label('Old Stock')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('new_stock')
                    ->label('New Stock')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stock_change')
                    ->label('Change')
                    ->getStateUsing(fn (GineeSyncLog $record): int => 
                        ($record->new_stock ?? 0) - ($record->old_stock ?? 0)
                    )
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger', 
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => 
                        $state > 0 ? "+{$state}" : (string) $state
                    ),
                    
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\TextColumn::make('session_id')
                    ->label('Session')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\ToggleColumn::make('dry_run')
                    ->label('Dry Run')
                    ->disabled()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('operation_type')
                    ->label('Operation')
                    ->options([
                        'sync' => '📥 Sync from Ginee',
                        'push' => '📤 Push to Ginee',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => '✅ Success',
                        'completed' => '✅ Completed',
                        'failed' => '❌ Failed',
                        'error' => '❌ Error',
                        'skipped' => '⏭️ Skipped',
                        'started' => '🔄 Started',
                    ]),
                    
                Tables\Filters\Filter::make('dry_run')
                    ->label('Dry Run Only')
                    ->query(fn (Builder $query): Builder => $query->where('dry_run', true)),
                    
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                    
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
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
    
    public static function getWidgets(): array
    {
        return [
            Widgets\SyncStatsWidget::class,
        ];
    }
}