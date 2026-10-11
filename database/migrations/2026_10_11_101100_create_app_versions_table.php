<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('platform', 30)->default('android');
            $table->string('version_name', 50);
            $table->unsignedInteger('version_code');
            $table->unsignedInteger('min_supported_version_code')->nullable();
            $table->string('download_url', 2048)->nullable();
            $table->string('playstore_url', 2048)->nullable();
            $table->string('appstore_url', 2048)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_force_update')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
