<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // ✅ Pastikan route Horizon terbuka (tidak dibatasi environment)
        Horizon::auth(function ($request) {
            // 🚧 sementara: izinkan semua akses agar bisa diakses via browser
            return true;
        });

        // ✅ Load route Horizon kalau belum otomatis terdaftar
        $this->loadRoutesFrom(base_path('routes/horizon.php'));
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // 🚀 Abaikan auth dulu di tahap dev
            return true;
        });
    }
}
