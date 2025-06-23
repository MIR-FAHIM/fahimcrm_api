<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Notifications;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationController extends Controller

{

    public function sendNotification(Request $request)

    {

        // Initialize Firebase with service account credentials

        $firebase = (new Factory)->withServiceAccount(env('FIREBASE_CREDENTIALS'));

        // Get Firebase Messaging instance

        $messaging = $firebase->createMessaging();

        // Get the device token from the request

        $deviceToken = $request->input('device_token');

        // Ensure the device token is valid

        if (empty($deviceToken)) {

            return response()->json(['error' => 'Device token is missing'], 400);
        }

        // Create the notification

        $notification = Notification::create('Test Title', 'Test Body');

        // Build the message

        $message = CloudMessage::withTarget('token', $deviceToken)->withNotification($notification);

        try {

            // Send the notification

            $messaging->send($message);

            return response()->json(['message' => 'Notification sent successfully']);
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {

            return response()->json(['error' => 'Invalid message: ' . $e->getMessage()], 400);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Error sending notification: ' . $e->getMessage()], 500);
        }
    }

    public function getNotificationsByUser($user_id)
    {
        // Validate if user_id exists in the database (optional)
        // You can skip this step if you assume user_id is always valid

        $notifications = Notifications::where('user_id', $user_id)
            ->orderBy('created_at', 'desc') // Optional: Order by created_at (latest first)
            ->get();

        // Check if notifications are found
        if ($notifications->isEmpty()) {
            return response()->json([
                'message' => 'No notifications found for this user.'
            ], 404);
        }

        // Return notifications in JSON format
        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ], 200);
    }
    public function addNotification($title, $subtitle, $user_id, )
    {
        // Create a new notification
        $notification = Notifications::create([
            'title' => $title,
            'subtitle' => $subtitle,
            'type' => 'task',
            'user_id' => $user_id,
            'is_seen' => false,
            'send_push' => false,
        ]);

        // Return the created notification
        return $notification;
    }
    
    public function addNotificationApi(Request $request)
    {
        // Create a new notification
        $notification = Notifications::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'type' => $request->type,
            'user_id' => $request->user_id,
            'is_seen' => false,
            'send_push' => false,
        ]);

        // Return the created notification
        return response()->json([
            'status'=> 'success',
            'message'=> 'notification added successfully',
            'data' => $notification]);
    }
    
}
