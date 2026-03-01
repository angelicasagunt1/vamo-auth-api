<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Sms;

use App\Auth\Application\Sms\SmsSenderInterface;
use Psr\Log\LoggerInterface;

final class NullSmsSender implements SmsSenderInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(string $phone, string $message): void
    {
        $this->logger->info('SMS delivery skipped.', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
