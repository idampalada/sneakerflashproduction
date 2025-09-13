<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // 1. Tambah kolom baru sebagai nullable dulu
            $table->json('image_paths')->nullable()->after('description');
        });

        // 2. Migrate data existing ke format array
        DB::table('banners')->get()->each(function ($banner) {
            $imagePaths = [];
            if (!empty($banner->image_path)) {
                $imagePaths = [$banner->image_path];
            }
            
            DB::table('banners')
                ->where('id', $banner->id)
                ->update(['image_paths' => json_encode($imagePaths)]);
        });

        // 3. Sekarang hapus kolom lama dan ubah nullable
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['title', 'image_path', 'link_url', 'button_text']);
        });

        // 4. Set image_paths jadi NOT NULL setelah data sudah dimigrate
        DB::statement('ALTER TABLE banners ALTER COLUMN image_paths SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->default('Banner');
            $table->string('image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('button_text')->nullable();
        });

        // Migrate data back
        DB::table('banners')->get()->each(function ($banner) {
            $imagePaths = json_decode($banner->image_paths, true);
            $firstImage = !empty($imagePaths) ? $imagePaths[0] : null;
            
            DB::table('banners')
                ->where('id', $banner->id)
                ->update(['image_path' => $firstImage]);
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image_paths');
        });
    }
};