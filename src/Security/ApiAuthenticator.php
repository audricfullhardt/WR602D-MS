<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly string $apiKeyHeader,
        private readonly string $apiKeyValue,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $providedKey = $request->headers->get($this->apiKeyHeader);

        if (null === $providedKey || '' === $providedKey) {
            throw new CustomUserMessageAuthenticationException('Missing API key.');
        }

        if ('' === $this->apiKeyValue || !hash_equals($this->apiKeyValue, $providedKey)) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        return new SelfValidatingPassport(new UserBadge('api_user'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
