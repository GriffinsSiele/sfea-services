<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\SystemUser;
use App\Repository\BulkRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bulk/{id}/response', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class BulkResponseController extends AbstractController
{
    public const NAME = 'bulk_response';

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
    ) {
    }

    public function __invoke(#[CurrentUser] SystemUser $user, int $id): Response
    {
        $bulk = $this->bulkRepository->find($id);
        if (!$bulk) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($bulk->getResultFilename());

        if ($this->mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $this->mimeTypeGuesser->guessMimeType($bulk->getResultFilename()));
        } else {
            $response->headers->set('Content-Type', 'application/octet-stream');
        }

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            \pathinfo($bulk->getResultFilename(), \PATHINFO_FILENAME),
        );

        return $response;
    }
}
