<?php
// 1. Buat migration baru
// php artisan make:migration add_responsive_images_to_banners_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            // Tambah kolom untuk desktop dan mobile images
            $table->json('desktop_images')->nullable()->after('image_paths');
            $table->json('mobile_images')->nullable()->after('desktop_images');
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['desktop_images', 'mobile_images']);
        });
    }
};