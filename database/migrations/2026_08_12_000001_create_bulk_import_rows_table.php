<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_import_rows')) {
            Schema::create('bulk_import_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bulk_import_id')->constrained('bulk_imports')->onDelete('cascade');
                $table->unsignedInteger('row_number');
                $table->string('row_type')->default('prospect_contact');
                $table->string('match_key')->nullable()->index();
                $table->string('status')->index();
                $table->json('raw_data')->nullable();
                $table->json('normalized_data')->nullable();
                $table->json('errors')->nullable();
                $table->json('warnings')->nullable();
                $table->unsignedBigInteger('created_record_id')->nullable();
                $table->unsignedBigInteger('created_contact_id')->nullable();
                $table->timestamps();

                $table->index(['bulk_import_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_import_rows');
    }
};
