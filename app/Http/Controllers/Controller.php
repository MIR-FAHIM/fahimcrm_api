<?php

namespace App\Http\Controllers;

use App\Models\UserActivityTracker;
use Illuminate\Http\Request;
use Throwable;

abstract class Controller
{
    protected function recordUserActivity(Request $request, $userId, string $activityName, ?string $details = null, ?string $type = null): void
    {
        if (!$userId) {
            return;
        }

        try {
            $platform = $request->input('platform', $request->header('X-Platform', 'web'));
            $platform = in_array($platform, ['web', 'app'], true) ? $platform : 'web';

            UserActivityTracker::create([
                'user_id' => $userId,
                'activity_name' => $activityName,
                'details' => $details,
                'type' => $type,
                'platform' => $platform,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
