<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/showresult', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_USER)]
class ShowResultController extends AbstractController
{
    public const NAME = 'showresult';

    public function __invoke(Request $request): Response
    {
        return $this->render('showresult.php', [
            'id' => $request->query->get('id'),
        ]);
    }
}
