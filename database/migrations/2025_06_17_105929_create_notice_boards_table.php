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
    Schema::create('notice_boards', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('notice');
        $table->boolean('is_active')->default(true);
        $table->boolean('highlight')->default(false);
        $table->string('color_code')->nullable(); // e.g., #FF0000 or 'danger'
        $table->string('type')->nullable(); // e.g., #FF0000 or 'danger'
        $table->unsignedBigInteger('created_by')->nullable(); 
        $table->dateTime('start_date')->nullable();
        $table->dateTime('end_date')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_boards');
    }
};
