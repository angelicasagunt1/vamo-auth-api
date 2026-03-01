<?php

declare(strict_types=1);

namespace App\Auth\Application\Sms;

interface SmsSenderInterface
{
    public function send(string $phone, string $message): void;
}
