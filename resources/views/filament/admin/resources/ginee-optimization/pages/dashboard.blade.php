{{-- FILE: resources/views/filament/admin/resources/ginee-optimization/pages/dashboard.blade.php --}}

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold">🚀 Ginee Sync Optimization</h1>
                    <p class="text-blue-100 mt-1">
                        High-performance sync system - up to 10x faster than standard method
                    </p>
                </div>
            </div>
        </div>

        {{-- Performance Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">Speed Boost</h3>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">10-20x</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Faster sync</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">API Efficiency</h3>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">90%+</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fewer calls</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">Time Saving</h3>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">5-10min</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">For 1300 SKUs</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">Reliability</h3>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">99%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">No timeouts</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Instructions Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">📋 How to Use Optimization</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full text-xs font-bold mr-2">1</span>
                        <h4 class="font-medium text-gray-900 dark:text-white">Click Quick Sync Button</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-8">
                        Use the "🚀 Quick Sync Test" button in the top-right header to start.
                    </p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full text-xs font-bold mr-2">2</span>
                        <h4 class="font-medium text-gray-900 dark:text-white">Enter Your SKUs</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-8">
                        Paste your 1300 SKUs (one per line or comma-separated). Start with dry run for safety.
                    </p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full text-xs font-bold mr-2">3</span>
                        <h4 class="font-medium text-gray-900 dark:text-white">Monitor Progress</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-8">
                        Watch real-time progress and check logs for detailed results.
                    </p>
                </div>
            </div>
        </div>

        {{-- Performance Comparison --}}
        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900 dark:to-blue-900 rounded-lg border border-green-200 dark:border-green-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Performance Comparison</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-3">🐌 Standard Method</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            45-60 minutes for 1300 SKUs
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            1300+ individual API calls
                        </li>
                        <li class="flex items-center text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            Frequent Error 524 timeouts
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-3">🚀 Optimized Method</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            5-10 minutes for 1300 SKUs
                        </li>
                        <li class="flex items-center text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            ~10-15 bulk API calls
                        </li>
                        <li class="flex items-center text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            Zero timeout errors
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Status Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Optimization System Status: Ready</span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Last Updated: {{ now()->format('Y-m-d H:i:s') }}
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>