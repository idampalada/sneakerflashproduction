<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Approach 1: Drop constraint check dan buat ulang dengan nilai yang lebih lengkap
        try {
            // Lihat constraint yang ada
            $constraints = DB::select("
                SELECT constraint_name 
                FROM information_schema.check_constraints 
                WHERE constraint_name LIKE '%status%' 
                AND table_name = 'ginee_sync_logs'
            ");
            
            // Drop semua constraint yang berkaitan dengan status
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE ginee_sync_logs DROP CONSTRAINT IF EXISTS {$constraint->constraint_name}");
            }
            
            // Ubah kolom status menjadi varchar tanpa constraint
            DB::statement("ALTER TABLE ginee_sync_logs ALTER COLUMN status TYPE VARCHAR(255)");
            
            echo "✅ Successfully removed status constraints and changed to VARCHAR\n";
            
        } catch (\Exception $e) {
            echo "❌ Error in approach 1: " . $e->getMessage() . "\n";
            
            // Approach 2: Coba dengan Laravel Schema
            try {
                Schema::table('ginee_sync_logs', function (Blueprint $table) {
                    $table->string('status')->change();
                });
                echo "✅ Successfully changed status to string using Laravel Schema\n";
            } catch (\Exception $e2) {
                echo "❌ Error in approach 2: " . $e2->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum asli jika diperlukan
        try {
            Schema::table('ginee_sync_logs', function (Blueprint $table) {
                $table->enum('status', ['started', 'completed', 'failed', 'cancelled'])->change();
            });
        } catch (\Exception $e) {
            // Jika gagal, biarkan sebagai string
            echo "Could not revert to enum: " . $e->getMessage() . "\n";
        }
    }
};