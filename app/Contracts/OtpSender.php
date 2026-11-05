<?php

namespace App\Contracts;

interface OtpSender
{
    public function send(string $mobile, string $code): void;
}
