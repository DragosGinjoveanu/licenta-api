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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->string('unit_of_measure');
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('alert_level', 10, 2)->default(0);
            $table->decimal('cost_per_unit', 10, 2)->default(0); 
            
            $table->unique(['restaurant_id', 'name']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};

