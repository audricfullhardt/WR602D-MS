<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MailControllerTest extends WebTestCase
{
    private const SCORE_CREATED_URI = '/mail/score-created';
    private const SCORE_SHARED_URI = '/mail/score-shared';

    public function testScoreCreatedWithoutApiKeyReturnsUnauthorized(): void
    {
        $client = static::createClient();

        $this->postJson($client, self::SCORE_CREATED_URI, $this->validCreatedPayload(), withApiKey: false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testScoreCreatedWithWrongApiKeyReturnsUnauthorized(): void
    {
        $client = static::createClient();

        $this->postJson(
            $client,
            self::SCORE_CREATED_URI,
            $this->validCreatedPayload(),
            apiKeyValue: 'definitely-wrong-key',
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testScoreCreatedWithMissingFieldsReturnsUnprocessable(): void
    {
        $client = static::createClient();

        $this->postJson($client, self::SCORE_CREATED_URI, ['to' => 'player@example.com']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testScoreCreatedWithInvalidEmailReturnsUnprocessable(): void
    {
        $client = static::createClient();

        $payload = $this->validCreatedPayload();
        $payload['to'] = 'not-an-email';

        $this->postJson($client, self::SCORE_CREATED_URI, $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testScoreCreatedWithValidPayloadReturnsAccepted(): void
    {
        $client = static::createClient();

        $this->postJson($client, self::SCORE_CREATED_URI, $this->validCreatedPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testScoreSharedWithValidPayloadReturnsAccepted(): void
    {
        $client = static::createClient();

        $this->postJson($client, self::SCORE_SHARED_URI, $this->validSharedPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    private function validCreatedPayload(): array
    {
        return [
            'to' => 'player@example.com',
            'username' => 'player1',
            'strokes' => 4,
            'holeNumber' => 3,
        ];
    }

    private function validSharedPayload(): array
    {
        return [
            'to' => 'friend@example.com',
            'username' => 'player1',
            'strokes' => 4,
            'holeNumber' => 3,
            'shareUrl' => 'https://minigolf.example/s/abc123',
        ];
    }

    private function postJson(
        KernelBrowser $client,
        string $uri,
        array $payload,
        bool $withApiKey = true,
        ?string $apiKeyValue = null,
    ): void {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($withApiKey) {
            $server[$this->apiKeyServerKey()] = $apiKeyValue ?? $this->apiKeyValue();
        }

        $client->request(
            Request::METHOD_POST,
            $uri,
            server: $server,
            content: json_encode($payload, \JSON_THROW_ON_ERROR),
        );
    }

    private function apiKeyServerKey(): string
    {
        return 'HTTP_'.strtoupper(str_replace('-', '_', $this->apiKeyHeader()));
    }

    private function apiKeyHeader(): string
    {
        return $_ENV['API_KEY_HEADER'] ?? 'X-API-KEY';
    }

    private function apiKeyValue(): string
    {
        return $_ENV['API_KEY_VALUE'] ?? '';
    }
}
