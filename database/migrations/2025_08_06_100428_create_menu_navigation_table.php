<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menu_navigation', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_external')->default(false);
            $table->string('target')->default('_self'); // _self, _blank
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('permissions')->nullable(); // untuk role/permission
            $table->json('meta_data')->nullable(); // data tambahan
            $table->timestamps();
            
            // Foreign key untuk parent menu (sub menu)
            $table->foreign('parent_id')->references('id')->on('menu_navigation')->onDelete('cascade');
            
            // Indexes untuk performa
            $table->index(['is_active', 'sort_order']);
            $table->index(['parent_id', 'sort_order']);
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_navigation');
    }
};