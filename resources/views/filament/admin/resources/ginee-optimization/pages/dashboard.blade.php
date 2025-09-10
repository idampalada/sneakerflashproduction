{{-- 📁 resources/views/filament/admin/resources/ginee-optimization/pages/dashboard.blade.php --}}

<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div class="mb-6">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {{-- Total Records --}}
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Records
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stats['total'] ?? 0) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Success Rate --}}
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Success Rate
                                </dt>
                                <dd class="text-lg font-medium text-green-600">
                                    @php
                                        $total = $stats['total'] ?? 0;
                                        $success = $stats['success'] ?? 0;
                                        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
                                    @endphp
                                    {{ $successRate }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Failed Count --}}
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Failed Syncs
                                </dt>
                                <dd class="text-lg font-medium text-red-600">
                                    {{ number_format($stats['failed'] ?? 0) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Today's Activity --}}
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Today's Syncs
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ number_format($stats['today'] ?? 0) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Cards --}}
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    🚀 Quick Actions
                </h3>
                
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {{-- Test Single SKU --}}
                    <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-md mb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Test Single SKU</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Quick test for one product</p>
                    </div>

                    {{-- Dry Run Sync --}}
                    <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-md mb-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Dry Run Preview</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Preview changes safely</p>
                    </div>

                    {{-- Live Sync --}}
                    <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-md mb-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Live Sync</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Update database directly</p>
                    </div>

                    {{-- View Statistics --}}
                    <div class="text-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-md mb-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Statistics</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">View detailed reports</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Sync Logs --}}
    <div class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    📋 Recent Sync Logs
                </h3>
                <a href="/admin/ginee-sync-logs" class="text-sm text-blue-600 hover:text-blue-500">
                    View all logs →
                </a>
            </div>
            
            @php
                $recentLogs = \App\Models\GineeSyncLog::query()
                    ->whereIn('type', ['individual_sync', 'bulk_optimized_sync', 'bulk_sync'])
                    ->latest('created_at')
                    ->limit(10)
                    ->get();
            @endphp

            @if($recentLogs->count() > 0)
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($recentLogs as $index => $log)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    @if($log->status === 'success')
                                        <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    @else
                                        <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $log->sku }}</span>
                                            @if($log->dry_run)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ml-2">
                                                    Dry Run
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($log->dry_run && $log->status === 'success')
                                                @php
                                                    $change = $log->change;
                                                    if (is_null($change) && !is_null($log->old_stock) && !is_null($log->new_stock)) {
                                                        $change = $log->new_stock - $log->old_stock;
                                                    }
                                                @endphp
                                                @if($change == 0)
                                                    Already in sync ({{ $log->old_stock ?? 0 }})
                                                @else
                                                    Would update: {{ $log->old_stock ?? 0 }} → {{ $log->new_stock ?? 0 }} 
                                                    ({{ $change > 0 ? '+' : '' }}{{ $change }})
                                                @endif
                                            @else
                                                {{ $log->message ?? 'No message' }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time>{{ $log->created_at->diffForHumans() }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 00-2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No sync logs yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by running your first sync operation.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Footer Information --}}
    <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
        <p>
            💡 <strong>Tips:</strong> 
            Always use dry run first to preview changes • 
            Old Stock = Local Database • 
            New Stock = Ginee API • 
            Logs are automatically cleaned up after 30 days
        </p>
        
        <div class="mt-2 flex items-center justify-center space-x-4 text-xs">
            <span class="inline-flex items-center">
                <div class="w-2 h-2 bg-gray-400 rounded-full mr-1"></div>
                Skipped (No Change)
            </span>
            <span class="inline-flex items-center">
                <div class="w-2 h-2 bg-blue-400 rounded-full mr-1"></div>
                Would Update
            </span>
            <span class="inline-flex items-center">
                <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
                Success
            </span>
            <span class="inline-flex items-center">
                <div class="w-2 h-2 bg-red-400 rounded-full mr-1"></div>
                Failed
            </span>
        </div>
    </div>
</x-filament-panels::page>