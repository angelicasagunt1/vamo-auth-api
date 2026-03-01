<?php

declare(strict_types=1);

namespace App\Auth\Application\Dto;

use App\Auth\Domain\Entity\Identity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ProviderLoginRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice([Identity::PROVIDER_EMAIL, Identity::PROVIDER_GOOGLE, Identity::PROVIDER_APPLE])]
    public ?string $provider = null;

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Regex(pattern: '/^\+?[1-9]\d{7,14}$/', message: 'Phone must be in E.164 format.')]
    public ?string $phone = null;

    #[Assert\Length(min: 8, max: 255)]
    public ?string $password = null;

    #[Assert\Length(min: 10)]
    public ?string $token = null;

    #[Assert\Callback]
    public function validatePayload(ExecutionContextInterface $context): void
    {
        if ($this->provider === null) {
            return;
        }

        $isEmailProvider = $this->provider === Identity::PROVIDER_EMAIL;
        $isSocialProvider = $this->provider === Identity::PROVIDER_GOOGLE || $this->provider === Identity::PROVIDER_APPLE;

        if ($isEmailProvider) {
            if ($this->email === null || $this->email === '' || $this->password === null || $this->password === '') {
                $context->buildViolation('Email and password are required for email login.')
                    ->atPath('email')
                    ->addViolation();
            }
        }

        if ($isSocialProvider) {
            if ($this->token === null || $this->token === '') {
                $context->buildViolation('Token is required for social login.')
                    ->atPath('token')
                    ->addViolation();
            }
        }
    }
}
