<?php

namespace App\Controller;

use App\Repository\BudgetAccountRepository;
use App\Repository\TransactionRepository;
use App\Service\BudgetAccountStatsLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    public function __construct(
        private readonly BudgetAccountRepository $budgetAccountRepository,
        private readonly BudgetAccountStatsLoader $budgetAccountStatsLoader,
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/stats', name: 'envelope_budget_stats')]
    public function budgetStatsAction(Request $request)
    {
        $budgetaccounts = $this->budgetAccountRepository->getUserBudgetAccounts();

        if ($request->query->get('startdate')) {
            $startDate = new \DateTime($request->query->get('startdate'));
        } else {
            $startDate = new \DateTime($this->transactionRepository->findUserFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $endDate = new \DateTime($request->query->get('enddate'));
        } else {
            $endDate = new \DateTime($this->transactionRepository->findUserLastTransactionDate());
        }

        $this->budgetAccountStatsLoader->loadBudgetAccountStats($startDate, $endDate);

        return $this->render(
            'default/budgetaccountstats.html.twig',
            [
                'budgetaccounts' => $budgetaccounts,
                'startdate' => $this->budgetAccountStatsLoader->getFirstTransactionDate(),
                'enddate' => $this->budgetAccountStatsLoader->getLastTransactionDate(),
            ]
        );
    }

    public function spendingStatsAction(Request $request)
    {
        $session = $request->getSession();

        if ($request->query->get('startdate')) {
            $startdate = new \DateTime($request->query->get('startdate'));
        } else {
            $startdate = new \DateTime($this->transactionRepository->findUserFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $enddate = new \DateTime($request->query->get('enddate'));
        } else {
            $enddate = new \DateTime($this->transactionRepository->findUserLastTransactionDate());
        }

        $excludeDescriptions = [
            'Fortnight Savings', 'Fortnight Cash', 'Credit Card Transfer', 'Savings',
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
              AND `Description` NOT IN ('".implode("','", $excludeDescriptions)."')
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
                'enddate' => $enddate->format('Y-m-d'),
            ]
        )) {
            foreach ($query as $result) {
                if ($result['numtransactions'] > 1) {
                    $results[] = [
                        'value' => abs($result['sumamount']),
                        'label' => "{$result['description']} ({$result['avgamount']} / {$result['numtransactions']})",
                    ];
                } else {
                    $excluded_transactions[] = $result;
                }
                $total = bcadd($total, $result['sumamount'], 2);
            }
        }

        foreach ($results as $key => $result) {
            $results[$key]['label'] .= ' '.round($result['value'] / $total * 100).'%';
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
