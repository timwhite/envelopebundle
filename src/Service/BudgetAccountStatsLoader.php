<?php

namespace App\Service;

use App\Repository\BudgetAccountRepository;
use App\Repository\BudgetTransactionRepository;

class BudgetAccountStatsLoader
{
    private \DateTime $firstTransactionDate;
    private \DateTime $lastTransactionDate;
    private float $totalFortnights;

    public function __construct(private readonly BudgetAccountRepository $budgetAccountRepository, private readonly BudgetTransactionRepository $budgetTransactionRepository)
    {
    }

    public function getFirstTransactionDate(): \DateTime
    {
        return $this->firstTransactionDate;
    }

    public function getLastTransactionDate(): \DateTime
    {
        return $this->lastTransactionDate;
    }

    private function loadDateRange(): void
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

    private function loadNegativeSums(): void
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

    private function loadIncomeSums(): void
    {
        foreach ($this->budgetTransactionRepository->getIncomeSums($this->firstTransactionDate, $this->lastTransactionDate) as $result) {
            $budgetAccount = $this->budgetAccountRepository->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->setAverageFortnightlyIncome($result['positivesum'] / $this->totalFortnights);
                $stats->setFirstIncomeTransactionDate(new \DateTime($result['mindate']));
                $stats->setLastIncomeTransactionDate(new \DateTime($result['maxdate']));
            }
        }
    }

    private function loadWeeklySums(): void
    {
        foreach ($this->budgetTransactionRepository->getWeeklySums($this->firstTransactionDate, $this->lastTransactionDate) as $result) {
            $budgetAccount = $this->budgetAccountRepository->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->appendWeekRunningTotal($result['yearweeknum'], $result['weeksum']);
            }
        }
    }

    private function loadWeeklySpends(): void
    {
        foreach ($this->budgetTransactionRepository->getWeeklySpends($this->firstTransactionDate, $this->lastTransactionDate) as $result) {
            $budgetAccount = $this->budgetAccountRepository->find($result['budget_account_id']);
            if ($budgetAccount) {
                $stats = $budgetAccount->getBudgetStats();
                $stats->appendWeekSpend($result['yearweeknum'], $result['weekspend']);
            }
        }
    }

    /**
     * Loads Stats and Injects them into BudgetAccount objects (which can be retrieved through normal methods).
     */
    public function loadBudgetAccountStats(\DateTime $startDate, \DateTime $endDate): void
    {
        $this->firstTransactionDate = $startDate;
        $this->lastTransactionDate = $endDate;
        $this->loadDateRange();
        $this->loadPositiveSums();
        $this->loadNegativeSums();
        $this->loadIncomeSums();
        $this->loadWeeklySums();
        $this->loadWeeklySpends();
    }
}
