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
    Schema::create('product_brands', function (Blueprint $table) {
        $table->id();
        $table->string('brand_name');
        $table->string('image')->nullable();
        $table->boolean('is_active')->default(true);
        $table->string('type')->nullable();
        $table->unsignedBigInteger('added_by')->nullable();
        $table->text('details')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_brands');
    }
};
