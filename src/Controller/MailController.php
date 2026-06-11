<?php

declare(strict_types=1);

namespace App\Controller;

use App\Email\ScoreCreatedEmailBuilder;
use App\Email\ScoreSharedEmailBuilder;
use App\Service\MailerService;
use App\Service\Utils\RequestChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mail', name: 'mail_')]
final class MailController extends AbstractController
{
    #[Route('/score-created', name: 'score_created', methods: [Request::METHOD_POST])]
    public function scoreCreated(
        Request $request,
        RequestChecker $requestChecker,
        MailerService $mailerService,
        ScoreCreatedEmailBuilder $emailBuilder,
    ): JsonResponse {
        $data = $this->decodePayload($request);

        $error = $requestChecker->checkRequiredFields($data, ['to', 'username', 'strokes', 'holeNumber'])
            ?? $requestChecker->checkEmail($data['to'] ?? null);

        if (null !== $error) {
            return $error;
        }

        $mailerService->send($emailBuilder, $data);

        return $this->json([
            'status' => 'sent',
            'type' => 'score-created',
            'to' => $data['to'],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/score-shared', name: 'score_shared', methods: [Request::METHOD_POST])]
    public function scoreShared(
        Request $request,
        RequestChecker $requestChecker,
        MailerService $mailerService,
        ScoreSharedEmailBuilder $emailBuilder,
    ): JsonResponse {
        $data = $this->decodePayload($request);

        $error = $requestChecker->checkRequiredFields($data, ['to', 'username', 'strokes', 'holeNumber', 'shareUrl'])
            ?? $requestChecker->checkEmail($data['to'] ?? null)
            ?? $requestChecker->checkUrl($data['shareUrl'] ?? null);

        if (null !== $error) {
            return $error;
        }

        $mailerService->send($emailBuilder, $data);

        return $this->json([
            'status' => 'sent',
            'type' => 'score-shared',
            'to' => $data['to'],
        ], Response::HTTP_ACCEPTED);
    }

    private function decodePayload(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return \is_array($data) ? $data : [];
    }
}
