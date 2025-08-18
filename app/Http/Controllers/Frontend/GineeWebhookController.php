<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GineeWebhookController extends Controller
{
    public function orders(Request $r)
    {
        $payload = $r->all();

        // Idempotensi sederhana
        if (isset($payload['id']) &&
            DB::table('webhook_events')->where('event_id',$payload['id'])->exists()) {
            return response()->noContent();
        }
        DB::table('webhook_events')->insert([
            'event_id' => $payload['id'] ?? uniqid('ginee_', true),
            'entity'   => $payload['entity'] ?? 'order',
            'action'   => $payload['action'] ?? null,
            'payload'  => json_encode($payload),
        ]);

        // TODO: mapping payload['payload'] ke tabel order Anda
        Log::info('[GineeWebhook][Order]', $payload);

        return response()->noContent(); // 204/200
    }

    public function masterProducts(Request $r)
    {
        $payload = $r->all();
        // TODO: mapping master product jika diperlukan
        Log::info('[GineeWebhook][MasterProduct]', $payload);
        return response()->noContent();
    }
    public function global(Request $request)
{
    // Jika Ginee “cek URL” pakai GET
    if ($request->isMethod('get')) {
        return response()->json(['ok' => true, 'endpoint' => 'ginee/global']);
    }

    $payload = $request->all();
    $eventId = $payload['id'] ?? uniqid('ginee_', true);

    // Idempotensi
    if (\DB::table('webhook_events')->where('event_id', $eventId)->exists()) {
        return response()->noContent();
    }

    \DB::table('webhook_events')->insert([
        'event_id'   => $eventId,
        'source'     => 'ginee',
        'entity'     => $payload['entity'] ?? 'global',
        'action'     => $payload['action'] ?? null,
        'payload'    => json_encode($payload),
        'created_at' => now(),
    ]);

    // (opsional) routing sederhana ke handler khusus
    // if (($payload['entity'] ?? null) === 'order') { /* map order */ }

    return response()->noContent(); // 204
}

}
