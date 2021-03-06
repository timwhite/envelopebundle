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
            ->setParameter('accessgroup', $session->get('accessgroupid'));

        $budgetaccounts = $qb->getQuery()->getResult();

        $budgetAccountStatsLoader = new BudgetAccountStatsLoader($this->getDoctrine()->getManager(), $request);
        $budgetAccountStatsLoader->loadBudgetAccountStats();

        return $this->render(
            'EnvelopeBundle:Default:budgetaccountstats.html.twig',
            [
                'budgetaccounts' => $budgetaccounts,
                'startdate' => $budgetAccountStatsLoader->getFirstTransactionDate(),
                'enddate' => $budgetAccountStatsLoader->getLastTransactionDate(),
            ]
        );
    }

    private function findFirstTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MIN(t.date)')
            ->from('EnvelopeBundle:Transaction', 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findLastTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MAX(t.date)')
            ->from('EnvelopeBundle:Transaction', 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function spendingStatsAction(Request $request) {
        $session = $request->getSession();

        if ($request->query->get('startdate')) {
            $startdate = new \DateTime($request->query->get('startdate'));
        } else {
            $startdate = new \DateTime($this->findFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $enddate = new \DateTime($request->query->get('enddate'));;
        } else {
            $enddate = new \DateTime($this->findLastTransactionDate());
        }

        $excludeDescriptions = [
            'Fortnight Savings', 'Fortnight Cash', 'Credit Card Transfer', 'Savings'
        ];

        $query = $this->getDoctrine()->getManager()->getConnection()->prepare("
            SELECT
              COUNT(SUBSTRING_INDEX( `Description` , '-', 1 )) AS numtransactions,
              SUBSTRING_INDEX( `Description` , '-', 1 ) AS description,
              SUM(`amount`) as sumamount,
              AVG(`amount`) as avgamount
            FROM `transaction`
              JOIN `account` ON `transaction`.`account_id` = `account`.`id`
            WHERE `amount` < 0
              AND `account`.`accessgroup_id` = :accessgroup
              AND `Description` NOT IN ('". implode("','", $excludeDescriptions) ."')
              AND `transaction`.`date` >= :startdate
              AND `transaction`.`date` <= :enddate
              GROUP BY SUBSTRING_INDEX( `Description` , '-', 1 )
              ORDER BY SUM(`amount`) ASC");

        $total = 0;
        $results = [];
        $excluded_transactions = [];
        if ($query->execute(
            [
                'accessgroup' => $session->get('accessgroupid'),
                'startdate' => $startdate->format('Y-m-d'),
                'enddate' => $enddate->format('Y-m-d')
            ]
        )) {

            foreach($query as $result) {
                if($result['numtransactions'] > 1) {
                    $results[] = [
                        'value' => abs($result['sumamount']),
                        'label' => "${result['description']} (${result['avgamount']} / ${result['numtransactions']})"
                    ];
                }else{
                    $excluded_transactions[] = $result;
                }
                $total = bcadd($total, $result['sumamount'], 2);
            }
        }

        foreach($results as $key => $result) {
            $results[$key]['label'] .= " " . round($result['value'] / $total * 100) . "%";
        }

        return $this->render(
            'EnvelopeBundle:Stats:spendingStats.html.twig',
            [
                'piechartvalues' => json_encode($results),
                'excludedtransactions' => $excluded_transactions,
                'startdate' => $startdate,
                'enddate' => $enddate,
            ]
        );


    }

    /* Spending location Query
    SELECT COUNT(SUBSTRING_INDEX( `Description` , '-', 1 )), SUBSTRING_INDEX( `Description` , '-', 1 ), SUM(`amount`), AVG(`amount`)
FROM `transaction`
JOIN `account` ON `transaction`.`account_id` = `account`.`id`
WHERE `amount` < 0
AND `account`.`accessgroup_id` = 1
AND `Description` NOT IN ('Fortnight Savings', 'Fortnight Cash', 'Credit Card Transfer')
GROUP BY SUBSTRING_INDEX( `Description` , '-', 1 )
ORDER BY SUM(`amount`) ASC
    */

}