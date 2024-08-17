<?php

namespace App\Service;

use App\Entity\BudgetAccount;
use App\Repository\BudgetAccountRepository;
use App\Repository\BudgetTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class BudgetAccountStatsLoader
{
    /** @var \DateTime */
    private $firstTransactionDate;
    /** @var \DateTime */
    private $lastTransactionDate;
    private $totalFortnights;

    public function __construct(private EntityManagerInterface $entityManager, private BudgetAccountRepository $budgetAccountRepository, private BudgetTransactionRepository $budgetTransactionRepository)
    {
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
        // Don't calculate the number of fortnights until we have established our date range
        $this->totalFortnights = $this->lastTransactionDate->diff($this->firstTransactionDate)->days / 14.0;

        $budgetAccounts = $this->budgetAccountRepository->getUserBudgetAccounts();
        foreach ($budgetAccounts as $budgetAccount) {
            $stats = $budgetAccount->getBudgetStats();
            $stats->setFirstTransactionDate($this->firstTransactionDate);
            $stats->setLastTransactionDate($this->lastTransactionDate);
        }
    }

    private function loadNegativeSums()
    {
        foreach ($this->budgetTransactionRepository->getNegativeBudgetSums($this->firstTransactionDate, $this->lastTransactionDate) as $result) {
            $budgetAccount = $this->budgetAccountRepository->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->setNegativeSum($result['negativesum']);
                $stats->setAverageFortnightlySpend($result['negativesum'] / $this->totalFortnights);
                $stats->setFirstSpendTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastSpendTransactionDate(new \DateTime($result['maxdate']));
            }
        }
    }

    private function loadPositiveSums(): void
    {
        foreach ($this->budgetTransactionRepository->getPositiveBudgetSums($this->firstTransactionDate, $this->lastTransactionDate) as $result) {
            $budgetAccount = $this->budgetAccountRepository->find($result['budget_account_id']);
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
        $query = $this->entityManager->getConnection()->prepare('
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
              AND date >= :startdate
              AND date <= :enddate
            GROUP BY budget_account_id');
        $query->execute(
            [
                'startdate' => $this->firstTransactionDate->format('Y-m-d H:i:s'),
                'enddate' => $this->lastTransactionDate->format('Y-m-d H:i:s'),
            ]
        );
        foreach ($query as $result) {
            $budgetAccountRepo = $this->entityManager->getRepository('EnvelopeBundle:BudgetAccount');
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
        $query = $this->entityManager->getConnection()->prepare('
          SELECT
           YEARWEEK(transaction.date, 3) AS yearweeknum,
            budgetName,
            budget_account_id,
            SUM(budget_transaction.amount) as weeksum
          FROM budget_transaction
            JOIN transaction ON transaction_id = transaction.id
            JOIN budget_account on budget_account_id = budget_account.id

            GROUP BY budget_account_id, yearweeknum
              ORDER BY yearweeknum, budget_account_id');
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->entityManager->getRepository('EnvelopeBundle:BudgetAccount');
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
        $query = $this->entityManager->getConnection()->prepare('
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
            ORDER BY yearweeknum, budget_account_id');
        $query->execute();
        foreach ($query as $result) {
            $budgetAccountRepo = $this->entityManager->getRepository('EnvelopeBundle:BudgetAccount');
            /** @var BudgetAccount $budgetAccount */
            $budgetAccount = $budgetAccountRepo->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->appendWeekSpend($result['yearweeknum'], $result['weekspend']);
            }
        }
    }

    /**
     * Loads Stats and Injects them into BudgetAccount objects (which can be retrieved through normal methods).
     */
    public function loadBudgetAccountStats(\DateTime $startDate, \DateTime $endDate)
    {
        $this->firstTransactionDate = $startDate;
        $this->lastTransactionDate = $endDate;
        $this->loadDateRange();
        $this->loadPositiveSums();
        $this->loadNegativeSums();
        //        $this->loadIncomeSums();
        //        $this->loadWeeklySums();
        //        $this->loadWeeklySpends();
    }
}
