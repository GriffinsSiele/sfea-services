<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\SystemUser;
use App\Repository\RequestNewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: self::NAME, methods: [Request::METHOD_GET, Request::METHOD_POST])]
#[IsGranted(AccessRoles::ROLE_USER)]
class DefaultController extends AbstractController
{
    public const NAME = 'default';

    public function __construct(
        private readonly RequestNewRepository $requestNewRepository,
    ) {
    }

    public function __invoke(#[CurrentUser] SystemUser $user, Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)
            && !$request->attributes->has('_skipLimits')
        ) {
            $maxProcessing = 50;

            if (3302 === $user->getId()) {
                $maxProcessing = 10;
            }

            $countOfProcessing = $this->requestNewRepository->findCountOfProcessingBySystemUser($user);

            if ($countOfProcessing->getProcessing() >= $maxProcessing) {
                throw new TooManyRequestsHttpException();
            }

            if ($countOfProcessing->getTotalProcessing() >= 300) {
                throw new ServiceUnavailableHttpException();
            }
        }

        return $this->render('index.php', []);
    }
}
