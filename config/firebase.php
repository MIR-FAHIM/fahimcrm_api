<?php

return [
    'credentials' => [
        'file' => base_path('config/firebase_credintials.json'),
    ],
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'database_url' => env('FIREBASE_DATABASE_URL'),
    'api_key' => env('FIREBASE_API_KEY'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
];
