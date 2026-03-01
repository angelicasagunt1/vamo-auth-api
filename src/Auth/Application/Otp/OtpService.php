<?php

declare(strict_types=1);

namespace App\Auth\Application\Otp;

use App\Auth\Application\Sms\SmsSenderInterface;
use App\Auth\Domain\Entity\OtpCode;
use App\Auth\Infrastructure\Doctrine\Repository\OtpCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class OtpService
{
    private const OTP_TTL_SECONDS = 300;
    private const OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly OtpCodeRepository $otpRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SmsSenderInterface $smsSender,
        #[Autowire('%env(OTP_SECRET)%')]
        private readonly string $otpSecret,
        #[Autowire('%env(APP_ENV)%')]
        private readonly string $appEnv
    ) {
    }

    /**
     * @return array{phone:string, expires_at:string, dev_code:?string}
     */
    public function request(string $phone): array
    {
        $this->deleteExisting($phone);

        $code = (string) random_int(100000, 999999);
        $expiresAt = new \DateTimeImmutable(sprintf('+%d seconds', self::OTP_TTL_SECONDS), new \DateTimeZone('UTC'));
        $hash = $this->hashCode($phone, $code);

        $otp = new OtpCode($phone, $hash, $expiresAt);
        $this->entityManager->persist($otp);
        $this->entityManager->flush();

        $this->smsSender->send($phone, sprintf('Your verification code is %s', $code));

        return [
            'phone' => $phone,
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'dev_code' => $this->appEnv === 'dev' ? $code : null,
        ];
    }

    public function verify(string $phone, string $code): void
    {
        $otp = $this->otpRepository->findOneBy(['phone' => $phone]);
        if ($otp === null) {
            throw new \RuntimeException('OTP not found.');
        }

        if ($otp->isExpired()) {
            $this->entityManager->remove($otp);
            $this->entityManager->flush();
            throw new \RuntimeException('OTP expired.');
        }

        if ($otp->getAttempts() >= self::OTP_MAX_ATTEMPTS) {
            $this->entityManager->remove($otp);
            $this->entityManager->flush();
            throw new \RuntimeException('OTP attempts exceeded.');
        }

        $otp->incrementAttempts();
        $valid = hash_equals($otp->getCodeHash(), $this->hashCode($phone, $code));

        if (!$valid) {
            $this->entityManager->flush();
            throw new \RuntimeException('Invalid OTP code.');
        }

        $this->entityManager->remove($otp);
        $this->entityManager->flush();
    }

    private function deleteExisting(string $phone): void
    {
        $existing = $this->otpRepository->findBy(['phone' => $phone]);
        foreach ($existing as $otp) {
            $this->entityManager->remove($otp);
        }
        $this->entityManager->flush();
    }

    private function hashCode(string $phone, string $code): string
    {
        return hash('sha256', $phone . '|' . $code . '|' . $this->otpSecret);
    }
}
