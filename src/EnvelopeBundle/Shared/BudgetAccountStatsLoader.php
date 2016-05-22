<?php

namespace EnvelopeBundle\Shared;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use EnvelopeBundle\Entity\BudgetAccount;

class BudgetAccountStatsLoader
{
    /** @var  EntityManager $em */
    private $em;

    /** @var Request $request */
    private $request;

    private $firstTransactionDate;
    private $lastTransactionDate;
    private $totalFortnights;

    public function __construct(EntityManager $em, Request $request)
    {
        $this->em = $em;
        $this->request = $request;
    }

    public function getFirstTransactionDate()
    {
        return $this->firstTransactionDate;
    }

    public function getLastTransactionDate()
    {
        return $this->lastTransactionDate;
    }

    private function loadDateRange()
    {
        $query = $this->em->getConnection()->prepare("
            SELECT MAX(`date`) as maxdate, MIN(`date`) as mindate
            FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            WHERE
              transaction.amount != 0
              AND budget_transaction.amount < 0");
        if ($query->execute()) {
            $result = $query->fetch();
            $this->firstTransactionDate = new \DateTime($result['mindate']);
            $this->lastTransactionDate = new \DateTime($result['maxdate']);
            $this->totalFortnights = $this->lastTransactionDate->diff($this->firstTransactionDate)->days / 14;
        }

        if ($this->request->query->get('startdate')) {
            $this->firstTransactionDate = new \DateTime($this->request->query->get('startdate'));
        }
        if ($this->request->query->get('enddate')) {
            $this->lastTransactionDate = new \DateTime($this->request->query->get('enddate'));;
        }

        // Load all Budget Accounts to set common data
        $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
        /** @var BudgetAccount $budgetAccounts */
        $budgetAccounts = $budgetAccountRepo->findAll();
        /** @var BudgetAccountStats $stats */
        foreach ($budgetAccounts as $budgetAccount) {
            $stats = $budgetAccount->getBudgetStats();
            $stats->setFirstTransactionDate($this->firstTransactionDate);
            $stats->setLastTransactionDate($this->lastTransactionDate);
        }

    }

    private function loadNegativeSums()
    {

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
        foreach ($query as $result) {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->setNegativeSum($result['negativesum']);
                $stats->setAverageFortnightlySpend($result['negativesum'] / $this->totalFortnights);
                $stats->setFirstSpendTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastSpendTransactionDate(new \DateTime($result['maxdate']));
            }
        }
    }

    private function loadPositiveSums()
    {

        $query = $this->em->getConnection()->prepare("
            SELECT budgetName,
                budget_account_id,
                MIN(date) as mindate,
                MAX(date) as maxdate,
                SUM(budget_transaction.amount) as positivesum
            FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id
            WHERE
              transaction.amount = 0
              AND budget_transaction.amount > 0
            GROUP BY budget_account_id");
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->setPositiveSum($result['positivesum']);
                $stats->setAverageFortnightlyPositive($result['positivesum'] / $this->totalFortnights);
                $stats->setFirstIncomeTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastIncomeTransactionDate(new \DateTime($result['maxdate']));
            }
        }
    }

    private function loadIncomeSums()
    {

        $query = $this->em->getConnection()->prepare("
            SELECT budgetName,
                budget_account_id,
                MIN(date) as mindate,
                MAX(date) as maxdate,
                SUM(budget_transaction.amount) as positivesum
            FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id
            WHERE
              transaction.amount != 0
              AND budget_transaction.amount > 0
            GROUP BY budget_account_id");
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->setAverageFortnightlyIncome($result['positivesum'] / $this->totalFortnights);
                $stats->setFirstIncomeTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastIncomeTransactionDate(new \DateTime($result['maxdate']));
            }
        }
    }

    private function loadWeeklySums()
    {
        $query = $this->em->getConnection()->prepare("
          SELECT
           YEARWEEK(transaction.date, 3) AS yearweeknum,
            budgetName,
            budget_account_id,
            SUM(budget_transaction.amount) as weeksum
          FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id

            GROUP BY budget_account_id, yearweeknum
ORDER BY yearweeknum, budget_account_id");
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->appendWeekRunningTotal($result['yearweeknum'], $result['weeksum']);
            }
        }
    }

    private function loadWeeklySpends()
    {
        $query = $this->em->getConnection()->prepare("
          SELECT
           YEARWEEK(transaction.date, 3) AS yearweeknum,
            budgetName,
            budget_account_id,
            SUM(budget_transaction.amount) as weekspend
          FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id
            WHERE budget_transaction.amount < 0
            GROUP BY budget_account_id, yearweeknum
            ORDER BY yearweeknum, budget_account_id");
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->em->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->appendWeekSpend($result['yearweeknum'], $result['weekspend']);
            }
        }
    }

    /**
     * Loads Stats and Injects them into BudgetAccount objects (which can be retrieved through normal methods)
     */
    public function loadBudgetAccountStats()
    {
        $this->loadDateRange();
        $this->loadPositiveSums();
        $this->loadNegativeSums();
        $this->loadIncomeSums();
        $this->loadWeeklySums();
        $this->loadWeeklySpends();

    }
}