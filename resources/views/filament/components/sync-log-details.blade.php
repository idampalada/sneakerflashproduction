<div class="space-y-6">
    {{-- Header Info --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">📊 Sync Information</h3>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Sync ID:</dt>
                        <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $record->sync_id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Status:</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $record->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $record->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                {{ $record->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $record->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}">
                                {{ $record->status_badge }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Started:</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $record->started_at->format('d M Y, H:i:s') }}</dd>
                    </div>
                    @if($record->completed_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Completed:</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $record->completed_at->format('d M Y, H:i:s') }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Duration:</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $record->duration_formatted }}</dd>
                    </div>
                </dl>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">🎯 Processing Statistics</h3>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Total Rows:</dt>
                        <dd class="text-gray-900 dark:text-gray-100 font-semibold">{{ number_format($record->total_rows) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Processed:</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ number_format($record->processed_rows) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-green-600 dark:text-green-400">Created Products:</dt>
                        <dd class="text-green-700 dark:text-green-300 font-semibold">{{ number_format($record->created_products) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-blue-600 dark:text-blue-400">Updated Products:</dt>
                        <dd class="text-blue-700 dark:text-blue-300 font-semibold">{{ number_format($record->updated_products) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-yellow-600 dark:text-yellow-400">Skipped Rows:</dt>
                        <dd class="text-yellow-700 dark:text-yellow-300">{{ number_format($record->skipped_rows) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-red-600 dark:text-red-400">Errors:</dt>
                        <dd class="text-red-700 dark:text-red-300 font-semibold">{{ number_format($record->error_count) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Success Rate Progress Bar --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">📈 Success Rate</h3>
            <span class="text-sm font-semibold {{ $record->success_rate >= 90 ? 'text-green-600 dark:text-green-400' : ($record->success_rate >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                {{ $record->success_rate }}%
            </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            @php
                $successRate = min(100, max(0, $record->success_rate ?? 0));
                $colorClass = $successRate >= 90 ? 'bg-green-500' : ($successRate >= 70 ? 'bg-yellow-500' : 'bg-red-500');
            @endphp
            <div class="h-2 rounded-full transition-all duration-300 {{ $colorClass }}" 
                 data-width="{{ $successRate }}"
                 x-data
                 x-init="$el.style.width = $el.dataset.width + '%'">
            </div>
        </div>
    </div>

    {{-- Google Sheets Info --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">📊 Google Sheets Source</h3>
        <dl class="space-y-1 text-sm">
            <div class="flex justify-between">
                <dt class="text-blue-700 dark:text-blue-300">Spreadsheet ID:</dt>
                <dd class="text-blue-800 dark:text-blue-200 font-mono">{{ $record->spreadsheet_id }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-blue-700 dark:text-blue-300">Sheet Name:</dt>
                <dd class="text-blue-800 dark:text-blue-200">{{ $record->sheet_name }}</dd>
            </div>
            <div class="mt-2">
                <a href="https://docs.google.com/spreadsheets/d/{{ $record->spreadsheet_id }}/edit" 
                   target="_blank"
                   class="inline-flex items-center px-3 py-1 border border-blue-300 dark:border-blue-600 shadow-sm text-xs leading-4 font-medium rounded-md text-blue-700 dark:text-blue-200 bg-white dark:bg-blue-900 hover:bg-blue-50 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                    </svg>
                    Open Spreadsheet
                </a>
            </div>
        </dl>
    </div>

    {{-- Summary --}}
    @if($record->summary)
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">📝 Summary</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->summary }}</p>
    </div>
    @endif

    {{-- Error Message (if failed) --}}
    @if($record->error_message)
    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
        <h3 class="text-sm font-medium text-red-900 dark:text-red-100 mb-2">❌ Error Message</h3>
        <p class="text-sm text-red-700 dark:text-red-300 font-mono">{{ $record->error_message }}</p>
    </div>
    @endif

    {{-- Sync Options --}}
    @if($record->sync_options && !empty($record->sync_options))
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">⚙️ Sync Options</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @foreach($record->sync_options as $key => $value)
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                <dd class="text-gray-900 dark:text-gray-100">
                    @if(is_bool($value))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                            {{ $value ? 'Yes' : 'No' }}
                        </span>
                    @else
                        {{ is_array($value) ? implode(', ', $value) : $value }}
                    @endif
                </dd>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Initiated By --}}
    @if($record->initiated_by)
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">👤 Initiated By</h3>
        @php
            $user = \App\Models\User::find($record->initiated_by);
        @endphp
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">
                        {{ $user ? strtoupper(substr($user->name, 0, 1)) : 'S' }}
                    </span>
                </div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $user ? $user->name : 'System' }}
                </p>
                @if($user && $user->email)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Performance Metrics --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">⚡ Performance Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Processing Speed --}}
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    @if($record->duration_seconds && $record->total_rows > 0)
                        {{ round($record->total_rows / max($record->duration_seconds, 1), 1) }}
                    @else
                        -
                    @endif
                </div>
                <div class="text-xs text-blue-500 dark:text-blue-400 mt-1">Rows/Second</div>
            </div>

            {{-- Success Rate --}}
            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $record->success_rate }}%
                </div>
                <div class="text-xs text-green-500 dark:text-green-400 mt-1">Success Rate</div>
            </div>

            {{-- Error Rate --}}
            <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    @if($record->total_rows > 0)
                        {{ round(($record->error_count / $record->total_rows) * 100, 1) }}%
                    @else
                        0%
                    @endif
                </div>
                <div class="text-xs text-red-500 dark:text-red-400 mt-1">Error Rate</div>
            </div>
        </div>
    </div>

    {{-- Sync Results Details --}}
    @if($record->sync_results && !empty($record->sync_results))
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">📊 Detailed Results</h3>
        <div class="space-y-2">
            @foreach($record->sync_results as $key => $value)
                @if(!in_array($key, ['error_details']) && !is_array($value))
                <div class="flex justify-between py-1">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                        @if(is_numeric($value))
                            {{ number_format($value) }}
                        @elseif(is_bool($value))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $value ? 'Yes' : 'No' }}
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        @if($record->error_count > 0)
        <button type="button" 
                onclick="alert('Error details will be shown in a modal')"
                class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 dark:text-red-200 bg-white dark:bg-red-900/20 hover:bg-red-50 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            View {{ $record->error_count }} Error{{ $record->error_count > 1 ? 's' : '' }}
        </button>
        @endif

        <a href="https://docs.google.com/spreadsheets/d/{{ $record->spreadsheet_id }}/edit" 
           target="_blank"
           class="inline-flex items-center px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-200 bg-white dark:bg-blue-900/20 hover:bg-blue-50 dark:hover:bg-blue-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
            </svg>
            Open Spreadsheet
        </a>

        @if($record->status === 'completed')
        <button type="button" 
                onclick="if(confirm('Are you sure you want to trigger a new sync?')) { alert('Sync functionality will be implemented'); }"
                class="inline-flex items-center px-3 py-2 border border-green-300 dark:border-green-600 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 dark:text-green-200 bg-white dark:bg-green-900/20 hover:bg-green-50 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Sync Again
        </button>
        @endif
    </div>
</div>