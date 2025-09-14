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
        Schema::table('user_addresses', function (Blueprint $table) {
            // Add hierarchical location fields - check if columns exist first
            $table->unsignedInteger('province_id')->nullable()->after('phone_recipient');
            // province_name already exists, so we skip it
            
            $table->unsignedInteger('city_id')->nullable()->after('province_name');
            // city_name already exists, so we skip it
            
            $table->unsignedInteger('district_id')->nullable()->after('city_name');
            $table->string('district_name')->nullable()->after('district_id');
            
            $table->unsignedInteger('sub_district_id')->nullable()->after('district_name');
            $table->string('sub_district_name')->nullable()->after('sub_district_id');
            
            // Rename existing columns to match our hierarchical system
            $table->renameColumn('subdistrict_name', 'subdistrict_name_old');
            $table->renameColumn('postal_code', 'postal_code_old');
            
            // Add new postal code from API response
            $table->string('postal_code_api')->nullable()->after('sub_district_name');
            
            // Add search_location for backward compatibility if not exists
            if (!Schema::hasColumn('user_addresses', 'search_location')) {
                $table->string('search_location')->nullable()->after('postal_code_api');
            }
            
            // Add index for faster queries
            $table->index(['province_id', 'city_id', 'district_id', 'sub_district_id'], 'location_hierarchy_index');
            $table->index('province_id', 'province_id_index');
            $table->index('city_id', 'city_id_index');
            $table->index('district_id', 'district_id_index');
            $table->index('sub_district_id', 'sub_district_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('location_hierarchy_index');
            $table->dropIndex('province_id_index');
            $table->dropIndex('city_id_index');
            $table->dropIndex('district_id_index');
            $table->dropIndex('sub_district_id_index');
            
            // Drop columns
            $table->dropColumn([
                'province_id',
                'province_name',
                'city_id',
                'city_name',
                'district_id',
                'district_name',
                'sub_district_id',
                'sub_district_name',
                'postal_code_api'
            ]);
        });
    }
};