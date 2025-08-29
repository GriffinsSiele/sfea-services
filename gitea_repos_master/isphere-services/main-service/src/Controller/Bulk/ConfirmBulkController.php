<?php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\AbstractController;
use App\Entity\AccessRoles;
use App\Entity\Bulk;
use App\Message\ConfirmBulkMessage;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bulk/{id}/confirm', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class ConfirmBulkController extends AbstractController
{
    public const NAME = 'confirm_bulk';

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $bulk = $this->bulkRepository->find($id);
        if (!$bulk) {
            throw $this->createNotFoundException();
        }

        if (Bulk::STATUS_WAIT_FOR_CONFIRMATION !== $bulk->getStatus()) {
            throw new LockedHttpException();
        }

        $bulk->setStatus(Bulk::STATUS_WAIT_CONFIRMED);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        $this->messageBus->dispatch(
            (new ConfirmBulkMessage())
                ->setBulkId($bulk->getId()),
        );

        $this->addFlash('success', 'Реестр №<b>'.$bulk->getId().'</b> успешно подтверждён');

        return $this->redirectToRoute('bulk_status', [
            'id' => $bulk->getId(),
        ]);
    }
}
