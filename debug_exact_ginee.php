<?php

echo "🔍 Testing with EXACT Ginee API Tool Code\n\n";

// EXACT CODE dari Ginee API Tool
$request_host = 'https://api.ginee.com';
$request_uri = "/openapi/warehouse-inventory/v1/sku/list";
$http_method = "POST";
$param_json = '{"page":0,"size":2}';
$access_key = '6505d28a3bb0b621';
$secret_key = 'f88d75ae803fbbdd';
$newline = '$';
$sign_str = $http_method . $newline . $request_uri . $newline;
$authorization = sprintf('%s:%s', $access_key, base64_encode(hash_hmac('sha256', $sign_str, $secret_key, TRUE)));

echo "Signature string: " . $sign_str . "\n";
echo "Authorization: " . $authorization . "\n\n";

$header_array = array(
    'Authorization: ' . $authorization,
    'Content-Type: ' . 'application/json',
    'X-Advai-Country: ' . 'ID'
);

$http_header = array(
    'http' => array(
        'method' => $http_method,
        'header' => implode("\r\n", $header_array),
        'content' => $param_json
    )
);

$context = stream_context_create($http_header);
$response = file_get_contents($request_host . $request_uri, false, $context);

if ($response === false) {
    echo "❌ Request failed\n";
} else {
    echo "✅ Response received:\n";
    $json = json_decode($response, true);
    echo "Code: " . ($json['code'] ?? 'unknown') . "\n";
    echo "Message: " . ($json['message'] ?? 'no message') . "\n";
}
