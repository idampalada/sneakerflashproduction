{{-- 📁 resources/views/filament/admin/resources/ginee-optimization/modals/statistics.blade.php --}}

@php
    // Get statistics data
    $totalLogs = \App\Models\GineeSyncLog::count();
    $recentLogs = \App\Models\GineeSyncLog::where('created_at', '>=', now()->subDays(7))->get();
    
    // Calculate stats
    $stats = [
        'total' => $totalLogs,
        'recent_total' => $recentLogs->count(),
        'success_rate' => $recentLogs->count() > 0 ? round(($recentLogs->where('status', 'success')->count() / $recentLogs->count()) * 100, 1) : 0,
        'dry_run_percentage' => $recentLogs->count() > 0 ? round(($recentLogs->where('dry_run', true)->count() / $recentLogs->count()) * 100, 1) : 0,
        'unique_skus' => $recentLogs->pluck('sku')->unique()->count(),
        'avg_change' => $recentLogs->where('change', '!=', 0)->avg('change') ?? 0,
    ];
    
    // Daily breakdown for last 7 days
    $dailyStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dayLogs = $recentLogs->where('created_at', '>=', $date->startOfDay())->where('created_at', '<=', $date->endOfDay());
        
        $dailyStats[] = [
            'date' => $date->format('M d'),
            'total' => $dayLogs->count(),
            'success' => $dayLogs->where('status', 'success')->count(),
            'failed' => $dayLogs->where('status', 'failed')->count(),
            'dry_run' => $dayLogs->where('dry_run', true)->count(),
        ];
    }
    
    // Top failing SKUs
    $failingSkus = \App\Models\GineeSyncLog::where('status', 'failed')
        ->where('created_at', '>=', now()->subDays(30))
        ->selectRaw('sku, product_name, COUNT(*) as failure_count, MAX(created_at) as last_failure')
        ->groupBy('sku', 'product_name')
        ->orderBy('failure_count', 'desc')
        ->limit(10)
        ->get();
    
    // Largest stock changes
    $largestChanges = \App\Models\GineeSyncLog::where('status', 'success')
        ->where('change', '!=', 0)
        ->where('dry_run', false)
        ->where('created_at', '>=', now()->subDays(7))
        ->orderByRaw('ABS(change) DESC')
        ->limit(10)
        ->get();
@endphp

<div class="space-y-6">
    {{-- Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Syncs --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 rounded-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Syncs (All Time)</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="text-blue-200">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Success Rate --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 rounded-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Success Rate (7 Days)</p>
                    <p class="text-2xl font-bold">{{ $stats['success_rate'] }}%</p>
                </div>
                <div class="text-green-200">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Unique SKUs --}}
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4 rounded-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Unique SKUs (7 Days)</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['unique_skus']) }}</p>
                </div>
                <div class="text-purple-200">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Avg Stock Change --}}
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 rounded-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Avg Stock Change</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['avg_change'], 1) }}</p>
                </div>
                <div class="text-orange-200">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Activity Chart --}}
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">📊 Daily Activity (Last 7 Days)</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Success</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Failed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dry Run</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Success Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($dailyStats as $day)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $day['date'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $day['total'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $day['success'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($day['failed'] > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $day['failed'] }}
                            </span>
                            @else
                            <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $day['dry_run'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            @if($day['total'] > 0)
                                {{ round(($day['success'] / $day['total']) * 100, 1) }}%
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Failing SKUs --}}
        <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">❌ Most Failing SKUs (30 Days)</h3>
            
            @if($failingSkus->count() > 0)
            <div class="space-y-3">
                @foreach($failingSkus as $sku)
                <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $sku->sku }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $sku->product_name ?? 'No name' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            Last: {{ $sku->last_failure ? \Carbon\Carbon::parse($sku->last_failure)->diffForHumans() : 'Unknown' }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ $sku->failure_count }} fails
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Recent Failures</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All syncs have been successful in the last 30 days!</p>
            </div>
            @endif
        </div>

        {{-- Largest Stock Changes --}}
        <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">📈 Largest Stock Changes (7 Days)</h3>
            
            @if($largestChanges->count() > 0)
            <div class="space-y-3">
                @foreach($largestChanges as $change)
                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $change->sku }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $change->product_name ?? 'No name' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $change->old_stock }} → {{ $change->new_stock }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $change->change > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $change->change > 0 ? '+' : '' }}{{ $change->change }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Stock Changes</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No significant stock changes in the last 7 days.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Performance Metrics --}}
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">⚡ Performance Metrics</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Dry Run vs Live Ratio --}}
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $stats['dry_run_percentage'] }}%
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Dry Run Usage</div>
                <div class="text-xs text-gray-400 mt-1">
                    Higher is safer
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $stats['recent_total'] }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Syncs This Week</div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ round($stats['recent_total'] / 7, 1) }}/day average
                </div>
            </div>

            {{-- Error Rate --}}
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold {{ $stats['success_rate'] >= 95 ? 'text-green-600' : ($stats['success_rate'] >= 85 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ 100 - $stats['success_rate'] }}%
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Error Rate</div>
                <div class="text-xs text-gray-400 mt-1">
                    @if($stats['success_rate'] >= 95)
                        Excellent
                    @elseif($stats['success_rate'] >= 85)
                        Good
                    @else
                        Needs attention
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recommendations --}}
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 p-6 rounded-lg border border-indigo-200 dark:border-indigo-700">
        <h3 class="text-lg font-medium text-indigo-900 dark:text-indigo-100 mb-4">💡 Recommendations</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="font-medium text-indigo-800 dark:text-indigo-200 mb-2">✅ Good Practices:</h4>
                <ul class="text-sm text-indigo-700 dark:text-indigo-300 space-y-1">
                    @if($stats['dry_run_percentage'] >= 70)
                    <li>• High dry run usage - Great for safety!</li>
                    @endif
                    @if($stats['success_rate'] >= 95)
                    <li>• Excellent success rate</li>
                    @endif
                    @if($stats['recent_total'] > 0)
                    <li>• Regular sync activity maintained</li>
                    @endif
                </ul>
            </div>
            
            <div>
                <h4 class="font-medium text-indigo-800 dark:text-indigo-200 mb-2">⚠️ Suggestions:</h4>
                <ul class="text-sm text-indigo-700 dark:text-indigo-300 space-y-1">
                    @if($stats['success_rate'] < 85)
                    <li>• Investigate frequent sync failures</li>
                    @endif
                    @if($stats['dry_run_percentage'] < 50)
                    <li>• Consider using dry run more often</li>
                    @endif
                    @if($failingSkus->count() > 5)
                    <li>• Review top failing SKUs</li>
                    @endif
                    @if($stats['recent_total'] < 7)
                    <li>• Consider more frequent syncing</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>