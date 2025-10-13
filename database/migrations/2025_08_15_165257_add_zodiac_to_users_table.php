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
        Schema::table('users', function (Blueprint $table) {
            // Add zodiac column after birthdate
            $table->string('zodiac', 20)->nullable()->after('birthdate');
            
            // Add index for better performance
            $table->index('zodiac');
        });
        
        // PostgreSQL specific: Add comment to column
        DB::statement("COMMENT ON COLUMN users.zodiac IS 'User zodiac sign calculated from birthdate'");
        
        // Create function to calculate zodiac from birthdate (PostgreSQL)
        DB::statement("
            CREATE OR REPLACE FUNCTION get_zodiac_sign(birth_date DATE) 
            RETURNS VARCHAR(20) AS $$
            DECLARE
                month_day INTEGER;
            BEGIN
                IF birth_date IS NULL THEN
                    RETURN NULL;
                END IF;
                
                -- Convert to MMDD format for easy comparison
                month_day := EXTRACT(MONTH FROM birth_date) * 100 + EXTRACT(DAY FROM birth_date);
                
                CASE 
                    WHEN (month_day >= 219 AND month_day <= 320) THEN RETURN 'PISCES';
                    WHEN (month_day >= 321 AND month_day <= 419) THEN RETURN 'ARIES';
                    WHEN (month_day >= 420 AND month_day <= 520) THEN RETURN 'TAURUS';
                    WHEN (month_day >= 521 AND month_day <= 620) THEN RETURN 'GEMINI';
                    WHEN (month_day >= 621 AND month_day <= 722) THEN RETURN 'CANCER';
                    WHEN (month_day >= 723 AND month_day <= 822) THEN RETURN 'LEO';
                    WHEN (month_day >= 823 AND month_day <= 922) THEN RETURN 'VIRGO';
                    WHEN (month_day >= 923 AND month_day <= 1022) THEN RETURN 'LIBRA';
                    WHEN (month_day >= 1023 AND month_day <= 1121) THEN RETURN 'SCORPIO';
                    WHEN (month_day >= 1122 AND month_day <= 1221) THEN RETURN 'SAGITARIUS';
                    WHEN (month_day >= 1222 OR month_day <= 119) THEN RETURN 'CAPRICORN';
                    WHEN (month_day >= 120 AND month_day <= 218) THEN RETURN 'AQUARIUS';
                    ELSE RETURN NULL;
                END CASE;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // Update existing users with zodiac based on birthdate
        DB::statement("
            UPDATE users 
            SET zodiac = get_zodiac_sign(birthdate) 
            WHERE birthdate IS NOT NULL AND zodiac IS NULL
        ");
        
        // Create trigger function to auto-update zodiac when birthdate changes
        DB::statement("
            CREATE OR REPLACE FUNCTION update_zodiac_trigger() 
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.zodiac := get_zodiac_sign(NEW.birthdate);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // Create trigger to auto-update zodiac
        DB::statement("
            CREATE TRIGGER trigger_update_zodiac
                BEFORE INSERT OR UPDATE OF birthdate ON users
                FOR EACH ROW
                EXECUTE FUNCTION update_zodiac_trigger()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop trigger and function first
        DB::statement("DROP TRIGGER IF EXISTS trigger_update_zodiac ON users");
        DB::statement("DROP FUNCTION IF EXISTS update_zodiac_trigger()");
        DB::statement("DROP FUNCTION IF EXISTS get_zodiac_sign(DATE)");
        
        Schema::table('users', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex(['users_zodiac_index']);
            
            // Drop column
            $table->dropColumn('zodiac');
        });
    }
};