<?php

// app/Filament/Resources/GineeSyncLogResource/Pages/ListGineeSyncLogs.php

namespace App\Filament\Admin\Resources\GineeSyncLogResource\Pages;

use App\Filament\Admin\Resources\GineeSyncLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGineeSyncLogs extends ListRecords
{
    protected static string $resource = GineeSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup_old_logs')
                ->label('🗑️ Cleanup Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cleanup Old Sync Logs')
                ->modalDescription('This will delete sync logs older than 30 days. This action cannot be undone.')
                ->action(function () {
                    $deleted = \App\Models\GineeSyncLog::where('created_at', '<', now()->subDays(30))->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Cleanup Completed')
                        ->body("Deleted {$deleted} old log entries")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('export_logs')
                ->label('📊 Export Logs')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $logs = \App\Models\GineeSyncLog::recent()->limit(1000)->get();
                    
                    return response()->streamDownload(function () use ($logs) {
                        echo "Date,Operation,SKU,Product Name,Status,Old Stock,New Stock,Change,Message,Transaction ID\n";
                        foreach ($logs as $log) {
                            $change = $log->new_stock - $log->old_stock;
                            echo "\"{$log->created_at}\",\"{$log->operation_type}\",\"{$log->sku}\",\"{$log->product_name}\",\"{$log->status}\",\"{$log->old_stock}\",\"{$log->new_stock}\",\"{$change}\",\"{$log->message}\",\"{$log->transaction_id}\"\n";
                        }
                    }, 'ginee_sync_logs_' . now()->format('Y-m-d_H-i-s') . '.csv');
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GineeSyncLogResource\Widgets\SyncStatsWidget::class,
        ];
    }
}