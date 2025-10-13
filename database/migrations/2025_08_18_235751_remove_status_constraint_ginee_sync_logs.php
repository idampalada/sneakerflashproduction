<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus constraint check untuk status
        DB::statement('ALTER TABLE ginee_sync_logs DROP CONSTRAINT IF EXISTS ginee_sync_logs_status_check');
        
        // Pastikan kolom status adalah VARCHAR
        DB::statement('ALTER TABLE ginee_sync_logs ALTER COLUMN status TYPE VARCHAR(255)');
    }

    public function down(): void
    {
        // Tidak perlu down, biarkan sebagai VARCHAR
    }
};