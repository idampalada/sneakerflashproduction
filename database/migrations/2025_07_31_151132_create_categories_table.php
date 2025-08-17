<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            
            // Display Settings
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Menu Classification (Optional - for future use)
            $table->string('menu_placement')->nullable(); // general, mens, womens, kids, accessories
            $table->json('secondary_menus')->nullable(); // PostgreSQL JSON - additional menus
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // SEO & Search (Optional)
            $table->json('category_keywords')->nullable(); // PostgreSQL JSON
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // PostgreSQL JSON
            $table->string('brand_color')->nullable(); // Hex color code
            
            // Additional Data
            $table->json('meta_data')->nullable(); // PostgreSQL JSON - any additional data
            
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('slug');
            $table->index('show_in_menu');
            $table->index('menu_placement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
