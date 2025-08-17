<div class="space-y-4">
    {{-- Error Summary --}}
    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                    {{ $record->error_count }} Error{{ $record->error_count > 1 ? 's' : '' }} Encountered
                </h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <p>Sync ID: <code class="bg-red-100 dark:bg-red-900 px-2 py-1 rounded text-xs">{{ $record->sync_id }}</code></p>
                    <p>Duration: {{ $record->duration_formatted }}</p>
                    @if($record->total_rows > 0)
                    <p>Error Rate: {{ round(($record->error_count / $record->total_rows) * 100, 1) }}%</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Error Message (if exists) --}}
    @if($record->error_message)
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">❌ Main Error Message</h4>
        <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
            <pre class="text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap font-mono">{{ $record->error_message }}</pre>
        </div>
    </div>
    @endif

    {{-- Detailed Errors --}}
    @if($record->error_details && !empty($record->error_details))
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">🔍 Detailed Error Log</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Showing {{ count($record->error_details) }} error{{ count($record->error_details) > 1 ? 's' : '' }}
            </p>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            @foreach($record->error_details as $index => $error)
            <div class="px-4 py-3 {{ $index > 0 ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-red-100 dark:bg-red-900/40 rounded-full flex items-center justify-center">
                            <span class="text-red-600 dark:text-red-400 text-xs font-medium">{{ $index + 1 }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        {{-- Error Header --}}
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                @if(isset($error['row']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    Row {{ $error['row'] }}
                                </span>
                                @endif
                                
                                @if(isset($error['sku_parent']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    SKU: {{ $error['sku_parent'] }}
                                </span>
                                @endif
                            </div>
                            
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Error #{{ $index + 1 }}
                            </span>
                        </div>

                        {{-- Error Message --}}
                        <div class="mb-2">
                            <p class="text-sm text-red-600 dark:text-red-400 font-medium">{{ $error['error'] }}</p>
                        </div>

                        {{-- Error Data (if available) --}}
                        @if(isset($error['data']) && !empty($error['data']))
                        <details class="mt-2">
                            <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Show row data
                            </summary>
                            <div class="mt-2 bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                    @foreach($error['data'] as $key => $value)
                                        @if(!empty($value) && strlen($value) < 200)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400 truncate">{{ $key }}:</dt>
                                            <dd class="text-gray-900 dark:text-gray-100 font-mono truncate ml-2">
                                                {{ is_array($value) ? json_encode($value) : $value }}
                                            </dd>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </details>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Error Categories Summary --}}
    @if($record->error_details && !empty($record->error_details))
    @php
        $errorCategories = [];
        foreach($record->error_details as $error) {
            $message = $error['error'];
            // Categorize errors by common patterns
            if (str_contains($message, 'required')) {
                $errorCategories['Missing Required Fields'] = ($errorCategories['Missing Required Fields'] ?? 0) + 1;
            } elseif (str_contains($message, 'price') || str_contains($message, 'numeric')) {
                $errorCategories['Invalid Price/Numeric Data'] = ($errorCategories['Invalid Price/Numeric Data'] ?? 0) + 1;
            } elseif (str_contains($message, 'image') || str_contains($message, 'URL')) {
                $errorCategories['Image/URL Issues'] = ($errorCategories['Image/URL Issues'] ?? 0) + 1;
            } elseif (str_contains($message, 'category')) {
                $errorCategories['Category Issues'] = ($errorCategories['Category Issues'] ?? 0) + 1;
            } elseif (str_contains($message, 'SKU') || str_contains($message, 'duplicate')) {
                $errorCategories['SKU/Duplicate Issues'] = ($errorCategories['SKU/Duplicate Issues'] ?? 0) + 1;
            } else {
                $errorCategories['Other Errors'] = ($errorCategories['Other Errors'] ?? 0) + 1;
            }
        }
    @endphp
    
    <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">📊 Error Categories</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($errorCategories as $category => $count)
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 border border-red-200 dark:border-red-800">
                <div class="text-center">
                    <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $count }}</div>
                    <div class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $category }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Resolution Tips --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">💡 Resolution Tips</h4>
        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
            <li>Check that all required fields (name, sku_parent, price) have values</li>
            <li>Ensure price and numeric fields contain valid numbers</li>
            <li>Verify that image URLs are accessible and properly formatted</li>
            <li>Make sure SKU values are unique across all rows</li>
            <li>Check date formats match expected patterns (YYYY-MM-DD)</li>
            <li>Remove any special characters that might cause parsing issues</li>
        </ul>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="https://docs.google.com/spreadsheets/d/{{ $record->spreadsheet_id }}/edit" 
           target="_blank"
           class="inline-flex items-center px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-200 bg-white dark:bg-blue-900/20 hover:bg-blue-50 dark:hover:bg-blue-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
            </svg>
            Fix Data in Spreadsheet
        </a>

        <button type="button" 
                onclick="if(confirm('Are you sure you want to retry the sync after fixing the errors?')) { /* implement retry logic */ }"
                class="inline-flex items-center px-3 py-2 border border-green-300 dark:border-green-600 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 dark:text-green-200 bg-white dark:bg-green-900/20 hover:bg-green-50 dark:hover:bg-green-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Retry Sync
        </button>

        <button type="button" 
                onclick="navigator.clipboard.writeText(document.querySelector('#error-log').textContent)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" clip-rule="evenodd" />
            </svg>
            Copy Error Log
        </button>
    </div>

    {{-- Hidden error log for copying --}}
    <div id="error-log" class="hidden">
Sync ID: {{ $record->sync_id }}
Spreadsheet: {{ $record->spreadsheet_id }}
Started: {{ $record->started_at }}
Total Errors: {{ $record->error_count }}

@if($record->error_message)
Main Error: {{ $record->error_message }}

@endif
@if($record->error_details)
Detailed Errors:
@foreach($record->error_details as $index => $error)
{{ $index + 1 }}. @if(isset($error['row']))Row {{ $error['row'] }}: @endif{{ $error['error'] }}
@endforeach
@endif
    </div>
</div>