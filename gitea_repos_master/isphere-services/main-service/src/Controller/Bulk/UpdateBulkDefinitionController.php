<?php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\AbstractController;
use App\Entity\AccessRoles;
use App\Entity\Bulk;
use App\Model\ScalarDefinition;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/bulk/{id}/update-definition', name: self::NAME, requirements: ['id' => '\d+'], methods: Request::METHOD_POST)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class UpdateBulkDefinitionController extends AbstractController
{
    public const NAME = 'update_bulk_definition';

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $bulk = $this->bulkRepository->find($id);
        if (!$bulk) {
            throw $this->createNotFoundException();
        }

        if (Bulk::STATUS_WAIT_FOR_CONFIRMATION !== $bulk->getStatus()) {
            throw new LockedHttpException();
        }

        /** @var ScalarDefinition $modification */
        $modification = $this->serializer->deserialize(
            $request->getContent(),
            ScalarDefinition::class,
            JsonEncoder::FORMAT,
        );

        $errors = $this->validator->validate($modification);
        if ($errors->count() > 0) {
            throw new UnprocessableEntityHttpException((string) $errors);
        }

        /** @var ScalarDefinition $definition */
        foreach ($bulk->getDefinitions() as $definition) {
            if ($definition->getNumber() !== $modification->getNumber()) {
                continue;
            }

            $modification->setUnique($definition->isUnique());

            if (!$modification->isUnique()) {
                $modification->setIdentifier(false);
            }

            $bulk->removeDefinition($definition);
            $bulk->addDefinition($modification);

            break;
        }

        // @todo обход бага доктрины, когда она не видит изменения поля типа массив
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->update(Bulk::class, 'b')
            ->set('b.definitions', ':definitions')
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $bulk->getId())
            ->setParameter(
                'definitions',
                $this->serializer->serialize(
                    $bulk->getDefinitions(),
                    JsonEncoder::FORMAT,
                ),
            )
            ->getQuery()
            ->execute();

        return $this->json($bulk, Response::HTTP_OK, context: [
            'groups' => ['Public'],
        ]);
    }
}
