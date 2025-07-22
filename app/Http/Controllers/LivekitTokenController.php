<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;

class LivekitTokenController extends Controller
{
  public function generateToken(Request $request)
    {
        $roomName = $request->query('roomName');
        $participantIdentity = $request->query('participantIdentity');

        if (empty($roomName) || empty($participantIdentity)) {
            return response()->json([
                'error' => 'roomName and participantIdentity are required query parameters'
            ], 400);
        }

        $apiKey = env('LIVEKIT_API_KEY');
        $apiSecret = env('LIVEKIT_API_SECRET');

        if (empty($apiKey) || empty($apiSecret)) {
            return response()->json([
                'error' => 'LIVEKIT_API_KEY or LIVEKIT_API_SECRET is not set in the .env file.'
            ], 500);
        }

        // LiveKit AccessToken payload structure
        $grants = [
            'roomJoin' => true,
            'room' => $roomName,
            'canPublish' => true,
            'canSubscribe' => true,
        ];

        $payload = [
            'iss' => $apiKey,
            'nbf' => time(),             // Not before (current time)
            'exp' => time() + (6 * 60 * 60), // Expires in 6 hours
            'sub' => $participantIdentity,
            'video' => $grants,
        ];

        try {
            $token = JWT::encode($payload, $apiSecret, 'HS256');
            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            \Log::error("Error generating LiveKit token: " . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
