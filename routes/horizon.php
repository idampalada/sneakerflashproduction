<?php

use Laravel\Horizon\Horizon;

/*
|--------------------------------------------------------------------------
| Horizon Routes
|--------------------------------------------------------------------------
|
| File ini mengatur akses dashboard Horizon.
| Ubah return true ke sistem otentikasi kalau sudah production.
|
*/

Horizon::auth(function ($request) {
    // 🚧 Untuk development / staging: izinkan semua akses
    return true;

    // 🔒 Untuk production nanti:
    // return auth()->check() && auth()->user()->is_admin;
});
