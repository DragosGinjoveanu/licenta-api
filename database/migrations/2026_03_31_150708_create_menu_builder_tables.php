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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('name'); 
            $table->timestamps();
        });
        
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        
        Schema::create('menu_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->integer('order')->default(0);
            
            // filters to show inside pdf
            $table->boolean('show_price')->default(true);
            $table->boolean('show_description')->default(true);
            $table->boolean('show_ingredients')->default(false);
            $table->json('visible_ingredients')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_builder_tables');
    }
};
