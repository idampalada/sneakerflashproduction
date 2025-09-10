<?php

namespace App\Filament\Admin\Resources\GineeSyncLogResource\Widgets;

use App\Models\GineeSyncLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SyncStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

protected function getStats(): array
{
    try {
        // Basic counts dengan error handling
        $totalSyncs = GineeSyncLog::count();
        $todaySync = GineeSyncLog::whereDate('created_at', today())->count();
        
        if ($totalSyncs == 0) {
            return [
                Stat::make('Total Syncs', '0')
                    ->description('No sync operations yet')
                    ->color('gray'),
                Stat::make('Today\'s Syncs', '0')
                    ->description('No syncs today')
                    ->color('gray'),
                Stat::make('Success Rate', '0%')
                    ->description('No data available')
                    ->color('gray'),
                Stat::make('This Week', '0')
                    ->description('No syncs this week')
                    ->color('gray'),
            ];
        }

        $totalSuccess = GineeSyncLog::where('status', 'success')->count();
        $successRate = round(($totalSuccess / $totalSyncs) * 100, 1);

        return [
            Stat::make('Total Syncs', number_format($totalSyncs))
                ->description('All time operations')
                ->color('primary'),

            Stat::make('Today\'s Syncs', number_format($todaySync))
                ->description('Operations today')
                ->color('info'),

            Stat::make('Success Rate', $successRate . '%')
                ->description($successRate >= 90 ? 'Excellent' : 'Good')
                ->color($successRate >= 90 ? 'success' : 'warning'),

            Stat::make('This Week', number_format(GineeSyncLog::where('created_at', '>=', now()->startOfWeek())->count()))
                ->description('Weekly operations')
                ->color('secondary'),
        ];

    } catch (\Exception $e) {
        \Log::error('SyncStatsWidget error: ' . $e->getMessage());
        
        return [
            Stat::make('Error', 'Stats unavailable')
                ->description('Check logs')
                ->color('danger'),
        ];
    }
}

    protected function getColumns(): int
    {
        return 4;
    }
}