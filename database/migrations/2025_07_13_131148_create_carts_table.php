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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->decimal('product_amount', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign keys (optional but recommended)
            $table->foreign('order_id');
            $table->foreign('product_id');
            $table->foreign('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
