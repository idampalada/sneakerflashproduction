<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_sync_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sync_type', 50); // 'auto', 'manual', 'spreadsheet_to_db', 'db_to_spreadsheet'
            $table->string('status', 20); // 'success', 'error', 'partial'
            $table->integer('records_processed')->default(0);
            $table->integer('errors_count')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamp('synced_at');
            $table->integer('execution_time_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_sync_log');
    }
};