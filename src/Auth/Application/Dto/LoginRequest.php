<?php

declare(strict_types=1);

namespace App\Auth\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class LoginRequest
{
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Regex(pattern: '/^\+?[1-9]\d{7,14}$/', message: 'Phone must be in E.164 format.')]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public ?string $password = null;

    #[Assert\Callback]
    public function validateIdentifier(ExecutionContextInterface $context): void
    {
        $hasEmail = $this->email !== null && $this->email !== '';
        $hasPhone = $this->phone !== null && $this->phone !== '';

        if ($hasEmail === $hasPhone) {
            $context->buildViolation('Provide exactly one of email or phone.')
                ->atPath('email')
                ->addViolation();
        }
    }
}
