<?php
// database/migrations/2025_09_15_cleanup_user_addresses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // 1. HAPUS kolom yang duplikasi/tidak diperlukan
            if (Schema::hasColumn('user_addresses', 'subdistrict_name_old')) {
                $table->dropColumn('subdistrict_name_old');
            }
            if (Schema::hasColumn('user_addresses', 'postal_code_old')) {
                $table->dropColumn('postal_code_old');
            }
            
            // 2. RENAME kolom untuk konsistensi
            // Ubah postal_code_api menjadi postal_code
            if (Schema::hasColumn('user_addresses', 'postal_code_api') && 
                !Schema::hasColumn('user_addresses', 'postal_code')) {
                $table->renameColumn('postal_code_api', 'postal_code');
            }
            
            // 3. TAMBAH kolom yang belum ada (jika diperlukan)
            if (!Schema::hasColumn('user_addresses', 'subdistrict_name')) {
                $table->string('subdistrict_name')->nullable()->after('sub_district_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Kembalikan perubahan
            $table->string('subdistrict_name_old')->nullable();
            $table->string('postal_code_old')->nullable();
            
            if (Schema::hasColumn('user_addresses', 'postal_code')) {
                $table->renameColumn('postal_code', 'postal_code_api');
            }
            
            if (Schema::hasColumn('user_addresses', 'subdistrict_name')) {
                $table->dropColumn('subdistrict_name');
            }
        });
    }
};