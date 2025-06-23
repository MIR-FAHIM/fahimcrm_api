<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    // Add a new client
    public function addClient(Request $request)
    {
        $request->validate([
            'prospect_id' => 'required|integer',
            'client_code' => 'required|string|unique:clients,client_code',
            'status' => 'nullable|string',
            'isActive' => 'nullable|boolean',
        ]);

        $client = Client::create([
            'prospect_id' => $request->prospect_id,
            'client_code' => $request->client_code,
            'status' => $request->status,
            'isActive' => $request->isActive ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Client created successfully',
            'data' => $client,
        ]);
    }

    // Get all clients
    public function getAllClients()
    {
        $clients = Client::with('prospect', 'prospect.industryType', 'prospect.informationSource')->get();

        return response()->json([
            'status' => 'success',
            'data' => $clients,
        ]);
    }
    public function getClientDetails($id)
    {
        $clients = Client::with('prospect', 'prospect.industryType', 'prospect.informationSource')->find($id);

        return response()->json([
            'status' => 'success',
            'data' => $clients,
        ]);
    }
}
