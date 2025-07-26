<?php
// File: database/migrations/2025_01_XX_add_google_fields_to_users_table.php
// PostgreSQL Compatible Migration for Google OAuth

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add Google OAuth fields
            $table->string('google_id')->nullable()->after('email');
            $table->text('avatar')->nullable()->after('google_id'); // Use TEXT for long URLs
            
            // Make password nullable (for Google users) - PostgreSQL way
            $table->string('password')->nullable()->change();
            
            // Ensure email_verified_at is nullable
            $table->timestamp('email_verified_at')->nullable()->change();
            
            // Add PostgreSQL specific index for Google ID
            $table->index('google_id');
            $table->index('email'); // Ensure email is indexed
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop PostgreSQL indexes first
            $table->dropIndex(['google_id']);
            
            // Drop columns
            $table->dropColumn(['google_id', 'avatar']);
            
            // Note: In PostgreSQL, reverting nullable constraint might require data handling
            // Be careful with existing data
        });
    }
};