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
        Schema::create('modulepermissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id'); // Reference to company
            $table->string('module');                 // e.g., 'dashboard', 'task', etc.
            $table->boolean('is_active')->default(true); // true = enabled
            $table->timestamps();

            // Optional: Add foreign key constraint if you have a `companies` table
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modulepermissions');
    }
};
