<?php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-email', function () {
    $toEmail = 'ridoyfahim92@gmail.com';

    try {
        Mail::raw('This is a test email from my Laravel CRM.', function ($message) use ($toEmail) {
            $message->to($toEmail)
                    ->subject('Test Email from Laravel CRM');
        });

        return 'Test email sent successfully to ' . $toEmail;
    } catch (\Exception $e) {
        return 'Failed to send email: ' . $e->getMessage();
    }
});