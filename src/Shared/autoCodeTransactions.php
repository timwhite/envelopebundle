<?php

namespace App\Shared;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AutoCodeSearch;
use App\Entity\BudgetTransaction;

class autoCodeTransactions
{
    private $codedResults = [];

    /**
     * @param $em EntityManagerInterface
     * @param $accessgroup
     */
    public function codeTransactions($em, $accessgroup)
    {
        /** @var AutoCodeSearch[] $searches */
        $searches = $em->getRepository(AutoCodeSearch::class)->findByAccessGroup($accessgroup);
        /** @var TransactionRepository $transctionRepository */
        $transctionRepository = $em->getRepository(Transaction::class);

        foreach ($searches as $search) {
            $transactions = $transctionRepository->findUnassignedTransactions($search, $accessgroup);

            foreach ($transactions as $transaction) {
                $budgetTransaction = new BudgetTransaction();
                $budgetTransaction->setAmount($transaction->getAmount());
                $budgetTransaction->setBudgetAccount($search->getBudgetAccount());
                $budgetTransaction->setTransaction($transaction);
                $this->codedResults[] = [
                    "transaction" => $transaction,
                    "search" => $search
                ];

                if ($search->getRename() != "") {
                    $transaction->setDescription($search->getRename());
                    $em->persist($transaction);
                }

                $em->persist($budgetTransaction);
            }
            $em->flush();
        }
    }

    public function getResults()
    {
        return $this->codedResults;
    }
}