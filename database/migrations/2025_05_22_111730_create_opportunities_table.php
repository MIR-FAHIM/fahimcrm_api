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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->text('details');
            $table->unsignedBigInteger('prospect_id');
            $table->unsignedBigInteger('created_by');
            $table->date('closing_date')->nullable();
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            // Optional: Add foreign key constraints
            // $table->foreign('prospect_id')->references('id')->on('prospects')->onDelete('cascade');
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('priority_id')->references('id')->on('priorities');
            // $table->foreign('stage_id')->references('id')->on('stages');
            // $table->foreign('approved_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
