<?php

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
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            // Ubah kolom type dari NOT NULL menjadi nullable
            $table->string('type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            // Kembalikan ke NOT NULL jika diperlukan
            // HATI-HATI: Pastikan tidak ada data dengan type NULL sebelum rollback
            $table->string('type')->nullable(false)->change();
        });
    }
};