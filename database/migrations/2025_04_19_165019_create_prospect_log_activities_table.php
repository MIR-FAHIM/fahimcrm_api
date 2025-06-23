<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProspectLogActivitiesTable extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_log_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('related_id')->nullable(); // For tasks etc.
            $table->enum('activity_type', ['general','task', 'call', 'email', 'whatsapp', 'visit', 'message','meeting', 'stage']);
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('activity_time')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_log_activities');
    }
}

