<?php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\AbstractController;
use App\Entity\AccessRoles;
use App\Entity\Bulk;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bulk/{id}/cancel', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class CancelBulkController extends AbstractController
{
    public const NAME = 'cancel_bulk';

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $bulk = $this->bulkRepository->find($id);
        if (!$bulk) {
            throw $this->createNotFoundException();
        }

        if (Bulk::STATUS_IN_PROGRESS !== $bulk->getStatus()
            && Bulk::STATUS_BEFORE_PROGRESS !== $bulk->getStatus()
        ) {
            throw new LockedHttpException();
        }

        $bulk->setStatus(Bulk::STATUS_NOT_COMPLETED);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        $this->addFlash('danger', 'Реестр №<b>'.$bulk->getId().'</b> успешно прерван');

        return $this->redirectToRoute('bulk_status', [
            'id' => $bulk->getId(),
        ]);
    }
}
