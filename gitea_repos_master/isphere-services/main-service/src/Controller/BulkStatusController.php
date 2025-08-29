<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\Bulk;
use App\Repository\BulkRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bulk/{id}', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class BulkStatusController extends AbstractController
{
    public const NAME = 'bulk_status';

    private const DEFAULT_COUNT_PER_PAGE = 10;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
    ) {
    }

    #[Template('bulk_status.html.twig')]
    public function __invoke(Request $request, int $id): array
    {
        $bulk = $this->bulkRepository->find($id);
        if (!$bulk) {
            throw $this->createNotFoundException();
        }

        if (Bulk::STATUS_WAIT_FOR_CONFIRMATION === $bulk->getStatus()) {
            $this->addFlash('info', 'Реестр <b>'.$bulk->getId().'</b> требует вашего подтверждения');
        }

        return [
            'bulk' => $bulk,
            'countPerPage' => $request->query->getInt('limit', self::DEFAULT_COUNT_PER_PAGE),
        ];
    }
}
