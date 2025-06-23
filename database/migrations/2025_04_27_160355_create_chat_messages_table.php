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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_room_id');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'file', 'audio', 'video'])->default('text');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_seen')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable(); // For reply messages
            $table->timestamps();

            // // Foreign keys
            // $table->foreign('conversation_room_id')->references('id')->on('conversation_rooms')->onDelete('cascade');
            // $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('receiver_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('parent_id')->references('id')->on('chat_messages')->onDelete('set null');

            // Indexes
            $table->index('conversation_room_id');
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
