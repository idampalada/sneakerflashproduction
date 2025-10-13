<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add kolom google_id dan avatar
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email_verified_at');
            $table->text('avatar')->nullable()->after('google_id');
        });

        // Make password nullable (PostgreSQL)
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
        
        // Add unique constraint untuk google_id
        DB::statement('CREATE UNIQUE INDEX users_google_id_unique ON users (google_id) WHERE google_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_google_id_unique');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
        });
    }
};
