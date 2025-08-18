<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GineeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GineeSyncController extends Controller
{
    public function __construct(private GineeClient $ginee) {}

    public function pullProducts()
    {
        $res = $this->ginee->listProducts(['pageNo' => 1, 'pageSize' => 100]);
        // TODO: mapping $res ke DB kamu bila perlu
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function pushStock(Request $request)
    {
        // Ambil dari request (atau isi manual saat uji)
        $warehouseId = (string) $request->input('warehouseId');

        // Maks 20 item per request (sesuai AdjustInventory)
        $stockList = $request->input('stockList', [
            ['masterSku' => 'SKU-001', 'quantity' => 30, 'remark' => 'sync'],
        ]);

        $data = $this->ginee->adjustInventory($warehouseId, $stockList);
        return response()->json(['ok' => true, 'data' => $data]);
    }
}
