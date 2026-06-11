<?php

declare(strict_types=1);

namespace App\Email;

use Symfony\Component\Mime\Email;

final class ScoreSharedEmailBuilder implements EmailBuilderInterface
{
    public function build(array $payload): Email
    {
        $username = (string) $payload['username'];
        $strokes = (int) $payload['strokes'];
        $holeNumber = (int) $payload['holeNumber'];
        $shareUrl = (string) $payload['shareUrl'];

        return (new Email())
            ->to((string) $payload['to'])
            ->subject(sprintf('%s a partagé un score avec vous', $username))
            ->text(sprintf(
                "Bonjour,\n\n%s a réalisé %d coup(s) sur le trou n°%d et souhaite vous le partager.\n\nVoir le score : %s",
                $username,
                $strokes,
                $holeNumber,
                $shareUrl,
            ))
            ->html(sprintf(
                '<h1>⛳ %s partage un score avec vous !</h1>'
                .'<p><strong>%s</strong> a réalisé <strong>%d</strong> coup(s) '
                .'sur le trou <strong>n°%d</strong> et souhaite vous le partager.</p>'
                .'<p><a href="%s" style="display:inline-block;padding:12px 20px;'
                .'background:#2e7d32;color:#fff;text-decoration:none;border-radius:4px;">Voir le score</a></p>'
                .'<p style="font-size:12px;color:#888;">Lien : %s</p>',
                htmlspecialchars($username, \ENT_QUOTES),
                htmlspecialchars($username, \ENT_QUOTES),
                $strokes,
                $holeNumber,
                htmlspecialchars($shareUrl, \ENT_QUOTES),
                htmlspecialchars($shareUrl, \ENT_QUOTES),
            ));
    }
}
