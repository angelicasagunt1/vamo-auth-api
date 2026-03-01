<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Otp\OtpService;
use App\Auth\Domain\Entity\Identity;
use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OtpController
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly IdentityRepository $identityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route(path: '/auth/otp/request', name: 'auth_otp_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $phone = isset($data['phone']) ? (string) $data['phone'] : '';
        $errors = $this->validator->validate($phone, [
            new Assert\NotBlank(),
            new Assert\Regex(pattern: '/^\+?[1-9]\d{7,14}$/', message: 'Phone must be in E.164 format.'),
        ]);

        if ($errors->count() > 0) {
            return new JsonResponse(['error' => 'Validation failed.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $payload = $this->otpService->request($phone);

        return new JsonResponse($payload, JsonResponse::HTTP_OK);
    }

    #[Route(path: '/auth/otp/verify', name: 'auth_otp_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $phone = isset($data['phone']) ? (string) $data['phone'] : '';
        $code = isset($data['code']) ? (string) $data['code'] : '';

        $errors = $this->validator->validate($phone, [
            new Assert\NotBlank(),
            new Assert\Regex(pattern: '/^\+?[1-9]\d{7,14}$/', message: 'Phone must be in E.164 format.'),
        ]);

        if ($errors->count() > 0 || $code === '') {
            return new JsonResponse(['error' => 'Validation failed.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->otpService->verify($phone, $code);
        } catch (\RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $identity = $this->identityRepository->findOneBy([
            'provider' => Identity::PROVIDER_PHONE,
            'identifier' => $phone,
        ]);

        if ($identity === null) {
            $user = new User();
            $identity = new Identity(Identity::PROVIDER_PHONE, $phone);
            $user->addIdentity($identity);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $payload = [
            'user_id' => $identity->getUser()?->getId(),
            'identity_id' => $identity->getId(),
            'provider' => $identity->getProvider(),
        ];

        $jwtUser = JWTUser::createFromPayload($phone, $payload);
        $token = $this->jwtManager->create($jwtUser);

        return new JsonResponse(['token' => $token]);
    }
}
