<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Entity\SystemUser;
use App\Form\Type\StatsFilterType;
use App\Model\Period;
use App\Model\StatsFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_REPORTS)]
class ReportsController extends AbstractController
{
    public const NAME = 'reports';

    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(#[CurrentUser] SystemUser $user, Request $request): Response
    {
        $filter = new StatsFilter();
        $filter
            ->setType('sources')
            ->setPeriod(
                (new Period())
                    ->setFrom(new \DateTimeImmutable('today midnight'))
                    ->setTo(new \DateTimeImmutable('tomorrow midnight - 1 second')),
            );

        $form = $this->createForm(StatsFilterType::class, $filter, [
            'method' => Request::METHOD_GET,
            'csrf_protection' => false,
        ]);

        $form->add('_submit', SubmitType::class, [
            'label' => 'Обновить',
        ]);

        if ($request->query->has('from')
            || $request->query->has('to')
        ) {
            $request->query->set('period', [
                'from' => $request->query->get('from'),
                'to' => $request->query->get('to'),
            ]);

            $request->query->remove('from');
            $request->query->remove('to');
        }

        if ($request->query->has('client_id')) {
            $request->query->set('client', $request->query->get('client_id'));
            $request->query->remove('client_id');
        }

        if ($request->query->has('user_id')) {
            $request->query->set('user', $request->query->get('user_id'));
            $request->query->remove('user_id');
        }

        $form->handleRequest($request);

        $filter = $form->getData();

        \ob_start();

        $connection = $this->entityManager->getConnection();

        $_REQUEST['type'] = $filter->getType();
        $_REQUEST['client_id'] = $filter->getClient()?->getId();
        $_REQUEST['user_id'] = $filter->getUser()?->getId();
        $_REQUEST['nested'] = $filter->getNested();
        $_REQUEST['ip'] = $filter->getIp();
        $_REQUEST['source'] = $filter->getSource();
        $_REQUEST['checktype'] = $filter->getCheckType();
        $_REQUEST['pay'] = $filter->getPay();
        $_REQUEST['order'] = $filter->getOrder();

        $_REQUEST['from'] = $filter->getPeriod()
            ?->getFrom()
            ?->format(\DateTimeInterface::ATOM);

        $_REQUEST['to'] = $filter->getPeriod()
            ?->getTo()
            ?->format(\DateTimeInterface::ATOM);

        require_once __DIR__.'/../../templates/reports.php';

        $table = \ob_get_clean();

        return $this->render('reports.html.twig', [
            'form' => $form->createView(),
            'table' => $table,
        ]);
    }
}
