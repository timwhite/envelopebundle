<?php

namespace EnvelopeBundle\Shared;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class BudgetAccountStatsLoader
{
    /** @var  EntityManager $em */
    private $em;
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Loads Stats and Injects them into BudgetAccount objects (which can be retrieved through normal methods)
     */
    public function loadBudgetAccountStats(){
        $query = $this->em->getConnection()->prepare("
            SELECT MAX(`date`) as maxdate, MIN(`date`) as mindate
            FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            WHERE
              transaction.amount != 0
              AND budget_transaction.amount < 0");
        if($query->execute()) {
            $result = $query->fetch();
            $mindate = new \DateTime($result['mindate']);
            $maxdate = new \DateTime($result['maxdate']);
            $fortnights = $maxdate->diff($mindate)->days/14;
        }

        $query = $this->em->getConnection()->prepare("
            SELECT budgetName, budget_account_id, MIN(date) as mindate, MAX(date) as maxdate, SUM(budget_transaction.amount) as negativesum
            FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id
            WHERE
              transaction.amount != 0
              AND budget_transaction.amount < 0
            GROUP BY budget_account_id");
        $query->execute();
        foreach($query as $result)
        {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if($budgetAccount) {
                $stats = new BudgetAccountStats();
                $stats->setNegativeSum($result['negativesum']);
                $stats->setAverageFortnightlySpend($result['negativesum']/$fortnights);
                $stats->setFirstSpendTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastSpendTransactionDate(new \DateTime($result['maxdate']));
                $budgetAccount->setBudgetStats($stats);
            }
        }

    }
}