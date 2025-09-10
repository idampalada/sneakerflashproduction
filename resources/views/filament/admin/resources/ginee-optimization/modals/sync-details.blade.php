{{-- 📁 resources/views/filament/admin/resources/ginee-optimization/modals/sync-details.blade.php --}}

<div class="space-y-6">
    {{-- Header Info --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                📦 {{ $record->sku }}
            </h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($record->dry_run) bg-gray-100 text-gray-800 @else bg-blue-100 text-blue-800 @endif">
                {{ $record->dry_run ? '🧪 Dry Run' : '🔄 Live Sync' }}
            </span>
        </div>
        
        @if($record->product_name)
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ $record->product_name }}
        </p>
        @endif
    </div>

    {{-- Status & Results --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Status Card --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">📊 Sync Status</h4>
            
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        @if($record->status === 'success') bg-green-100 text-green-800
                        @elseif($record->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif">
                        @if($record->dry_run && $record->status === 'success')
                            @if($record->change == 0)
                                ⏩ Skipped
                            @else
                                ✅ Would Update
                            @endif
                        @else
                            {{ ucfirst($record->status) }}
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Operation:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ ucfirst($record->operation_type ?? 'sync') }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Session ID:</span>
                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                        {{ $record->session_id }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sync Time:</span>
                    <span class="text-sm text-gray-900 dark:text-white">
                        {{ $record->created_at->format('M d, Y H:i:s') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Stock Changes Card --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">📈 Stock Changes</h4>
            
            <div class="space-y-3">
                {{-- Old Stock --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Old Stock (Local):</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        {{ $record->old_stock ?? '0' }}
                    </span>
                </div>
                
                {{-- New Stock --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">New Stock (Ginee):</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $record->new_stock ?? '0' }}
                    </span>
                </div>
                
                {{-- Change --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Change:</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium
                        @if($record->change > 0) bg-green-100 text-green-800
                        @elseif($record->change < 0) bg-red-100 text-red-800  
                        @else bg-gray-100 text-gray-800 @endif">
                        @if($record->change == 0)
                            0
                        @else
                            {{ $record->change > 0 ? '+' : '' }}{{ $record->change }}
                        @endif
                    </span>
                </div>
                
                {{-- Visual Change Indicator --}}
                @if($record->old_stock !== null && $record->new_stock !== null)
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">{{ $record->old_stock }}</span>
                        </div>
                        
                        <div class="flex-1 flex items-center justify-center">
                            @if($record->change != 0)
                                <svg class="w-4 h-4 {{ $record->change > 0 ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                    @if($record->change > 0)
                                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                            @else
                                <span class="text-gray-400">→</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $record->new_stock }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Message & Error Details --}}
    @if($record->message || $record->error_message)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">💬 Details</h4>
        
        @if($record->message)
        <div class="mb-3">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Message:</span>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                {{ $record->message }}
            </p>
        </div>
        @endif
        
        @if($record->error_message)
        <div>
            <span class="text-sm font-medium text-red-700 dark:text-red-400">Error:</span>
            <p class="text-sm text-red-600 dark:text-red-400 mt-1 p-2 bg-red-50 dark:bg-red-900/20 rounded">
                {{ $record->error_message }}
            </p>
        </div>
        @endif
    </div>
    @endif

    {{-- Metadata (if exists) --}}
    @if($record->metadata && is_array($record->metadata))
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">🔧 Technical Details</h4>
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
            <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ json_encode($record->metadata, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @endif

    {{-- Action Buttons --}}
    @if($record->status === 'failed' || ($record->dry_run && $record->change != 0))
    <div class="flex space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
        @if($record->status === 'failed')
        <button type="button" onclick="$wire.call('retrySingleSku', '{{ $record->sku }}')" 
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Retry Sync
        </button>
        @endif
        
        @if($record->dry_run && $record->change != 0)
        <button type="button" onclick="$wire.call('runLiveSync', '{{ $record->sku }}')"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Run Live Sync
        </button>
        @endif
    </div>
    @endif
</div>