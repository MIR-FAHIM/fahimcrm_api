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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable(); // Added phone number
            $table->text('address')->nullable(); // Added address
            $table->foreignId('designation_id')->nullable(); // Added foreign key for designation
            $table->foreignId('role_id')->nullable(); // Added foreign key for role
            $table->foreignId('department_id')->nullable(); // Added foreign key for department
            $table->date('birthdate')->nullable(); // Added birthdate
            $table->boolean('isActive')->default(true); // Added isActive status
            $table->string('photo')->nullable(); // Added photo URL
            $table->text('bio')->nullable(); // Added bio
            $table->string('fcm_token')->nullable(); // Added fcm_token
            $table->string('app_token')->nullable(); // Added app_token
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('start_hour')->nullable();
            $table->integer('start_min')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
