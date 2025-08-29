<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_USER)]
class AdminController extends AbstractController
{
    public const NAME = 'admin';

    public function __invoke(): Response
    {
        return $this->render('admin.php', []);
    }
}
