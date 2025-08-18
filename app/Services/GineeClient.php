<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GineeClient
{
    private string $base;
    private string $accessKey;
    private string $secretKey;
    private string $country;

    public function __construct(?array $cfg = null)
    {
        $cfg = $cfg ?: config('services.ginee', []);
        $this->base      = rtrim($cfg['base'] ?? 'https://api.ginee.com', '/');
        $this->accessKey = (string)($cfg['access_key'] ?? '');
        $this->secretKey = (string)($cfg['secret_key'] ?? '');
        $this->country   = (string)($cfg['country'] ?? 'ID');

        if (!$this->accessKey || !$this->secretKey) {
            throw new \RuntimeException('Ginee access_key/secret_key belum terisi.');
        }
    }

    /* ===================== PUBLIC APIS ===================== */

    /** List Master Product — sesuai dokumentasi: POST /openapi/product/master/v1/list */
    public function listMasterProduct(array $params): array
    {
        $body = [
            'page'        => (int)($params['page'] ?? 0),    // >= 0
            'size'        => (int)($params['size'] ?? 20),   // 1..199
            'productName' => $params['productName'] ?? null,
            'sku'         => $params['sku'] ?? null,
            'brand'       => $params['brand'] ?? null,
            'barCode'     => $params['barCode'] ?? null,
            'categoryId'  => $params['categoryId'] ?? null,
            'createDateFrom' => $params['createDateFrom'] ?? null,
            'createDateTo'   => $params['createDateTo'] ?? null,
        ];
        // buang null/empty
        $body = array_filter($body, fn ($v) => !is_null($v) && $v !== '');
        return $this->request('POST', '/openapi/product/master/v1/list', $body);
    }

    /** Contoh adjust stock (ubah path sesuai kebutuhanmu kalau berbeda) */
    public function adjustInventory(string $warehouseId, array $stockList): array
    {
        $body = [
            'warehouseId' => $warehouseId,
            'items'       => $stockList, // masing2: ['masterSku'=>'SKU-001','quantity'=>30,'remark'=>'sync']
        ];
        return $this->request('POST', '/openapi/stock/warehouse/inventory/adjust', $body);
    }

    /* ===================== CORE REQUEST W/ SIGN ===================== */

    private function request(string $method, string $path, array $json = []): array
    {
        $method  = strtoupper($method);
        $path    = '/' . ltrim($path, '/');

        // penting: body harus string yang sama persis saat dihitung signature
        $bodyStr = $json ? json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        // Kandidat tanggal yang sering dipakai Ginee
        $dates = [
            now('UTC')->format('Y-m-d\TH:i:s.v\Z'),     // ISO8601 w/ milliseconds
            (string) intval(microtime(true) * 1000),    // epoch ms
        ];

        // Kandidat “hash body” yang beberapa tenant masukkan ke string-to-sign
        $hashes = [
            '',                                         // tidak dipakai
            $bodyStr,                                   // raw body
            hash('sha256', $bodyStr),                   // sha256 hex
            base64_encode(hash('sha256', $bodyStr, true)), // sha256 b64
            md5($bodyStr),                              // md5 hex
            base64_encode(md5($bodyStr, true)),         // md5 b64
        ];

        $last = null;

        foreach ($dates as $date) {
            foreach ($hashes as $h) {
                // Beberapa pola string-to-sign yang umum:
                $candidates = [
                    "{$method}\n{$path}\n{$date}\n{$h}",
                    "{$date}\n{$h}",
                    "{$method}\n{$path}\n{$date}",
                    "{$date}",
                ];

                foreach ($candidates as $variantIdx => $toSign) {
                    $sig = base64_encode(hash_hmac('sha256', $toSign, $this->secretKey, true));

                    $headers = [
                        'Content-Type'    => 'application/json',
                        'X-Advai-Country' => $this->country,
                        'X-Advai-Date'    => $date,
                        'Authorization'   => "{$this->accessKey}:{$sig}",
                    ];

                    $resp = Http::baseUrl($this->base)
                        ->timeout(30)
                        ->withHeaders($headers)
                        ->acceptJson()
                        // gunakan ->withBody agar bodyStr tidak dimodifikasi (penting untuk sign)
                        ->withBody($bodyStr, 'application/json')
                        ->send($method, $path);

                    $jsonResp = $resp->json() ?? [];

                    if (($jsonResp['code'] ?? null) === 'SUCCESS') {
                        Log::debug('[Ginee] SIGN OK', [
                            'variant'   => $variantIdx,
                            'date'      => $date,
                            'http'      => $resp->status(),
                            'path'      => $path,
                        ]);
                        return $jsonResp;
                    }

                    // simpan respons terakhir buat di-return kalau semuanya gagal
                    $last = $jsonResp ?: ['http' => $resp->status(), 'body' => $resp->body()];
                }
            }
        }

        Log::warning('[Ginee] SIGN FAILED', ['path' => $path, 'last' => $last]);
        return $last ?? ['code' => 'CLIENT_ERROR', 'message' => 'No response'];
    }
}
