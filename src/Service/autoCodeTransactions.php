<?php

namespace App\Service;

use App\Entity\BudgetTransaction;
use App\Repository\AutoCodeSearchRepository;
use App\Repository\TransactionRepository;

class autoCodeTransactions
{
    private $codedResults = [];

    public function __construct(private AutoCodeSearchRepository $autoCodeSearchRepository, private TransactionRepository $transactionRepository)
    {
    }

    /**
     * Auto code transactions (based on searches the user has access to).
     */
    public function codeTransactions(): void
    {
        foreach ($this->autoCodeSearchRepository->findUsersSearches() as $search) {
            foreach ($this->transactionRepository->searchTransactions($search) as $transaction) {
                $budgetTransaction = new BudgetTransaction();
                $budgetTransaction->setAmount($transaction->getAmount());
                $budgetTransaction->setBudgetAccount($search->getBudgetAccount());
                $budgetTransaction->setTransaction($transaction);
                $this->codedResults[] = [
                    'transaction' => $transaction,
                    'search' => $search,
                ];

                if ('' != $search->getRename()) {
                    $transaction->setDescription($search->getRename());
                    $this->transactionRepository->persistTransaction($transaction);
                }
            }
        }
    }

    public function getResults()
    {
        return $this->codedResults;
    }
}
