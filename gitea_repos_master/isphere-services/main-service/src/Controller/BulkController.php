<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\Bulk;
use App\Entity\SystemUser;
use App\Form\Type\BulkFilterType;
use App\Form\Type\UploadBulkType;
use App\Message\ParseBulkFileMessage;
use App\Model\BulkFilter;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bulk', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_BULK)]
class BulkController extends AbstractController
{
    public const NAME = 'bulk';

    private const DEFAULT_COUNT_PER_PAGE = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function __invoke(#[CurrentUser] SystemUser $user, Request $request): Response
    {
        $uploadForm = $this->createForm(UploadBulkType::class);
        $uploadForm->add('_submit', SubmitType::class, [
            'label' => 'Обработать реестр',
        ]);

        $uploadForm->handleRequest($request);

        if ($uploadForm->isSubmitted() && $uploadForm->isValid()) {
            /** @var UploadedFile $file */
            $file = $uploadForm->get('file')->getData();
            $originalFilename = \pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.\uniqid($user->getUserIdentifier(), true).'.'.$file->guessExtension();

            try {
                $file->move($this->getParameter('app.bulk_path'), $newFilename);
            } catch (FileException $e) {
                $uploadForm->addError(new FormError($e->getMessage()));
            }

            $bulk = (new Bulk())
                ->setCreatedAt(new \DateTimeImmutable())
                ->setCreatedDate(new \DateTimeImmutable())
                ->setUser($user)
                ->setIp($request->getClientIp())
                ->setFilename($newFilename)
                ->setRecursive(0)
                ->setStatus(Bulk::STATUS_WAIT)
                ->setSources(\implode(',', $uploadForm->get('sources')->getData()));

            $this->entityManager->persist($bulk);
            $this->entityManager->flush();

            $message = (new ParseBulkFileMessage())
                ->setBulkId($bulk->getId());

            $this->messageBus->dispatch($message);

            $this->addFlash('success', 'Файл <b>'.$originalFilename.'</b> успешно загружен. Сформирован реестр №<b>'.$bulk->getId().'</b>');

            return $this->redirectToRoute(BulkStatusController::NAME, [
                'id' => $bulk->getId(),
            ]);
        }

        $filter = new BulkFilter();
        $form = $this->createForm(BulkFilterType::class, $filter, [
            'method' => Request::METHOD_GET,
        ]);

        $form->get('period')->get('from')->setData(new \DateTimeImmutable('first day of this month'));
        $form->get('period')->get('to')->setData(new \DateTimeImmutable('first day of next month - 1 second'));

        $form->add('_submit', SubmitType::class, [
            'label' => 'Обновить',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            \assert($filter instanceof BulkFilter);
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select([
                'r.id',
                'r.filename',
                'r.createdAt',
                'r.processedAt',
                'r.sources',
                'r.status',
                'r.resultsNote',

                'u.id as user_id',
                'u.login as user_login',
            ])
            ->from(Bulk::class, 'r')
            ->innerJoin('r.user', 'u')
            ->orderBy('r.id', Criteria::DESC);

        if (null !== $filter->getUser()) {
            $orX = $qb->expr()->orX(
                $qb->expr()->eq('u.id', ':userId'),
            );

            if (true === $filter->getNested()) {
                $qb2 = $this->entityManager->createQueryBuilder();
                $qb2
                    ->select('u2.id')
                    ->from(SystemUser::class, 'u2')
                    ->where(
                        $qb->expr()->eq('u2.masterUserId', ':userId'),
                    );

                $orX->add($qb->expr()->in('u.id', $qb2->getDQL()));
            }

            $qb
                ->andWhere($orX)
                ->setParameter('userId', $filter->getUser()->getId());
        }

        if (null !== $filter->getPeriod()) {
            $andX = $qb->expr()->andX();

            if (null !== $filter->getPeriod()->getFrom()) {
                $andX->add($qb->expr()->gte('r.createdDate', ':from'));
                $qb->setParameter('from', $filter->getPeriod()->getFrom());
            }

            if (null !== $filter->getPeriod()->getTo()) {
                $andX->add($qb->expr()->lte('r.createdDate', ':to'));
                $qb->setParameter('to', $filter->getPeriod()->getTo());
            }

            if ($andX->count() > 0) {
                $qb->andWhere($andX);
            }
        }

        $countPerPage = $request->query->getInt('limit');
        if ($countPerPage < 1) {
            $countPerPage = self::DEFAULT_COUNT_PER_PAGE;
        }

        $currentPage = $request->query->getInt('page');
        $qb->setMaxResults($countPerPage);
        $qb->setFirstResult($currentPage * $countPerPage);

        $query = $qb->getQuery();

        /** @var Paginator|Bulk[]|null $result */
        $result = new Paginator($query, fetchJoinCollection: true);
        $result->setUseOutputWalkers(false);

        $resultCount = \count($result);
        $totalPages = \ceil($resultCount / $countPerPage);

        return $this->render('bulk.html.twig', [
            'form' => $form->createView(),
            'uploadForm' => $uploadForm->createView(),
            'result' => $result,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'countPerPage' => $countPerPage,
        ]);
    }
}
