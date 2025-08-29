<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\RequestNew;
use App\Entity\SystemUser;
use App\Form\Type\HistoryFilterType;
use App\Model\HistoryFilter;
use App\Model\Period;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/history', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_HISTORY)]
class HistoryController extends AbstractController
{
    public const NAME = 'history';

    private const DEFAULT_COUNT_PER_PAGE = 20;

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(#[CurrentUser] SystemUser $user, Request $request): Response
    {
        $filter = new HistoryFilter();
        $filter->setPeriod(
            (new Period())
                ->setFrom(new \DateTimeImmutable('today midnight'))
                ->setTo(new \DateTimeImmutable('tomorrow midnight - 1 second')),
        );

        $form = $this->createForm(HistoryFilterType::class, $filter, [
            'method' => Request::METHOD_GET,
        ]);

        $form->add('_submit', SubmitType::class, [
            'label' => 'Обновить',
        ]);

        $form->handleRequest($request);

        /** @var Paginator|RequestNew[]|null $result */
        $result = null;
        $page = 0;
        $maxPage = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            \assert($filter instanceof HistoryFilter);
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('r')
            ->from(RequestNew::class, 'r')
            ->innerJoin('r.user', 'u');

        $this->addOrderByFromRequest($qb, $request);

        if (null !== $filter->getUser()) {
            $orX = $qb->expr()->orX(
                $qb->expr()->eq('r.userId', ':userId'),
            );

            if (true === $filter->getNested()) {
                $qb2 = $this->entityManager->createQueryBuilder();
                $qb2
                    ->select('u2.id')
                    ->from(SystemUser::class, 'u2')
                    ->where(
                        $qb->expr()->eq('u2.masterUserId', ':userId'),
                    );

                $orX->add($qb->expr()->in('r.userId', $qb2->getDQL()));
            }

            $qb
                ->andWhere($orX)
                ->setParameter('userId', $filter->getUser()->getId());
        }

        if (null !== $filter->getClient()) {
            $qb
                ->andWhere($qb->expr()->eq('r.clientId', ':clientId'))
                ->setParameter('clientId', $filter->getClient()->getId());
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

            $qb->andWhere($andX);
        }

        if ($request->query->has('minid')) {
            $qb
                ->andWhere($qb->expr()->gt('r.id', ':minid'))
                ->setParameter('minid', $request->query->getInt('minid'));
        }

        if ($request->query->has('maxid')) {
            $qb
                ->andWhere($qb->expr()->lt('r.id', ':maxid'))
                ->setParameter('maxid', $request->query->getInt('maxid'));
        }

        if (null !== $filter->getSource()
            || null !== $filter->getCheckType()
            || null !== $filter->getStatusCode()
        ) {
            $qb->innerJoin('r.responses', 'rr');

            if (null !== $filter->getCheckType()) {
                $qb
                    ->andWhere(
                        $qb->expr()->andX(
                            $qb->expr()->gt('rr.resCode', 0),
                            $qb->expr()->eq('rr.checkType', ':checkType'),
                        ),
                    )
                    ->setParameter('checkType', $filter->getCheckType());
            }
        }

        $countPerPage = $request->query->getInt('limit', self::DEFAULT_COUNT_PER_PAGE);
        $currentPage = $request->query->getInt('page');
        $qb->setMaxResults($countPerPage);
        $qb->setFirstResult($currentPage * $countPerPage);

        $query = $qb->getQuery();

        $result = new Paginator($query, fetchJoinCollection: true);
        $resultCount = \count($result);
        $totalPages = \ceil($resultCount / $countPerPage);

        return $this->render('history.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'countPerPage' => $countPerPage,
        ]);
    }

    private function addOrderByFromRequest(QueryBuilder $qb, Request $request): void
    {
        if (!$request->query->has('order')) {
            $qb->orderBy('r.id', Criteria::DESC);

            return;
        }

        $order = $request->query->get('order');
        $orderCriteria = Criteria::DESC;

        if (\preg_match('~(\w+)(?:\s+(\w+))?~', $order, $m)) {
            $order = $m[1];
            $orderCriteria = \strtoupper($m[2]) ?? Criteria::ASC;

            if (!\in_array($orderCriteria, [Criteria::ASC, Criteria::DESC], true)) {
                throw new BadRequestHttpException(\sprintf('Order criteria "%s" not supports', $orderCriteria));
            }
        }

        if (!$this->propertyAccessor->isReadable(new RequestNew(), $order)) {
            throw new BadRequestHttpException(\sprintf('Property value "%s" not available in this context', $order));
        }

        $qb->orderBy('r.'.$order, $orderCriteria);
    }
}
