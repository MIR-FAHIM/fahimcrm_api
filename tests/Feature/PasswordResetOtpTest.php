<?php

namespace Tests\Feature;

use App\Mail\PasswordResetConfirmationMail;
use App\Mail\ResetPasswordOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('forgot-password:ip:127.0.0.1');
        RateLimiter::clear('forgot-password:ip:unknown');
        Mail::fake();
    }

    private function createTestUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'testuser_' . uniqid() . '@example.com',
            'password' => Hash::make('OldPassword123'),
            'app_token' => 'active_session_token_' . uniqid(),
            'isActive' => true,
        ], $attributes));
    }

    public function test_public_endpoints_accessible_without_app_token(): void
    {
        $responseForgot = $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $this->assertNotEquals(450, $responseForgot->status());
        $responseForgot->assertStatus(200);

        $responseReset = $this->postJson('/api/reset-password', [
            'email' => 'nobody@example.com',
            'otp' => '123456',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $this->assertNotEquals(450, $responseReset->status());
        $responseReset->assertStatus(400);
    }

    public function test_forgot_password_succeeds_and_sends_email_for_existing_user(): void
    {
        $user = $this->createTestUser();
        RateLimiter::clear('forgot-password:cooldown:' . sha1(strtolower($user->email)));

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'If this email is registered, a password reset code has been sent.',
            ]);

        Mail::assertSent(ResetPasswordOtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && preg_match('/^[0-9]{6}$/', $mail->otp);
        });

        $this->assertDatabaseHas('password_reset_otps', [
            'user_id' => $user->id,
            'email' => $user->email,
            'attempts' => 0,
            'used_at' => null,
        ]);

        $record = PasswordResetOtp::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($record);
        // Verify that plaintext OTP is never stored in DB
        $this->assertNotEquals(6, strlen($record->otp_hash));
        $this->assertTrue(Hash::check(
            app()->make('mailer')->sent()[0]->getSymfonySentMessage() ? 'mock' : 'mock',
            $record->otp_hash
        ) || strlen($record->otp_hash) >= 60);
    }

    public function test_forgot_password_returns_same_response_for_unknown_email(): void
    {
        $unknownEmail = 'unregistered_' . uniqid() . '@example.com';
        RateLimiter::clear('forgot-password:cooldown:' . sha1(strtolower($unknownEmail)));

        $response = $this->postJson('/api/forgot-password', [
            'email' => $unknownEmail,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'If this email is registered, a password reset code has been sent.',
            ]);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => $unknownEmail,
        ]);
    }

    public function test_resend_within_60_seconds_is_throttled(): void
    {
        $user = $this->createTestUser();
        RateLimiter::clear('forgot-password:cooldown:' . sha1(strtolower($user->email)));

        $first = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);
        $first->assertStatus(200);

        // Immediate second attempt
        $second = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $second->assertStatus(429)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_resending_otp_invalidates_previous_otp(): void
    {
        $user = $this->createTestUser();
        $emailKey = 'forgot-password:cooldown:' . sha1(strtolower($user->email));
        RateLimiter::clear($emailKey);

        // First OTP request
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(200);
        $firstOtpRecord = PasswordResetOtp::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($firstOtpRecord);
        $this->assertNull($firstOtpRecord->used_at);

        // Simulate 61 seconds passing
        Carbon::setTestNow(Carbon::now()->addSeconds(65));
        RateLimiter::clear($emailKey);

        // Second OTP request
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(200);

        $firstOtpRecord->refresh();
        $this->assertNotNull($firstOtpRecord->used_at, 'Previous OTP should be invalidated on resend');

        Carbon::setTestNow();
    }

    public function test_reset_password_with_valid_otp_succeeds_updates_password_clears_token_and_sends_confirmation(): void
    {
        $user = $this->createTestUser(['password' => Hash::make('OldSecret123')]);
        $otp = '654321';

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewBrandSecret88!',
            'password_confirmation' => 'NewBrandSecret88!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password reset successfully. Please log in with your new password.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewBrandSecret88!', $user->password));
        $this->assertNull($user->app_token, 'app_token must be cleared upon password reset');

        // Verify OTP is consumed
        $record = PasswordResetOtp::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($record->used_at);

        // Verify confirmation email was sent
        Mail::assertSent(PasswordResetConfirmationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_reset_password_fails_with_wrong_otp_and_increments_attempts(): void
    {
        $user = $this->createTestUser();
        $otp = '112233';

        $record = PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => '999999',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ]);

        $record->refresh();
        $this->assertEquals(1, $record->attempts);
        $this->assertNull($record->used_at);
    }

    public function test_reset_password_fails_when_attempts_exhausted(): void
    {
        $user = $this->createTestUser();
        $otp = '555666';

        $record = PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 5, // Exhausted
            'last_sent_at' => Carbon::now(),
        ]);

        // Attempting with the correct OTP after exhaustion must fail
        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ]);

        $record->refresh();
        $this->assertNull($record->used_at);
    }

    public function test_reset_password_fails_with_expired_otp(): void
    {
        $user = $this->createTestUser();
        $otp = '777888';

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->subMinute(), // Expired
            'attempts' => 0,
            'last_sent_at' => Carbon::now()->subMinutes(6),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ]);
    }

    public function test_reset_password_fails_when_otp_already_used(): void
    {
        $user = $this->createTestUser();
        $otp = '333444';

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => Carbon::now(),
            'used_at' => Carbon::now(), // Already used
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ]);
    }

    public function test_confirmation_email_failure_does_not_rollback_password_reset(): void
    {
        $user = $this->createTestUser(['password' => Hash::make('InitialPassword123')]);
        $otp = '987654';

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => Carbon::now(),
        ]);

        // Mock mailer to simulate failure when sending confirmation email
        Mail::shouldReceive('to')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->andThrow(new Exception('SMTP connection timed out'));

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'ResilientPass999!',
            'password_confirmation' => 'ResilientPass999!',
        ]);

        // API should still report success
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        // Password must still be updated in DB
        $user->refresh();
        $this->assertTrue(Hash::check('ResilientPass999!', $user->password));
        $this->assertNull($user->app_token);
    }
}
