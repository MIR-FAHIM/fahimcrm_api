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
        Schema::create('fb_sheet_leads', function (Blueprint $t) {
           $t->id();
            $t->string('lead_id')->unique();              // maps 'id' from sheet
            $t->string('full_name')->nullable();
            $t->string('phone_number')->nullable();
            $t->string('email')->nullable();

            $t->string('created_time')->nullable();    // FB created_time

            $t->string('ad_id')->nullable();
            $t->string('ad_name')->nullable();
            $t->string('adset_id')->nullable();
            $t->string('adset_name')->nullable();
            $t->string('campaign_id')->nullable();
            $t->string('campaign_name')->nullable();

            $t->string('form_id')->nullable();
            $t->string('form_name')->nullable();

            $t->boolean('is_organic')->nullable();
            $t->string('platform')->nullable();           // e.g., facebook / instagram
            $t->string('lead_status')->nullable();        // your own status (new/processed)

            $t->json('raw')->nullable();                  // keep the whole row for safety
            $t->timestamps();

            $t->index(['campaign_id', 'adset_id', 'ad_id']);
            $t->index(['created_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fb_sheet_leads');
    }
};
