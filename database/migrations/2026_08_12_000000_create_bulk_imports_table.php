<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_imports')) {
            Schema::create('bulk_imports', function (Blueprint $table) {
                $table->id();
                $table->string('module')->index();
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('status')->default('previewed')->index();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_count')->default(0);
                $table->unsignedInteger('warning_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedInteger('imported_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->json('summary')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_imports');
    }
};
