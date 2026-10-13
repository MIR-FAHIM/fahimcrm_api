<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetConfirmationMail;
use App\Mail\ResetPasswordOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\ApiErrorLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PasswordResetOtpController extends Controller
{
    /**
     * Maximum failed verification attempts permitted per OTP record.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * OTP lifespan in minutes.
     */
    private const OTP_EXPIRY_MINUTES = 5;

    /**
     * Resend cooldown period in seconds.
     */
    private const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Handle forgot password request and send 6-digit OTP email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $ip = $request->ip() ?? 'unknown';

        $ipThrottleKey = 'forgot-password:ip:' . $ip;
        $emailCooldownKey = 'forgot-password:cooldown:' . sha1($email);

        // Global rate limit by IP: max 20 requests per minute
        if (RateLimiter::tooManyAttempts($ipThrottleKey, 20)) {
            $seconds = RateLimiter::availableIn($ipThrottleKey);
            return response()->json([
                'status' => 'error',
                'message' => "Too many requests from this IP. Please try again in {$seconds} seconds.",
            ], 429);
        }

        // 60-second resend cooldown applied to both registered and unregistered emails (prevents enumeration)
        if (RateLimiter::tooManyAttempts($emailCooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($emailCooldownKey);
            return response()->json([
                'status' => 'error',
                'message' => "Please wait {$seconds} seconds before requesting another verification code.",
            ], 429);
        }

        // Check database-backed cooldown in case cache was flushed
        $recentOtp = PasswordResetOtp::where('email', $email)
            ->where('last_sent_at', '>', Carbon::now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
            ->latest()
            ->first();

        if ($recentOtp) {
            $seconds = self::RESEND_COOLDOWN_SECONDS - Carbon::now()->diffInSeconds($recentOtp->last_sent_at);
            if ($seconds > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Please wait {$seconds} seconds before requesting another verification code.",
                ], 429);
            }
        }

        RateLimiter::hit($ipThrottleKey, 60);
        RateLimiter::hit($emailCooldownKey, self::RESEND_COOLDOWN_SECONDS);

        $user = User::where('email', $email)->first();

        if ($user && $user->isActive) {
            try {
                // Invalidate all previous unused OTPs for this user
                PasswordResetOtp::where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => Carbon::now()]);

                // Generate cryptographically secure 6-digit OTP
                $otp = sprintf('%06d', random_int(0, 999999));

                // Store hashed OTP
                PasswordResetOtp::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                    'attempts' => 0,
                    'last_sent_at' => Carbon::now(),
                ]);

                // Dispatch email
                Mail::to($user->email)->send(
                    new ResetPasswordOtpMail(
                        otp: $otp,
                        expiresMinutes: self::OTP_EXPIRY_MINUTES,
                        userName: $user->name
                    )
                );
            } catch (Throwable $e) {
                // Silently log delivery errors without leaking user existence
                ApiErrorLogService::logException($e, $request);
            }
        }

        // Uniform response for both existing and non-existing emails to prevent enumeration
        return response()->json([
            'status' => 'success',
            'message' => 'If this email is registered, a password reset code has been sent.',
        ], 200);
    }

    /**
     * Handle reset password request with OTP verification.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $otp = (string) $request->input('otp');
        $newPassword = (string) $request->input('password');
        $ip = $request->ip() ?? 'unknown';

        $verifyRateKey = 'reset-password:verify:' . sha1($email) . ':' . $ip;

        // Rate limit verification attempts: max 10 per 5 minutes per email/IP
        if (RateLimiter::tooManyAttempts($verifyRateKey, 10)) {
            $seconds = RateLimiter::availableIn($verifyRateKey);
            return response()->json([
                'status' => 'error',
                'message' => "Too many verification attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($verifyRateKey, 300);

        $genericErrorMessage = 'Invalid or expired verification code.';

        try {
            /** @var User|null $updatedUser */
            $updatedUser = DB::transaction(function () use ($email, $otp, $newPassword) {
                $user = User::where('email', $email)->lockForUpdate()->first();

                if (!$user) {
                    return null;
                }

                // Retrieve the latest unused OTP record with lock to prevent race conditions
                $otpRecord = PasswordResetOtp::where('user_id', $user->id)
                    ->where('email', $user->email)
                    ->whereNull('used_at')
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (!$otpRecord) {
                    return null;
                }

                // Check expiration
                if ($otpRecord->expires_at->isPast()) {
                    return null;
                }

                // Check attempts limit
                if ($otpRecord->attempts >= self::MAX_ATTEMPTS) {
                    return null;
                }

                // Verify OTP hash
                if (!Hash::check($otp, $otpRecord->otp_hash)) {
                    $otpRecord->increment('attempts');
                    return null;
                }

                // Mark current OTP as consumed
                $otpRecord->update([
                    'used_at' => Carbon::now(),
                ]);

                // Invalidate any other active reset codes for this user
                PasswordResetOtp::where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => Carbon::now()]);

                // Update password and invalidate active token sessions
                $user->password = Hash::make($newPassword);
                $user->app_token = null;
                $user->save();

                return $user;
            });

            if (!$updatedUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => $genericErrorMessage,
                ], 400);
            }

            // Clear verification rate limit on success
            RateLimiter::clear($verifyRateKey);

            // Send confirmation email (failure must not undo the password reset)
            try {
                Mail::to($updatedUser->email)->send(
                    new PasswordResetConfirmationMail(
                        userName: $updatedUser->name,
                        resetTime: Carbon::now()->toDayDateTimeString()
                    )
                );
            } catch (Throwable $mailException) {
                ApiErrorLogService::logException($mailException, $request);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully. Please log in with your new password.',
            ], 200);
        } catch (Throwable $e) {
            ApiErrorLogService::logException($e, $request);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while resetting the password.',
            ], 500);
        }
    }
}
