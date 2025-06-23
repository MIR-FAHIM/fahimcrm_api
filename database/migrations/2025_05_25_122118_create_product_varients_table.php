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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');         // FK to products
            $table->string('type')->nullable();               // e.g. electronics, clothing
            $table->string('model')->nullable();              // model name/code
            $table->string('sku')->unique();                  // unique SKU
            $table->string('product_code')->nullable();       // internal code
            $table->integer('quantity_required')->default(0); // qty threshold or minimum required
            $table->unsignedBigInteger('stock_id')->nullable(); // FK to stock location/table if applicable
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('in_stock');    // optional: in_stock, out_of_stock, etc.
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->string('discount_type')->nullable();      // percentage / flat
            $table->string('color_code')->nullable();         // e.g., #FFFFFF
            $table->string('size')->nullable();               // e.g., M, L, XL
            $table->string('unit')->nullable();               // e.g., kg, pcs
            $table->decimal('weight', 10, 2)->nullable();      // in kg or g
            $table->unsignedBigInteger('entry_by')->nullable(); // user ID who entered
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
            $table->boolean('is_refundable')->default(true);
            $table->string('video_link')->nullable();
            $table->string('image_link')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('external_link')->nullable();

            $table->timestamps();

            // Optional: Foreign key constraints
            $table->foreign('product_id')->references('id')->on('product_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
