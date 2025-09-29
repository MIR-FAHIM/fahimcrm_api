<?php

// app/Http/Controllers/SheetLeadController.php
namespace App\Http\Controllers;

use App\Models\FbSheetLeads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FbSheetLeadsController extends Controller
{
    public function store(Request $req)
    {
        // Optional simple auth (recommend): send X-SHEET-KEY from Apps Script and check here
        if (config('services.sheets.key') && $req->header('X-SHEET-KEY') !== config('services.sheets.key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $req->all();  // expect keys exactly as your headers

        // Normalize keys to our DB columns
        $payload = [
            'lead_id'       => $data['id'] ?? Str::uuid()->toString(),
            'full_name'     => $data['full_name'] ?? null,
            'phone_number'  => $data['phone_number'] ?? null,
            'email'         => $data['email'] ?? null,
            'created_time'  => isset($data['created_time'])
                                ? Carbon::parse($data['created_time']) : null,
            'ad_id'         => $data['ad_id'] ?? null,
            'ad_name'       => $data['ad_name'] ?? null,
            'adset_id'      => $data['adset_id'] ?? null,
            'adset_name'    => $data['adset_name'] ?? null,
            'campaign_id'   => $data['campaign_id'] ?? null,
            'campaign_name' => $data['campaign_name'] ?? null,
            'form_id'       => $data['form_id'] ?? null,
            'form_name'     => $data['form_name'] ?? null,
            'is_organic'    => isset($data['is_organic'])
                                ? filter_var($data['is_organic'], FILTER_VALIDATE_BOOLEAN) : null,
            'platform'      => $data['platform'] ?? null,
            'lead_status'   => $data['lead_status'] ?? 'new',
            'raw'           => $data,
        ];

        // Upsert by lead_id to avoid duplicates
        $lead = FbSheetLeads::updateOrCreate(
            ['lead_id' => $payload['lead_id']],
            $payload
        );

        Log::info('sheet_lead_upserted', ['lead_id' => $lead->lead_id]);

        return response()->json(['status' => 'ok', 'lead_id' => $lead->lead_id]);
    }
}

