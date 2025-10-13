<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;          // <-- WAJIB
use App\Services\GineeClient;               // <-- WAJIB

class InventoryPushController extends Controller
{
    public function __construct(private GineeClient $ginee) {}

    public function pushOnHand(Request $r)
    {
        $warehouseId = $r->string('warehouseId');

        $changes = DB::table('inventories')
            ->where('dirty', 1)
            ->limit(20) // sesuai batas API
            ->get(['master_sku as masterSku', 'qty as quantity'])
            ->map(fn($x) => ['masterSku'=>$x->masterSku, 'quantity'=>(int)$x->quantity, 'remark'=>'sync from laravel'])
            ->toArray();

        if (!$changes) {
            return response()->json(['ok'=>true, 'msg'=>'no changes']);
        }

        $data = $this->ginee->adjustInventory($warehouseId, $changes);

        DB::table('inventories')
            ->whereIn('master_sku', array_column($changes, 'masterSku'))
            ->update(['dirty'=>0]);

        return response()->json(['ok'=>true, 'data'=>$data]);
    }
}
