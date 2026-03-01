<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Dto\RegisterRequest;
use App\Auth\Application\Exception\IdentityAlreadyExists;
use App\Auth\Application\Service\RegisterUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterController
{
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route(path: '/auth/register', name: 'auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new RegisterRequest();
        $dto->email = isset($data['email']) ? (string) $data['email'] : null;
        $dto->phone = isset($data['phone']) ? (string) $data['phone'] : null;
        $dto->password = isset($data['password']) ? (string) $data['password'] : null;

        $errors = $this->validator->validate($dto);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'error' => 'Validation failed.',
                'details' => $this->formatErrors($errors),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->registerUser->register($dto);
        } catch (IdentityAlreadyExists $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, JsonResponse::HTTP_CREATED);
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
