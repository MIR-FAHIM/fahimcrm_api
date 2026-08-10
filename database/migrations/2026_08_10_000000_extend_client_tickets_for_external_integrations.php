<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE client_tickets MODIFY client_id BIGINT UNSIGNED NULL');

        Schema::table('client_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('client_tickets', 'source')) {
                $table->string('source')->default('internal')->after('createdBy');
            }
            if (!Schema::hasColumn('client_tickets', 'external_ticket_id')) {
                $table->string('external_ticket_id')->nullable()->after('source');
            }
            if (!Schema::hasColumn('client_tickets', 'external_client_id')) {
                $table->string('external_client_id')->nullable()->after('external_ticket_id');
            }
            if (!Schema::hasColumn('client_tickets', 'external_client_name')) {
                $table->string('external_client_name')->nullable()->after('external_client_id');
            }
            if (!Schema::hasColumn('client_tickets', 'external_client_email')) {
                $table->string('external_client_email')->nullable()->after('external_client_name');
            }
            if (!Schema::hasColumn('client_tickets', 'external_client_phone')) {
                $table->string('external_client_phone', 50)->nullable()->after('external_client_email');
            }
            if (!Schema::hasColumn('client_tickets', 'match_status')) {
                $table->string('match_status')->default('matched')->after('external_client_phone');
            }
            if (!Schema::hasColumn('client_tickets', 'matched_by')) {
                $table->string('matched_by')->nullable()->after('match_status');
            }
            if (!Schema::hasColumn('client_tickets', 'converted_task_id')) {
                $table->unsignedBigInteger('converted_task_id')->nullable()->after('matched_by');
            }
            if (!Schema::hasColumn('client_tickets', 'external_priority')) {
                $table->string('external_priority')->nullable()->after('converted_task_id');
            }
            if (!Schema::hasColumn('client_tickets', 'external_status')) {
                $table->string('external_status')->nullable()->after('external_priority');
            }
            if (!Schema::hasColumn('client_tickets', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('external_status');
            }
            if (!Schema::hasColumn('client_tickets', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('raw_payload');
            }
        });

        try {
            Schema::table('client_tickets', function (Blueprint $table) {
                $table->unique(['source', 'external_ticket_id'], 'client_tickets_source_external_ticket_unique');
            });
        } catch (\Exception $e) {
            // Keep migration deployable if the index already exists.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_tickets', function (Blueprint $table) {
            $table->dropUnique('client_tickets_source_external_ticket_unique');
            $table->dropColumn([
                'source',
                'external_ticket_id',
                'external_client_id',
                'external_client_name',
                'external_client_email',
                'external_client_phone',
                'match_status',
                'matched_by',
                'converted_task_id',
                'external_priority',
                'external_status',
                'raw_payload',
                'last_synced_at',
            ]);
        });
    }
};
