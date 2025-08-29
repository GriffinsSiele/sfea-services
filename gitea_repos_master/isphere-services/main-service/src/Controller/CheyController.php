<?php

declare(strict_types=1);

namespace App\Controller;

use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Deprecated]
#[Route('/chey', name: self::NAME, methods: [Request::METHOD_POST])]
class CheyController extends AbstractController
{
    public const NAME = 'chey';

    public function __invoke()
    {
        return $this->render('chey.php', []);
    }
}
