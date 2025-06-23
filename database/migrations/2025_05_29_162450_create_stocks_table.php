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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('stock_name');
            $table->string('stock_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('pending'); // can be changed to enum
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->text('note')->nullable();
            $table->string('stock_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
