<?php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Entity\AccessRoles;
use App\Repository\BulkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/bulk/{id}/status', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_GET)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class BulkStatusApiController extends AbstractController
{
    public const NAME = 'bulk_api_status';

    public function __construct(
        private readonly BulkRepository $bulkRepository,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $params = $this->bulkRepository->findStatusAndCountsById($id);

        return $this->json([
            'id' => $id,
            'processedRows' => $params['processedRows'] ?? 0,
            'status' => $params['status'],
            'successRows' => $params['successRows'] ?? 0,
            'totalRows' => $params['totalRows'] ?? 0,
        ]);
    }
}
