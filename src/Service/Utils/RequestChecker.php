<?php

declare(strict_types=1);

namespace App\Service\Utils;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RequestChecker
{
    public function checkRequiredFields(array $data, array $requiredFields): ?JsonResponse
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!\array_key_exists($field, $data) || '' === $data[$field] || null === $data[$field]) {
                $missing[] = $field;
            }
        }

        if ([] !== $missing) {
            return $this->error(sprintf('Missing or empty required field(s): %s.', implode(', ', $missing)));
        }

        return null;
    }
    public function checkEmail(mixed $email): ?JsonResponse
    {
        if (!\is_string($email) || false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->error('The "to" field must be a valid email address.');
        }

        return null;
    }

    public function checkUrl(mixed $url): ?JsonResponse
    {
        if (!\is_string($url) || false === filter_var($url, \FILTER_VALIDATE_URL)) {
            return $this->error('The "shareUrl" field must be a valid URL.');
        }

        return null;
    }

    private function error(string $message): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $message,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
