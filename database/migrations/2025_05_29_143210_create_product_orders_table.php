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
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('customer_id');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->string('order_from')->nullable(); // e.g., 'web', 'app', etc.
            $table->unsignedBigInteger('created_by')->nullable(); // admin/user who created it
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_cod')->default(false); // cash on delivery
            $table->string('status')->default('pending'); // or use enum
            $table->boolean('isPaid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
