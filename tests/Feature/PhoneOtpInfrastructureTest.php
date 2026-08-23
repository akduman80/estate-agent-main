<?php

namespace Tests\Feature;

use App\Contracts\PhoneOtpSender;
use App\Models\PhoneOtpCode;
use App\Models\User;
use App\Services\PhoneOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneOtpInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_otp_uses_replaceable_sender_and_marks_phone_verified(): void
    {
        $sender = new class implements PhoneOtpSender
        {
            public string $code = '';

            public function send(string $phone, string $code): void
            {
                $this->code = $code;
            }
        };
        $this->app->instance(PhoneOtpSender::class, $sender);
        $user = User::factory()->create(['phone' => '905551112233', 'phone_verified_at' => null]);

        $service = $this->app->make(PhoneOtpService::class);
        $service->issue($user, '127.0.0.1');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sender->code);
        $this->assertDatabaseCount('phone_otp_codes', 1);

        $service->verify($user, $sender->code);
        $this->assertNotNull($user->refresh()->phone_verified_at);
        $this->assertNotNull(PhoneOtpCode::first()->consumed_at);
    }
}
