<?php

namespace EnvelopeBundle\Controller;

use Doctrine\DBAL\Types\DecimalType;
use EnvelopeBundle\Entity\BudgetAccount;
use EnvelopeBundle\Shared\BudgetAccountStats;
use EnvelopeBundle\Shared\BudgetAccountStatsLoader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class StatsController extends Controller
{
    public function budgetStatsAction(Request $request)
    {
        $session = $request->getSession();

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('a')
            ->from('EnvelopeBundle:BudgetAccount', 'a')
            ->join('EnvelopeBundle:BudgetGroup', 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessgroup')
            ->setParameter('accessgroup', $session->get('accessgroupid'))
        ;

        $budgetaccounts = $qb->getQuery()->getResult();

        $budgetAccountStatsLoader = new BudgetAccountStatsLoader($this->getDoctrine()->getManager());
        $budgetAccountStatsLoader->loadBudgetAccountStats();

        return $this->render(
            'EnvelopeBundle:Default:budgetaccountstats.html.twig',
            [
                'budgetaccounts' => $budgetaccounts,
            ]
        );
    }

}