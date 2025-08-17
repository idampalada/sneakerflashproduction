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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Address details
            $table->string('label')->nullable(); // e.g., "Home", "Office", "Rumah Utama"
            $table->string('recipient_name'); // Nama penerima (bisa beda dari user name)
            $table->string('phone_recipient'); // Nomor telepon penerima (bisa beda dari user phone)
            
            // Location details from RajaOngkir
            $table->string('province_name');
            $table->string('city_name');
            $table->string('subdistrict_name');
            $table->string('postal_code', 10);
            $table->string('destination_id')->nullable(); // RajaOngkir destination ID
            
            // Address details
            $table->text('street_address'); // Nama jalan, gedung, no rumah
            $table->text('notes')->nullable(); // Catatan tambahan
            
            // Address status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Regular indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_primary']);
            $table->index(['user_id', 'is_active', 'is_primary']);
        });
        
        // PostgreSQL specific: Add unique constraint for one primary address per user
        // We'll do this after table creation to avoid issues
        DB::statement('
            CREATE UNIQUE INDEX user_addresses_one_primary_per_user 
            ON user_addresses (user_id) 
            WHERE is_primary = true AND is_active = true
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the custom index first (if exists)
        DB::statement('DROP INDEX IF EXISTS user_addresses_one_primary_per_user');
        
        Schema::dropIfExists('user_addresses');
    }
};