<?php
/**
 * ✅ Ginee API Manual Test - Signature Format 2025 ($ delimiter)
 * PHP 8.3 / Laravel 11 compatible
 */

$requestHost = "https://api.ginee.com";
$requestUri  = "/openapi/warehouse-inventory/v1/sku/list";
$httpMethod  = "POST";
$accessKey   = "6505d28a3bb0b621";       // ⚠️ ganti dengan key kamu (jangan commit ke git)
$secretKey   = "f88d75ae803fbbdd";       // ⚠️ ganti dengan secret key kamu
$country     = "ID";

// 🧩 Body JSON sesuai dokumentasi resmi
$paramJson = json_encode([
    "page" => 0,
    "size" => 50,
    "warehouseId" => "WW635660264CEDFD0001D9D605"
], JSON_UNESCAPED_SLASHES);

// 🧮 Buat signature string (gunakan $ sebagai pemisah)
$newline = '$';
$signStr = $httpMethod . $newline . $requestUri . $newline;

// ✍️ HMAC-SHA256 → Base64
$signature = base64_encode(hash_hmac('sha256', $signStr, $secretKey, true));
$authorization = sprintf('%s:%s', $accessKey, $signature);

// 🧱 Headers
$headers = [
    'Authorization: ' . $authorization,
    'Content-Type: application/json',
    'X-Advai-Country: ' . $country
];

// 🧠 Debug info
echo "=====================================================\n";
echo "🧠  GINEE API MANUAL TEST (Simplified \$ Signature)\n";
echo "=====================================================\n";
echo "HOST         : {$requestHost}\n";
echo "URI          : {$requestUri}\n";
echo "METHOD       : {$httpMethod}\n";
echo "BODY         : {$paramJson}\n";
echo "SIGN STRING  : {$signStr}\n";
echo "SIGNATURE    : {$signature}\n";
echo "AUTH HEADER  : {$authorization}\n";
echo "=====================================================\n";

// 🚀 Kirim request via stream context (sesuai contoh resmi)
$httpOptions = [
    'http' => [
        'method'  => $httpMethod,
        'header'  => $headers,
        'content' => $paramJson,
        'ignore_errors' => true,
        'timeout' => 30
    ]
];

$context = stream_context_create($httpOptions);
$response = file_get_contents($requestHost . $requestUri, false, $context);

// 📄 Output hasil
echo "✅ RESPONSE:\n";
echo $response . "\n";
echo "=====================================================\n";
