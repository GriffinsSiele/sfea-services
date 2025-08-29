<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/doc', name: self::NAME, methods: [Request::METHOD_GET])]
class DocController extends AbstractController
{
    public const NAME = 'doc';

    public function __invoke()
    {
        return $this->render('doc.html.twig', []);
    }
}
