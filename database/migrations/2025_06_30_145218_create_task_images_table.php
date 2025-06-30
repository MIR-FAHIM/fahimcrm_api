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
        Schema::create('task_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('image_file')->nullable(); // For storing file path or name
            $table->string('image_url')->nullable();  // Optional external link
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            // Add foreign key if tasks table exists
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_images');
    }
};
