<?php

namespace App\Filament\Admin\Resources\GineeSyncLogResource\Widgets;

use App\Models\GineeSyncLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SyncStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = today();
        $thisWeek = [now()->startOfWeek(), now()->endOfWeek()];

        // Today's stats
        $todaySync = GineeSyncLog::whereDate('created_at', $today)->sync()->count();
        $todayPush = GineeSyncLog::whereDate('created_at', $today)->where('operation_type', 'push')->count();
        $todaySuccess = GineeSyncLog::whereDate('created_at', $today)->success()->count();
        $todayFailed = GineeSyncLog::whereDate('created_at', $today)->failed()->count();

        // This week's stats
        $weekSync = GineeSyncLog::whereBetween('created_at', $thisWeek)->sync()->count();
        $weekPush = GineeSyncLog::whereBetween('created_at', $thisWeek)->where('operation_type', 'push')->count();

        // Success rate
        $totalToday = $todaySuccess + $todayFailed;
        $successRate = $totalToday > 0 ? round(($todaySuccess / $totalToday) * 100, 1) : 0;

        return [
            Stat::make('Today Sync Operations', $todaySync)
                ->description('📥 Sync from Ginee today')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('info'),

            Stat::make('Today Push Operations', $todayPush)
                ->description('📤 Push to Ginee today')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('warning'),

            Stat::make('Today Success Rate', $successRate . '%')
                ->description("{$todaySuccess} success, {$todayFailed} failed")
                ->descriptionIcon($successRate >= 90 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),

            Stat::make('This Week Total', $weekSync + $weekPush)
                ->description("{$weekSync} sync, {$weekPush} push")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }
}