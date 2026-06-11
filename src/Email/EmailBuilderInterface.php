<?php

declare(strict_types=1);

namespace App\Email;

use Symfony\Component\Mime\Email;

interface EmailBuilderInterface
{
    public function build(array $payload): Email;
}
