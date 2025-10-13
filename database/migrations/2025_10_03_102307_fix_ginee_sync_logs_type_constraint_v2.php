<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop ALL check constraints untuk type dan status
        DB::statement('ALTER TABLE ginee_sync_logs DROP CONSTRAINT IF EXISTS ginee_sync_logs_type_check');
        DB::statement('ALTER TABLE ginee_sync_logs DROP CONSTRAINT IF EXISTS ginee_sync_logs_status_check');
        
        // 2. Ubah kolom type dan status menjadi VARCHAR
        DB::statement('ALTER TABLE ginee_sync_logs ALTER COLUMN type TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE ginee_sync_logs ALTER COLUMN status TYPE VARCHAR(255)');
        
        // 3. Buat kolom type nullable (bisa kosong)
        DB::statement('ALTER TABLE ginee_sync_logs ALTER COLUMN type DROP NOT NULL');
        
        echo "✅ Fixed ginee_sync_logs constraints!\n";
        echo "   - Removed type check constraint\n";
        echo "   - Removed status check constraint\n";
        echo "   - Changed both to VARCHAR(255)\n";
        echo "   - Made type nullable\n";
    }

    public function down(): void
    {
        // Tidak perlu rollback - biarkan sebagai VARCHAR
        echo "⚠️ No rollback - keeping VARCHAR for flexibility\n";
    }
};