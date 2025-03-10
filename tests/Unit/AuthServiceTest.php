<?php

namespace Tests\Unit;

use App\Services\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();

        // Giả lập email & Redis key
        $email = 'test@example.com';
        $redisKey = "password_reset:{$email}";
        $otp = '123456';

        // Mock Redis::set()
        Redis::shouldReceive('set')
            ->with($redisKey, $otp)
            ->andReturn(true);

        // Mock Redis::expire()
        Redis::shouldReceive('expire')
            ->with($redisKey, 300)
            ->andReturn(true);

        // Mock Redis::get()
        Redis::shouldReceive('get')
            ->with($redisKey)
            ->andReturn($otp);

        // Mock Redis::del()
        Redis::shouldReceive('del')
            ->with($redisKey)
            ->andReturn(1);
    }

    #[Test]
    public function it_can_register_a_user()
    {
        $data = [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john.doe@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->authService->register($data);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('access_token', $response);
        $this->assertDatabaseHas('users', ['email' => $data['email']]);
    }

    #[Test]
    public function it_cannot_register_with_existing_email()
    {
        $existingUser = User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $data = [
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'duplicate@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->authService->register($data);

        $this->assertEquals(422, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    #[Test]
    public function it_can_login_a_user()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->authService->login([
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('access_token', $response);
    }

    #[Test]
    public function it_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email'    => 'invalid@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->authService->login([
            'email'    => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    #[Test]
    public function it_can_send_forgot_password_otp()
    {
        Mail::fake(); // Giả lập gửi mail

        // Giả lập Redis key với prefix chuẩn
        $redisKey = "password_reset:test@example.com";
        $otp = 508676; // Hoặc Mockery::type('int')

        // Cập nhật mock Redis để khớp với AuthService.php
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function ($key, $ttl, $value) use ($redisKey) {
                return $key === $redisKey && $ttl === 600 && is_numeric($value);
            });

        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->authService->sendPasswordResetOtp('test@example.com');

        $this->assertEquals(200, $response['status']);

        // Kiểm tra email đã được gửi đi
        Mail::assertSent(function (\Illuminate\Mail\Mailable $mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Kiểm tra không có mail nào bị gửi nếu email không tồn tại
        Mail::assertNotSent(\Illuminate\Mail\Mailable::class, function ($mail) {
            return $mail->hasTo('wrong@example.com');
        });
    }

    #[Test]
    public function it_cannot_send_otp_if_email_not_found()
    {
        Mail::fake();

        $response = $this->authService->sendPasswordResetOtp('notfound@example.com');

        $this->assertEquals(404, $response['status']);
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_can_reset_password()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $redisKey = "password_reset:test@example.com";

        // Mock Redis::get()
        Redis::shouldReceive('get')
            ->once()
            ->with($redisKey)
            ->andReturn($otp);

        // Mock Redis::del()
        Redis::shouldReceive('del')
            ->once()
            ->with($redisKey)
            ->andReturn(1);

        $response = $this->authService->resetPassword([
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Password reset successfully.', $response['message']);

        // Kiểm tra mật khẩu đã đổi
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    #[Test]
    public function it_cannot_reset_password_with_invalid_otp()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $invalidOtp = '999999';
        $redisKey = "password_reset:test@example.com";

        // Mock Redis trả về OTP hợp lệ nhưng khác với OTP nhập vào
        Redis::shouldReceive('get')
            ->once()
            ->with($redisKey)
            ->andReturn('123456');

        $response = $this->authService->resetPassword([
            'email' => 'test@example.com',
            'otp' => $invalidOtp,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('OTP không hợp lệ.', $response['message']);
    }

    #[Test]
    public function it_cannot_reset_password_if_email_not_found()
    {
        $response = $this->authService->resetPassword([
            'email' => 'notfound@example.com',
            'otp' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $this->assertEquals(404, $response['status']);
    }
}
