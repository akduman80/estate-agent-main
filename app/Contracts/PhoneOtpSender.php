<?php

namespace App\Contracts;

interface PhoneOtpSender
{
    public function send(string $phone, string $code): void;
}
