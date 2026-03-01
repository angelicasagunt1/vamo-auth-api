<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Exception\AuthenticationFailed;
use App\Auth\Application\Exception\ProviderNotConfigured;
use App\Auth\Application\Exception\ProviderNotSupported;
use App\Auth\Application\Service\LoginWithProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LoginController
{
    public function __construct(
        private readonly LoginWithProvider $loginWithProvider,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route(path: '/auth/login', name: 'auth_login', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new ProviderLoginRequest();
        $dto->provider = isset($data['provider']) ? (string) $data['provider'] : null;
        $dto->email = isset($data['email']) ? (string) $data['email'] : null;
        $dto->phone = isset($data['phone']) ? (string) $data['phone'] : null;
        $dto->password = isset($data['password']) ? (string) $data['password'] : null;
        $dto->token = isset($data['token']) ? (string) $data['token'] : null;

        $errors = $this->validator->validate($dto);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'error' => 'Validation failed.',
                'details' => $this->formatErrors($errors),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->loginWithProvider->login($dto);
        } catch (AuthenticationFailed $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (ProviderNotSupported | ProviderNotConfigured $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }

    private function formatErrors(iterable $errors): array
    {
        $details = [];
        foreach ($errors as $error) {
            $field = $error->getPropertyPath() !== '' ? $error->getPropertyPath() : 'payload';
            $details[$field][] = $error->getMessage();
        }

        return $details;
    }
}
