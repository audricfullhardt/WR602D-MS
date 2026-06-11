<?php

declare(strict_types=1);

namespace App\Email;

use Symfony\Component\Mime\Email;

final class ScoreCreatedEmailBuilder implements EmailBuilderInterface
{
    public function build(array $payload): Email
    {
        $username = (string) $payload['username'];
        $strokes = (int) $payload['strokes'];
        $holeNumber = (int) $payload['holeNumber'];

        return (new Email())
            ->to((string) $payload['to'])
            ->subject(sprintf('Nouveau score enregistré — trou %d', $holeNumber))
            ->text(sprintf(
                "Bonjour %s,\n\nVotre score vient d'être enregistré : %d coup(s) sur le trou n°%d.\n\nBonne partie !",
                $username,
                $strokes,
                $holeNumber,
            ))
            ->html(sprintf(
                '<h1>⛳ Score enregistré !</h1>'
                .'<p>Bonjour <strong>%s</strong>,</p>'
                .'<p>Votre score vient d\'être enregistré : <strong>%d</strong> coup(s) '
                .'sur le trou <strong>n°%d</strong>.</p>'
                .'<p>Bonne partie !</p>',
                htmlspecialchars($username, \ENT_QUOTES),
                $strokes,
                $holeNumber,
            ));
    }
}
