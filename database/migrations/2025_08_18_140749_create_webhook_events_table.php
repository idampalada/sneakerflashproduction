<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('source')->default('ginee');
            $table->string('entity')->nullable();
            $table->string('action')->nullable();
            $table->jsonb('payload')->nullable(); // pgsql
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['entity','action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
