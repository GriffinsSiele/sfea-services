<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/news', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_NEWS)]
class NewsController extends AbstractController
{
    public const NAME = 'news';

    public function __invoke(): Response
    {
        return $this->render('news.html.twig', []);
    }
}
