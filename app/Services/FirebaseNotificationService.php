<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
class FirebaseNotificationService
{
 /**
     * Send a push notification via FCM.
     *
     * @param string $fcmToken The FCM token of the target device.
     * @param string $title The title of the notification.
     * @param string $body The body of the notification.
     * @param array $data Custom data payload (optional).
     * @return array
     */
    public static function sendPushNotification($fcmToken, $title, $body, $data = [])
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('firebase_credentials.json'));
            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $response = $messaging->send($message);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Push notification sent successfully',
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Failed to send push notification',
                'errors' => $e->getMessage(),
            ];
        }
    }
}
