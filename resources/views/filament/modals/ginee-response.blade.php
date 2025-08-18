<div class="p-4">
    @if(empty($response))
        <div class="text-center text-gray-500 dark:text-gray-400">
            <p>No response data available</p>
        </div>
    @else
        <div class="space-y-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Ginee API Response</h4>
                <pre class="text-sm text-gray-700 dark:text-gray-300 overflow-x-auto whitespace-pre-wrap">{{ json_encode($response, JSON_PRETTY_PRINT) }}</pre>
            </div>
            
            @if(isset($response['code']))
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                        <div class="text-sm font-medium text-blue-800 dark:text-blue-200">Response Code</div>
                        <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ $response['code'] }}</div>
                    </div>
                    
                    @if(isset($response['message']))
                        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <div class="text-sm font-medium text-green-800 dark:text-green-200">Message</div>
                            <div class="text-lg font-bold text-green-900 dark:text-green-100">{{ $response['message'] }}</div>
                        </div>
                    @endif
                </div>
            @endif
            
            @if(isset($response['transactionId']))
                <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
                    <div class="text-sm font-medium text-purple-800 dark:text-purple-200">Transaction ID</div>
                    <div class="font-mono text-purple-900 dark:text-purple-100">{{ $response['transactionId'] }}</div>
                </div>
            @endif
        </div>
    @endif
</div>