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
        Schema::create('client_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id'); // reference to clients table
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // e.g. bug, feature, question
            $table->string('createdBy')->nullable(); // e.g. bug, feature, question
            $table->unsignedBigInteger('issue_id')->nullable(); // reference to issues table if any
            $table->string('ticket_code')->unique(); // unique ticket code
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->unsignedBigInteger('priority_id')->nullable(); // reference to priorities table
            $table->boolean('is_urgent')->default(false);
            $table->string('category')->nullable(); // category name or reference id
            $table->string('attachment')->nullable(); // file path or URL
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            // Optional indexes & foreign keys
            $table->index('client_id');
            $table->index('issue_id');
            $table->index('priority_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_tickets');
    }
};
