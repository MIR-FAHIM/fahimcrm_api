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
        Schema::create('add_prospect_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->onDelete('cascade');
            $table->string('person_name'); // Add person_name column
          
            $table->foreignId('designation_id')->nullable(); // Add designation_id (assuming you have a related table)
            $table->string('mobile'); // Add mobile column
            $table->string('email')->unique(); // Add email column with unique constraint
            $table->text('note')->nullable(); // Add note column, nullable
            $table->boolean('is_primary')->default(false); // Add is_primary column, default false
            $table->boolean('is_responsive')->default(true); // Add is_responsive column, default true
            $table->foreignId('attitude_id')->nullable(); // Add attitude_id (assuming you have a related table)
            $table->boolean('is_key_contact')->default(false); 
            // Add is_key_contact column, default false
            $table->foreignId('influencing_role_id')->nullable();
        $table->text('birth_date')->timestamps()->nullable();
        $table->text('anniversary')->timestamps()->nullable();
        $table->boolean('is_switched_job')->default(false);
            $table->timestamps(); // Add timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_prospect_contacts');
    }
};
